<?php
// Add Event: Organizer/Admin can create events
session_start();
$pageTitle = 'Add New Event';
include '../includes/header.php';
include '../includes/db_connect.php';

// Security check - only organizers and admins can access
if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] !== 1 && $_SESSION['role_id'] !== 2)) {
    header('Location: ../auth/login.php');
    exit;
}

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

// Fetch event categories
try {
    $categories_result = $connection->query("SELECT * FROM event_categories ORDER BY name");
} catch (Exception $e) {
    $categories_result = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid request. Please try again.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        $venue = trim($_POST['venue'] ?? '');
        $event_date = $_POST['event_date'] ?? '';
        $total_seats = intval($_POST['total_seats'] ?? 0);
        $price = floatval($_POST['price'] ?? 0);
        $organizer_id = $_SESSION['user_id'];

        // Validation
        if (empty($title)) {
            $error = 'Event title is required.';
        } elseif (strlen($title) < 3) {
            $error = 'Event title must be at least 3 characters long.';
        } elseif (empty($description)) {
            $error = 'Event description is required.';
        } elseif (strlen($description) < 10) {
            $error = 'Event description must be at least 10 characters long.';
        } elseif ($category_id <= 0) {
            $error = 'Please select a valid category.';
        } elseif (empty($venue)) {
            $error = 'Event venue is required.';
        } elseif (empty($event_date)) {
            $error = 'Event date and time is required.';
        } elseif (strtotime($event_date) <= time()) {
            $error = 'Event date must be in the future.';
        } elseif ($total_seats <= 0) {
            $error = 'Number of seats must be greater than 0.';
        } elseif ($total_seats > 10000) {
            $error = 'Number of seats cannot exceed 10,000.';
        } elseif ($price < 0) {
            $error = 'Price cannot be negative.';
        } elseif ($price > 100000) {
            $error = 'Price cannot exceed LKR 100,000.';
        } else {
            try {
                // Check if category exists
                $cat_check = $connection->prepare("SELECT id FROM event_categories WHERE id = ?");
                $cat_check->bind_param("i", $category_id);
                $cat_check->execute();
                if ($cat_check->get_result()->num_rows === 0) {
                    $error = 'Invalid category selected.';
                } else {
                    // Insert event
                    $stmt = $connection->prepare("INSERT INTO events (organizer_id, category_id, title, description, venue, event_date, total_seats, price, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
                    $stmt->bind_param("iisssiid", $organizer_id, $category_id, $title, $description, $venue, $event_date, $total_seats, $price);
                    
                    if ($stmt->execute()) {
                        $success = 'Event created successfully! It will be reviewed by administrators before being published.';
                        // Clear form data
                        $title = $description = $venue = $event_date = '';
                        $category_id = $total_seats = $price = 0;
                        // Regenerate CSRF token
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    } else {
                        $error = 'Failed to create event. Please try again.';
                    }
                }
            } catch (Exception $e) {
                $error = 'Database error. Please try again later.';
            }
        }
    }
}
?>

<div class="container" style="max-width: 800px; margin: 40px auto; padding: 0 20px;">
    <div class="form-container">
        <h2 style="text-align: center; margin-bottom: 30px; color: #FFD700;">
            <i class="fas fa-plus-circle"></i> Add New Event
        </h2>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <strong>Error:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <strong>Success:</strong> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="beautiful-form" onsubmit="return validateEventForm()">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-group">
                <label for="title">Event Title *</label>
                <input type="text" name="title" id="title" value="<?= htmlspecialchars($title ?? '') ?>" required maxlength="255" placeholder="Enter event title">
                <small style="color: #aaa;">Minimum 3 characters, maximum 255 characters</small>
            </div>

            <div class="form-group">
                <label for="description">Event Description *</label>
                <textarea name="description" id="description" rows="5" required maxlength="2000" placeholder="Describe your event in detail..."><?= htmlspecialchars($description ?? '') ?></textarea>
                <small style="color: #aaa;">Minimum 10 characters, maximum 2000 characters</small>
            </div>

            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label for="category_id">Event Category *</label>
                    <select name="category_id" id="category_id" required>
                        <option value="">Select Category</option>
                        <?php if ($categories_result && $categories_result->num_rows > 0): ?>
                            <?php while ($category = $categories_result->fetch_assoc()): ?>
                                <option value="<?= $category['id'] ?>" <?= (isset($category_id) && $category_id == $category['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name'] ?? $category['category'] ?? 'Unknown') ?>
                                </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="">No categories available</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="venue">Venue *</label>
                    <input type="text" name="venue" id="venue" value="<?= htmlspecialchars($venue ?? '') ?>" required maxlength="255" placeholder="Event venue/location">
                </div>
            </div>

            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label for="event_date">Event Date & Time *</label>
                    <input type="datetime-local" name="event_date" id="event_date" value="<?= isset($event_date) ? date('Y-m-d\TH:i', strtotime($event_date)) : '' ?>" required min="<?= date('Y-m-d\TH:i', strtotime('+1 hour')) ?>">
                    <small style="color: #aaa;">Must be at least 1 hour in the future</small>
                </div>

                <div class="form-group">
                    <label for="total_seats">Number of Seats *</label>
                    <input type="number" name="total_seats" id="total_seats" value="<?= $total_seats ?? '' ?>" required min="1" max="10000" placeholder="Available seats">
                    <small style="color: #aaa;">Between 1 and 10,000 seats</small>
                </div>
            </div>

            <div class="form-group">
                <label for="price">Ticket Price (LKR) *</label>
                <input type="number" name="price" id="price" value="<?= $price ?? '' ?>" required min="0" max="100000" step="0.01" placeholder="0.00">
                <small style="color: #aaa;">Enter 0 for free events, maximum LKR 100,000</small>
            </div>

            <div class="form-actions" style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px;">
                <a href="<?= $_SESSION['role_id'] === 1 ? '../dashboards/admin_dashboard.php' : '../dashboards/organizer_dashboard.php' ?>" class="button-backtohome">Cancel</a>
                <button type="submit" class="button-exploreevents" style="min-width: 150px;">
                    <i class="fas fa-plus"></i> Create Event
                </button>
            </div>
        </form>

        <div style="background: #2a2a2a; padding: 20px; border-radius: 10px; margin-top: 30px; border-left: 4px solid #FFD700;">
            <h4 style="margin-top: 0; color: #FFD700;">📋 Event Submission Guidelines</h4>
            <ul style="margin: 0; padding-left: 20px; color: #ccc; line-height: 1.6;">
                <li>All events require admin approval before being published</li>
                <li>Provide accurate and detailed event information</li>
                <li>Event images can be added after creation (coming soon)</li>
                <li>You can edit event details until it's approved</li>
                <li>Cancelled events will automatically refund attendees</li>
            </ul>
        </div>
    </div>
</div>

<script>
function validateEventForm() {
    const title = document.getElementById('title').value.trim();
    const description = document.getElementById('description').value.trim();
    const category = document.getElementById('category_id').value;
    const venue = document.getElementById('venue').value.trim();
    const eventDate = document.getElementById('event_date').value;
    const totalSeats = parseInt(document.getElementById('total_seats').value);
    const price = parseFloat(document.getElementById('price').value);

    if (title.length < 3) {
        showError('Event title must be at least 3 characters long.');
        return false;
    }

    if (description.length < 10) {
        showError('Event description must be at least 10 characters long.');
        return false;
    }

    if (!category) {
        showError('Please select an event category.');
        return false;
    }

    if (venue.length < 3) {
        showError('Venue must be at least 3 characters long.');
        return false;
    }

    if (!eventDate) {
        showError('Please select event date and time.');
        return false;
    }

    const selectedDate = new Date(eventDate);
    const now = new Date();
    if (selectedDate <= now) {
        showError('Event date must be in the future.');
        return false;
    }

    if (isNaN(totalSeats) || totalSeats < 1) {
        showError('Number of seats must be at least 1.');
        return false;
    }

    if (totalSeats > 10000) {
        showError('Number of seats cannot exceed 10,000.');
        return false;
    }

    if (isNaN(price) || price < 0) {
        showError('Price cannot be negative.');
        return false;
    }

    if (price > 100000) {
        showError('Price cannot exceed LKR 100,000.');
        return false;
    }

    return true;
}

function showError(message) {
    // Remove existing error messages
    const existingError = document.querySelector('.alert-error');
    if (existingError) {
        existingError.remove();
    }

    // Create new error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-error';
    errorDiv.innerHTML = '<strong>Error:</strong> ' + message;
    
    // Insert before form
    const form = document.querySelector('.beautiful-form');
    form.parentNode.insertBefore(errorDiv, form);
    
    // Scroll to error
    errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Real-time validation feedback
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.beautiful-form');
    const submitBtn = form.querySelector('button[type="submit"]');
    
    // Add character counters
    const titleInput = document.getElementById('title');
    const descInput = document.getElementById('description');
    
    titleInput.addEventListener('input', function() {
        updateCharacterCount(this, 255, 'title');
    });
    
    descInput.addEventListener('input', function() {
        updateCharacterCount(this, 2000, 'description');
    });
    
    // Form submission loading state
    form.addEventListener('submit', function() {
        if (validateEventForm()) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
            
            // Re-enable after 10 seconds as failsafe
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-plus"></i> Create Event';
            }, 10000);
        }
    });
});

function updateCharacterCount(input, maxLength, fieldName) {
    const currentLength = input.value.length;
    const remaining = maxLength - currentLength;
    
    // Find or create counter element
    let counter = input.parentNode.querySelector('.char-counter');
    if (!counter) {
        counter = document.createElement('small');
        counter.className = 'char-counter';
        counter.style.color = '#aaa';
        counter.style.fontSize = '0.8em';
        counter.style.display = 'block';
        counter.style.marginTop = '5px';
        input.parentNode.appendChild(counter);
    }
    
    counter.textContent = `${currentLength}/${maxLength} characters`;
    
    if (remaining < 50) {
        counter.style.color = '#ff6b6b';
    } else if (remaining < 100) {
        counter.style.color = '#ffc107';
    } else {
        counter.style.color = '#aaa';
    }
}
</script>

<style>
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 30px;
}

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

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
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