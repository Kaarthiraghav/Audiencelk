<?php
// Organizer Dashboard: Add/view events, view bookings for own events
session_start();
$pageTitle = 'Organizer Dashboard';
include '../includes/header.php';
include '../includes/db_connect.php';

// Check if user is logged in and has organizer role
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 2) {
    header('Location: ../auth/login.php');
    exit;
}

$organizer_id = $_SESSION['user_id'];

// Fetch events by organizer - using correct column names
$events_query = "SELECT e.*, 
                        COALESCE(ec.name, e.category) as category_name,
                        e.total_seats,
                        e.price,
                        e.status
                 FROM events e 
                 LEFT JOIN event_categories ec ON e.category_id = ec.id 
                 WHERE e.organizer_id = ?
                 ORDER BY e.event_date DESC";

$stmt = $connection->prepare($events_query);
$stmt->bind_param('i', $organizer_id);
$stmt->execute();
$events = $stmt->get_result();
?>

<style>
.organizer-dashboard {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.dashboard-header {
    background: linear-gradient(135deg, #1a1a1a, #2a2a2a);
    color: #FFD700;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    text-align: center;
}

.dashboard-header h1 {
    margin: 0;
    font-size: 2.5em;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
}

.dashboard-header p {
    margin: 10px 0 0 0;
    font-size: 1.2em;
    color: #ccc;
}

.action-bar {
    background: #2a2a2a;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.btn-add-event {
    background: linear-gradient(135deg, #FFD700, #FFA500);
    color: #000;
    padding: 12px 25px;
    border: none;
    border-radius: 25px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-add-event:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
}

.events-table {
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.events-table table {
    width: 100%;
    border-collapse: collapse;
}

.events-table th {
    background: #f8f9fa;
    padding: 15px;
    text-align: left;
    font-weight: 600;
    color: #333;
    border-bottom: 2px solid #dee2e6;
}

.events-table td {
    padding: 15px;
    border-bottom: 1px solid #dee2e6;
    vertical-align: middle;
}

.events-table tr:hover {
    background: #f8f9fa;
}

.status-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: bold;
    text-transform: uppercase;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-approved { background: #d4edda; color: #155724; }
.status-rejected { background: #f8d7da; color: #721c24; }

.btn-view-bookings {
    background: #007bff;
    color: white;
    padding: 6px 15px;
    border: none;
    border-radius: 15px;
    text-decoration: none;
    font-size: 0.9em;
    transition: all 0.3s ease;
}

.btn-view-bookings:hover {
    background: #0056b3;
    color: white;
}

.bookings-section {
    background: #fff;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    margin-top: 20px;
}

.no-events {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.no-events i {
    font-size: 4em;
    color: #ddd;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .organizer-dashboard {
        padding: 10px;
    }
    
    .action-bar {
        flex-direction: column;
        text-align: center;
    }
    
    .events-table {
        overflow-x: auto;
    }
    
    .events-table table {
        min-width: 800px;
    }
}
</style>

<div class="organizer-dashboard">
    <div class="dashboard-header">
        <h1>🎭 Organizer Dashboard</h1>
        <p>Welcome back, <?= htmlspecialchars($_SESSION['username']) ?>! Manage your events and track bookings.</p>
    </div>

    <div class="action-bar">
        <h2 style="margin: 0; color: #FFD700;">Your Events</h2>
        <a href="../events/add_event.php" class="btn-add-event">
            <span>➕</span>
            Add New Event
        </a>
    </div>

    <?php if ($events->num_rows > 0): ?>
        <div class="events-table">
            <table>
                <thead>
                    <tr>
                        <th>Event Title</th>
                        <th>Category</th>
                        <th>Venue</th>
                        <th>Date & Time</th>
                        <th>Seats</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($event = $events->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($event['title']) ?></strong>
                            <?php if (!empty($event['description'])): ?>
                                <br><small style="color: #666;"><?= htmlspecialchars(substr($event['description'], 0, 100)) ?>...</small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($event['category_name'] ?? 'Uncategorized') ?></td>
                        <td><?= htmlspecialchars($event['venue'] ?? 'TBD') ?></td>
                        <td>
                            <?php if ($event['event_date']): ?>
                                <?= date('M d, Y', strtotime($event['event_date'])) ?><br>
                                <small style="color: #666;"><?= date('h:i A', strtotime($event['event_date'])) ?></small>
                            <?php else: ?>
                                <span style="color: #999;">Not set</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $event['total_seats'] ?? $event['seats'] ?? 0 ?></td>
                        <td>LKR <?= number_format($event['price'] ?? 0, 2) ?></td>
                        <td>
                            <span class="status-badge status-<?= htmlspecialchars($event['status']) ?>">
                                <?= htmlspecialchars(ucfirst($event['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <a href="?bookings=<?= $event['id'] ?>" class="btn-view-bookings">
                                📊 View Bookings
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="no-events">
            <i>🎪</i>
            <h3>No Events Yet</h3>
            <p>You haven't created any events yet. Start by adding your first event!</p>
            <a href="../events/add_event.php" class="btn-add-event">
                <span>➕</span>
                Create Your First Event
            </a>
        </div>
    <?php endif; ?>

    <?php
    // Show bookings for selected event
    if (isset($_GET['bookings'])) {
        $event_id = intval($_GET['bookings']);
        
        // Verify the event belongs to this organizer
        $event_check = $connection->prepare("SELECT title FROM events WHERE id = ? AND organizer_id = ?");
        $event_check->bind_param('ii', $event_id, $organizer_id);
        $event_check->execute();
        $event_result = $event_check->get_result();
        
        if ($event_result->num_rows > 0) {
            $event_info = $event_result->fetch_assoc();
            
            // Get bookings for this event
            $bookings_query = "SELECT b.*, u.username, u.email 
                              FROM bookings b 
                              JOIN users u ON b.user_id = u.id 
                              WHERE b.event_id = ? 
                              ORDER BY b.created_at DESC";
            
            $bookings_stmt = $connection->prepare($bookings_query);
            $bookings_stmt->bind_param('i', $event_id);
            $bookings_stmt->execute();
            $bookings = $bookings_stmt->get_result();
            
            echo '<div class="bookings-section">';
            echo '<h3>📊 Bookings for: ' . htmlspecialchars($event_info['title']) . '</h3>';
            
            if ($bookings->num_rows > 0) {
                echo '<table style="width: 100%; border-collapse: collapse; margin-top: 20px;">';
                echo '<tr style="background: #f8f9fa;">';
                echo '<th style="padding: 12px; border: 1px solid #dee2e6;">Customer</th>';
                echo '<th style="padding: 12px; border: 1px solid #dee2e6;">Email</th>';
                echo '<th style="padding: 12px; border: 1px solid #dee2e6;">Booking Date</th>';
                echo '<th style="padding: 12px; border: 1px solid #dee2e6;">Status</th>';
                echo '</tr>';
                
                while ($booking = $bookings->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td style="padding: 12px; border: 1px solid #dee2e6;">' . htmlspecialchars($booking['username']) . '</td>';
                    echo '<td style="padding: 12px; border: 1px solid #dee2e6;">' . htmlspecialchars($booking['email']) . '</td>';
                    echo '<td style="padding: 12px; border: 1px solid #dee2e6;">' . date('M d, Y h:i A', strtotime($booking['created_at'])) . '</td>';
                    echo '<td style="padding: 12px; border: 1px solid #dee2e6;">';
                    echo '<span class="status-badge status-' . htmlspecialchars($booking['status']) . '">' . htmlspecialchars(ucfirst($booking['status'])) . '</span>';
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<p style="text-align: center; color: #666; padding: 40px;">No bookings yet for this event.</p>';
            }
            
            echo '<div style="margin-top: 20px; text-align: center;">';
            echo '<a href="organizer_dashboard.php" style="background: #6c757d; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;">← Back to Events</a>';
            echo '</div>';
            echo '</div>';
            
            $bookings_stmt->close();
        } else {
            echo '<div class="bookings-section">';
            echo '<p style="color: red; text-align: center;">Event not found or access denied.</p>';
            echo '</div>';
        }
        $event_check->close();
    }
    ?>
</div>

<?php include '../includes/footer.php'; ?>
