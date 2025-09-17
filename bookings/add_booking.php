<?php
// Add Booking: User books an event
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pageTitle = 'Book Event';
include '../includes/header.php';
include '../includes/db_connect.php';

// Security check - only users can book events
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 3) {
    header('Location: ../auth/login.php');
    exit;
}

$error = '';
$success = '';
$event = null;

// Get event ID from POST or GET
$event_id = intval($_POST['event_id'] ?? $_GET['event_id'] ?? 0);

if ($event_id <= 0) {
    $error = 'Invalid event ID.';
} else {
    // Fetch event details
    try {
        $stmt = $connection->prepare("SELECT e.*, c.category as category_name FROM events e LEFT JOIN event_categories c ON e.category_id = c.id WHERE e.id = ? AND e.status = 'approved'");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = 'Event not found or not available for booking.';
        } else {
            $event = $result->fetch_assoc();
            
            // Check if event date has passed
            if (strtotime($event['event_date']) <= time()) {
                $error = 'This event has already passed.';
            }
            
            // Check if seats are available
            if ($event['total_seats'] <= 0) {
                $error = 'Sorry, this event is sold out.';
            }
            
            // Check if user has already booked this event
            $existing_booking = $connection->prepare("SELECT id FROM bookings WHERE user_id = ? AND event_id = ?");
            $existing_booking->bind_param("ii", $_SESSION['user_id'], $event_id);
            $existing_booking->execute();
            
            if ($existing_booking->get_result()->num_rows > 0) {
                $error = 'You have already booked this event.';
            }
        }
    } catch (Exception $e) {
        $error = 'Database error. Please try again later.';
    }
}

// Process booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($event) && empty($error)) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid request. Please try again.';
    } else {
        $seats = intval($_POST['seats'] ?? 1);
        
        if ($seats <= 0) {
            $error = 'Number of seats must be at least 1.';
        } elseif ($seats > $event['total_seats']) {
            $error = 'Not enough seats available.';
        } elseif ($seats > 10) {
            $error = 'Maximum 10 seats can be booked per transaction.';
        } else {
            try {
                // Start transaction
                $connection->begin_transaction();
                
                // Generate unique booking number
                $booking_number = 'BK' . date('Ymd') . sprintf('%03d', rand(100, 999));
                
                // Check booking number uniqueness
                $check_stmt = $connection->prepare("SELECT id FROM bookings WHERE booking_number = ?");
                $check_stmt->bind_param("s", $booking_number);
                $check_stmt->execute();
                
                // If booking number exists, generate new one
                while ($check_stmt->get_result()->num_rows > 0) {
                    $booking_number = 'BK' . date('Ymd') . sprintf('%03d', rand(100, 999));
                    $check_stmt->execute();
                }
                
                // Insert booking
                $booking_stmt = $connection->prepare("INSERT INTO bookings (user_id, event_id, booking_number, seats, booking_date) VALUES (?, ?, ?, ?, NOW())");
                $booking_stmt->bind_param("iisi", $_SESSION['user_id'], $event_id, $booking_number, $seats);
                
                if (!$booking_stmt->execute()) {
                    throw new Exception("Failed to create booking");
                }
                
                $booking_id = $connection->insert_id;
                
                // Update event seats
                $update_stmt = $connection->prepare("UPDATE events SET total_seats = total_seats - ? WHERE id = ?");
                $update_stmt->bind_param("ii", $seats, $event_id);
                
                if (!$update_stmt->execute()) {
                    throw new Exception("Failed to update event seats");
                }
                
                // Create payment record
                $total_amount = $event['price'] * $seats;
                $payment_stmt = $connection->prepare("INSERT INTO payments (booking_id, amount, status, created_at) VALUES (?, ?, 'success', NOW())");
                $payment_stmt->bind_param("id", $booking_id, $total_amount);
                
                if (!$payment_stmt->execute()) {
                    throw new Exception("Failed to create payment record");
                }
                
                // Commit transaction
                $connection->commit();
                
                $success = "Booking confirmed! Your booking number is: " . $booking_number;
                
                // Refresh event data
                $stmt->execute();
                $event = $stmt->get_result()->fetch_assoc();
                
            } catch (Exception $e) {
                $connection->rollback();
                $error = 'Booking failed. Please try again.';
            }
        }
    }
}

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<div class="container" style="max-width: 800px; margin: 40px auto; padding: 0 20px;">
    <div class="form-container">
        <h2 style="text-align: center; margin-bottom: 30px; color: #FFD700;">
            <i class="fas fa-ticket-alt"></i> Book Event
        </h2>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <strong>Error:</strong> <?= htmlspecialchars($error) ?>
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="../events/view_events.php" class="button-backtohome">Back to Events</a>
            </div>
        <?php elseif (!empty($success)): ?>
            <div class="alert alert-success">
                <strong>Success:</strong> <?= htmlspecialchars($success) ?>
            </div>
            <div style="text-align: center; margin-top: 20px; display: flex; gap: 15px; justify-content: center;">
                <a href="../dashboards/user_dashboard.php" class="button-exploreevents">View My Bookings</a>
                <a href="../events/view_events.php" class="button-backtohome">Book Another Event</a>
            </div>
        <?php elseif (!empty($event)): ?>
            <!-- Event Details -->
            <div style="background: #2a2a2a; padding: 25px; border-radius: 12px; margin-bottom: 30px; border-left: 4px solid #FFD700;">
                <h3 style="margin-top: 0; color: #FFD700; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-calendar-star"></i>
                    <?= htmlspecialchars($event['title']) ?>
                </h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
                    <div>
                        <strong style="color: #FFD700;">📅 Date & Time:</strong><br>
                        <span style="color: #ccc;"><?= date('F j, Y \a\t g:i A', strtotime($event['event_date'])) ?></span>
                    </div>
                    <div>
                        <strong style="color: #FFD700;">📍 Venue:</strong><br>
                        <span style="color: #ccc;"><?= htmlspecialchars($event['venue']) ?></span>
                    </div>
                    <div>
                        <strong style="color: #FFD700;">🏷️ Category:</strong><br>
                        <span style="color: #ccc;"><?= htmlspecialchars($event['category_name'] ?? 'General') ?></span>
                    </div>
                    <div>
                        <strong style="color: #FFD700;">💰 Price per ticket:</strong><br>
                        <span style="color: #28a745; font-weight: bold; font-size: 1.2em;">
                            <?= $event['price'] > 0 ? 'LKR ' . number_format($event['price'], 2) : 'FREE' ?>
                        </span>
                    </div>
                </div>
                
                <?php if (!empty($event['description'])): ?>
                    <div style="margin-top: 20px;">
                        <strong style="color: #FFD700;">📝 Description:</strong><br>
                        <p style="color: #ccc; margin-top: 8px; line-height: 1.6;"><?= htmlspecialchars($event['description']) ?></p>
                    </div>
                <?php endif; ?>
                
                <div style="margin-top: 20px; padding: 15px; background: rgba(255, 215, 0, 0.1); border-radius: 8px; border: 1px solid #FFD700;">
                    <strong style="color: #FFD700;">🎫 Available Seats: <?= $event['total_seats'] ?></strong>
                </div>
            </div>

            <!-- Booking Form -->
            <form method="post" class="beautiful-form" onsubmit="return validateBookingForm()">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                
                <div class="form-group">
                    <label for="seats">Number of Tickets</label>
                    <input type="number" name="seats" id="seats" value="1" min="1" max="<?= min(10, $event['total_seats']) ?>" required>
                    <small style="color: #aaa;">
                        Maximum <?= min(10, $event['total_seats']) ?> tickets per booking
                        <?php if ($event['total_seats'] < 10): ?>
                            (Limited by availability)
                        <?php endif; ?>
                    </small>
                </div>
                
                <!-- Price Calculation -->
                <div style="background: #1a1a1a; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h4 style="margin-top: 0; color: #FFD700;">💳 Booking Summary</h4>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>Ticket Price:</span>
                        <span id="unit-price">LKR <?= number_format($event['price'], 2) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>Number of Tickets:</span>
                        <span id="ticket-count">1</span>
                    </div>
                    <hr style="border: 1px solid #333; margin: 15px 0;">
                    <div style="display: flex; justify-content: space-between; font-size: 1.2em; font-weight: bold;">
                        <span style="color: #FFD700;">Total Amount:</span>
                        <span style="color: #28a745;" id="total-amount">LKR <?= number_format($event['price'], 2) ?></span>
                    </div>
                </div>
                
                <!-- Terms and Conditions -->
                <div style="background: #2a2a2a; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107;">
                    <h4 style="margin-top: 0; color: #ffc107;">📋 Terms & Conditions</h4>
                    <ul style="margin: 0; padding-left: 20px; color: #ccc; line-height: 1.6;">
                        <li>Tickets are non-transferable and non-refundable</li>
                        <li>Please arrive at least 15 minutes before the event starts</li>
                        <li>Valid ID may be required for entry</li>
                        <li>Event organizers reserve the right to refuse entry</li>
                        <li>In case of event cancellation, full refund will be provided</li>
                    </ul>
                    
                    <div style="margin-top: 15px;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="agree-terms" required style="transform: scale(1.2);">
                            <span style="color: #FFD700;">I agree to the terms and conditions</span>
                        </label>
                    </div>
                </div>

                <div class="form-actions" style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px;">
                    <a href="../events/view_events.php" class="button-backtohome">Cancel</a>
                    <button type="submit" class="button-exploreevents" style="min-width: 180px;">
                        <i class="fas fa-credit-card"></i> Confirm Booking
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
function validateBookingForm() {
    const seats = parseInt(document.getElementById('seats').value);
    const agreeTerms = document.getElementById('agree-terms').checked;
    const maxSeats = <?= min(10, $event['total_seats'] ?? 1) ?>;
    
    if (seats < 1) {
        showError('Number of tickets must be at least 1.');
        return false;
    }
    
    if (seats > maxSeats) {
        showError(`Maximum ${maxSeats} tickets can be booked.`);
        return false;
    }
    
    if (!agreeTerms) {
        showError('Please agree to the terms and conditions.');
        return false;
    }
    
    return true;
}

function showError(message) {
    alert(message); // Simple alert for now, can be enhanced
}

// Update price calculation when seats change
document.addEventListener('DOMContentLoaded', function() {
    const seatsInput = document.getElementById('seats');
    const unitPrice = <?= $event['price'] ?? 0 ?>;
    
    if (seatsInput) {
        seatsInput.addEventListener('input', function() {
            const seats = parseInt(this.value) || 1;
            const totalPrice = unitPrice * seats;
            
            document.getElementById('ticket-count').textContent = seats;
            document.getElementById('total-amount').textContent = 'LKR ' + totalPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        });
    }
    
    // Form submission loading state
    const form = document.querySelector('.beautiful-form');
    const submitBtn = form.querySelector('button[type="submit"]');
    
    form.addEventListener('submit', function() {
        if (validateBookingForm()) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            // Re-enable after 10 seconds as failsafe
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-credit-card"></i> Confirm Booking';
            }, 10000);
        }
    });
});
</script>

<style>
.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: slideIn 0.4s ease-out;
}

.alert-error {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
    border-color: #dc3545;
}

.alert-success {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
    border-color: #28a745;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.form-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 30px;
}

@media (max-width: 768px) {
    .form-actions {
        flex-direction: column;
        gap: 15px;
    }
    
    .form-actions .button-exploreevents,
    .form-actions .button-backtohome {
        width: 100%;
        text-align: center;
    }
}
</style>

<?php include '../includes/footer.php'; ?>