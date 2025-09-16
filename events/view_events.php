<?php
// Public: View all events, book if logged in
session_start();

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$pageTitle = 'View Events';
include '../includes/header.php';
include '../includes/db_connect.php';

// Fetch approved events using prepared statement
try {
    $sql = "SELECT e.*, c.category AS category_name FROM events e LEFT JOIN event_categories c ON e.category_id = c.id WHERE e.status = ? ORDER BY e.created_at DESC";
    $stmt = $connection->prepare($sql);
    $status = 'approved';
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $result = $stmt->get_result();
} catch (Exception $e) {
    $result = null;
    $error = "Error loading events. Please try again later.";
}
?>

<div class="HomeCards1">
<div class="card" style="width: 100%; max-width: 1200px;">
    <h2 style="text-align: center; margin-bottom: 30px;">All Events</h2>
    
    <?php if (isset($error)): ?>
        <div class="message error" style="margin-bottom: 20px; text-align: center;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($result && $result->num_rows > 0): ?>
        <div class="events-container" style="overflow-x: auto;">
            <table style="width: 100%; min-width: 600px; border-collapse: separate; border-spacing: 0; background: #1a1a1a; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
                <thead>
                    <tr style="background: #2c2c2c;">
                        <th style="padding: 15px; text-align: left; color: #FFD700; font-weight: bold; border-bottom: 2px solid #FFD700;">Title</th>
                        <th style="padding: 15px; text-align: center; color: #FFD700; font-weight: bold; border-bottom: 2px solid #FFD700;">Category</th>
                        <th style="padding: 15px; text-align: center; color: #FFD700; font-weight: bold; border-bottom: 2px solid #FFD700;">Available Seats</th>
                        <th style="padding: 15px; text-align: center; color: #FFD700; font-weight: bold; border-bottom: 2px solid #FFD700;">Price</th>
                        <th style="padding: 15px; text-align: center; color: #FFD700; font-weight: bold; border-bottom: 2px solid #FFD700;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid #333; transition: background-color 0.3s;" onmouseover="this.style.backgroundColor='#252525'" onmouseout="this.style.backgroundColor=''">
                        <td style="padding: 15px; color: #FFD700;"><?= htmlspecialchars($row['title']) ?></td>
                        <td style="padding: 15px; text-align: center; color: #ddd;"><?= htmlspecialchars($row['category_name'] ?? 'Uncategorized') ?></td>
                        <td style="padding: 15px; text-align: center; color: #ddd; font-weight: bold;">
                            <?= $row['seats'] > 0 ? $row['seats'] : '<span style="color: #ff6b6b;">0</span>' ?>
                        </td>
                        <td style="padding: 15px; text-align: center; color: #ddd; font-weight: bold;">
                            $<?= number_format($row['price'], 2) ?>
                        </td>
                        <td style="padding: 15px; text-align: center;">
                            <?php if ($row['seats'] < 1): ?>
                                <span style="color: #ff6b6b; font-weight: bold; padding: 8px 16px; background: rgba(255, 107, 107, 0.1); border-radius: 20px; border: 1px solid #ff6b6b;">Sold Out</span>
                            <?php elseif (isset($_SESSION['user_id']) && $_SESSION['role_id'] === 3): ?>
                                <form action="../bookings/add_booking.php" method="post" style="display: inline;">
                                    <input type="hidden" name="event_id" value="<?= $row['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                    <button type="submit" class="button-exploreevents" style="padding: 8px 16px; font-size: 14px; min-width: auto;">Book Now</button>
                                </form>
                            <?php elseif (isset($_SESSION['user_id'])): ?>
                                <button disabled style="padding: 8px 16px; background: #666; color: #999; border: none; border-radius: 20px; cursor: not-allowed;">Not Available</button>
                            <?php else: ?>
                                <a href="../auth/login.php" class="button-exploreevents" style="padding: 8px 16px; font-size: 14px; min-width: auto; text-decoration: none;">Login to Book</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <p style="color: #aaa; font-size: 14px;">
                <?= $result->num_rows ?> event(s) available
                <?php if (isset($_SESSION['user_id']) && $_SESSION['role_id'] === 3): ?>
                    | <a href="../bookings/manage_bookings.php" style="color: #FFD700;">View My Bookings</a>
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 60px 20px;">
            <h3 style="color: #aaa; margin-bottom: 20px;">No events available at the moment.</h3>
            <p style="color: #888; margin-bottom: 30px;">Check back later for upcoming events!</p>
            <?php if (isset($_SESSION['user_id']) && ($_SESSION['role_id'] === 1 || $_SESSION['role_id'] === 2)): ?>
                <a href="../events/add_event.php" class="button-exploreevents">Add New Event</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div style="text-align: center; margin-top: 30px;">
        <a href="<?= BASE_URL ?>index.php" class="button-backtohome">Back to Home</a>
    </div>
</div>
</div>

<?php include '../includes/footer.php'; ?>
