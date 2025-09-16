
<?php
// Modern SPA Admin Dashboard with collapsible sidebar
session_start();
include '../includes/db_connect.php';

// Check if user is admin
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 1) {
    header('Location: ../auth/admin-login.php');
    exit;
}

// Session timeout check (30 minutes)
$timeout = 30 * 60; // 30 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    session_destroy();
    header('Location: ../auth/admin-login.php?timeout=1');
    exit;
}
$_SESSION['last_activity'] = time();

// Handle CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pageTitle = 'Admin Dashboard - AudienceLK';
$message = '';
$error = '';

// Get comprehensive statistics
try {
    $stats = [];
    $stats['total_users'] = $connection->query('SELECT COUNT(*) FROM users')->fetch_row()[0];
    $stats['total_events'] = $connection->query('SELECT COUNT(*) FROM events')->fetch_row()[0];
    $stats['total_bookings'] = $connection->query('SELECT COUNT(*) FROM bookings')->fetch_row()[0];
    $stats['total_revenue'] = $connection->query("SELECT SUM(amount) FROM payments WHERE status='confirmed'")->fetch_row()[0] ?? 0;
    $stats['pending_events'] = $connection->query("SELECT COUNT(*) FROM events WHERE status='pending'")->fetch_row()[0];
    $stats['active_events'] = $connection->query("SELECT COUNT(*) FROM events WHERE status='approved'")->fetch_row()[0];
    $stats['new_users_today'] = $connection->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetch_row()[0];
    $stats['bookings_today'] = $connection->query("SELECT COUNT(*) FROM bookings WHERE DATE(booking_date) = CURDATE()")->fetch_row()[0];
    
    // Additional variables for the old dashboard compatibility
    $total_users = $stats['total_users'];
    $total_events = $stats['total_events'];
    $total_bookings = $stats['total_bookings'];
    $total_revenue = $stats['total_revenue'];
    $pending_events_count = $stats['pending_events'];
    $active_events_count = $stats['active_events'];
    
    // Get additional data for tabs
    $recent_users = $connection->query("SELECT u.*, r.role FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.created_at DESC LIMIT 5");
    $popular_events = $connection->query("SELECT e.*, c.category as category, COUNT(b.id) as booking_count FROM events e LEFT JOIN event_categories c ON e.category_id = c.id LEFT JOIN bookings b ON e.id = b.event_id WHERE e.status = 'approved' GROUP BY e.id ORDER BY booking_count DESC LIMIT 5");
    $all_users = $connection->query("SELECT u.*, r.role FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.created_at DESC");
    $all_events = $connection->query("SELECT e.*, c.category as category, u.username as organizer_name FROM events e LEFT JOIN event_categories c ON e.category_id = c.id LEFT JOIN users u ON e.organizer_id = u.id ORDER BY e.created_at DESC");
    $recent_bookings = $connection->query("SELECT b.*, u.username, e.title as event_title FROM bookings b JOIN users u ON b.user_id = u.id JOIN events e ON b.event_id = e.id ORDER BY b.booking_date DESC LIMIT 10");
    $categories = $connection->query("SELECT * FROM event_categories ORDER BY category");
    $roles = $connection->query("SELECT * FROM roles WHERE id != 1 ORDER BY id"); // Exclude admin role from signup options
    
} catch (Exception $e) {
    $stats = array_fill_keys(['total_users', 'total_events', 'total_bookings', 'total_revenue', 'pending_events', 'active_events', 'new_users_today', 'bookings_today'], 0);
    $total_users = $total_events = $total_bookings = $total_revenue = $pending_events_count = $active_events_count = 0;
    $recent_users = $popular_events = $all_users = $all_events = $recent_bookings = $categories = $roles = null;
}

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid security token. Please try again.";
    } else {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'add_user':
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = trim($_POST['password'] ?? '');
                $role_id = intval($_POST['role_id'] ?? 0);
                
                if (empty($username) || empty($email) || empty($password) || $role_id === 0) {
                    $error = "All fields are required.";
                } elseif (strlen($password) < 8) {
                    $error = "Password must be at least 8 characters long.";
                } else {
                    // Check if username or email already exists
                    $check_stmt = $connection->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                    $check_stmt->bind_param("ss", $username, $email);
                    $check_stmt->execute();
                    
                    if ($check_stmt->get_result()->num_rows > 0) {
                        $error = "Username or email already exists.";
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $connection->prepare("INSERT INTO users (username, email, password, role_id) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("sssi", $username, $email, $hashed_password, $role_id);
                        
                        if ($stmt->execute()) {
                            $message = "User created successfully!";
                            // Refresh the page to update data
                            header("Location: " . $_SERVER['PHP_SELF']);
                            exit;
                        } else {
                            $error = "Error creating user: " . $connection->error;
                        }
                    }
                }
                break;
                
            case 'delete_user':
                $user_id = intval($_POST['user_id'] ?? 0);
                if ($user_id > 0 && $user_id !== $_SESSION['user_id']) {
                    $stmt = $connection->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    
                    if ($stmt->execute()) {
                        $message = "User deleted successfully!";
                        header("Location: " . $_SERVER['PHP_SELF']);
                        exit;
                    } else {
                        $error = "Error deleting user: " . $connection->error;
                    }
                }
                break;
                
            case 'approve_event':
                $event_id = intval($_POST['event_id'] ?? 0);
                if ($event_id > 0) {
                    $stmt = $connection->prepare("UPDATE events SET status = 'approved' WHERE id = ?");
                    $stmt->bind_param("i", $event_id);
                    
                    if ($stmt->execute()) {
                        $message = "Event approved successfully!";
                        header("Location: " . $_SERVER['PHP_SELF']);
                        exit;
                    } else {
                        $error = "Error approving event: " . $connection->error;
                    }
                }
                break;
                
            case 'reject_event':
                $event_id = intval($_POST['event_id'] ?? 0);
                if ($event_id > 0) {
                    $stmt = $connection->prepare("UPDATE events SET status = 'rejected' WHERE id = ?");
                    $stmt->bind_param("i", $event_id);
                    
                    if ($stmt->execute()) {
                        $message = "Event rejected successfully!";
                        header("Location: " . $_SERVER['PHP_SELF']);
                        exit;
                    } else {
                        $error = "Error rejecting event: " . $connection->error;
                    }
                }
                break;
                
            case 'add_event':
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $category = trim($_POST['category'] ?? ''); // Use category name directly since table has category field, not category_id
                $venue = trim($_POST['venue'] ?? '');
                $seats = intval($_POST['total_seats'] ?? 0);
                $price = floatval($_POST['price'] ?? 0);
                
                // Get organizer ID (current admin user)
                $organizer_id = $_SESSION['user_id'] ?? 1;
                
                if (empty($title) || empty($category) || empty($venue) || $seats <= 0) {
                    $error = "All fields are required and capacity must be greater than 0.";
                } else {
                    // Insert with the actual schema: title, category, seats, status, organizer_id, price, image, created_at
                    $stmt = $connection->prepare("INSERT INTO events (title, category, seats, status, organizer_id, price) VALUES (?, ?, ?, 'approved', ?, ?)");
                    $stmt->bind_param("ssiid", $title, $category, $seats, $organizer_id, $price);
                    
                    if ($stmt->execute()) {
                        $message = "Event added successfully!";
                        header("Location: " . $_SERVER['PHP_SELF']);
                        exit;
                    } else {
                        $error = "Error adding event: " . $connection->error;
                    }
                    $stmt->close();
                }
                break;
                
            case 'edit_event':
                $event_id = intval($_POST['event_id'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $event_date = $_POST['event_date'] ?? '';
                $venue = trim($_POST['venue'] ?? '');
                $category_id = intval($_POST['category_id'] ?? 0);
                $total_seats = intval($_POST['total_seats'] ?? 0);
                $price = floatval($_POST['price'] ?? 0);
                $status = $_POST['status'] ?? 'pending';
                
                if ($event_id === 0 || empty($title) || empty($description) || empty($event_date) || empty($venue) || $category_id === 0 || $total_seats <= 0) {
                    echo json_encode(['success' => false, 'message' => 'All fields are required and capacity must be greater than 0.']);
                    exit;
                }
                
                // Use correct column names based on actual schema
                $stmt = $connection->prepare('UPDATE events SET title = ?, description = ?, event_date = ?, venue = ?, category_id = ?, total_seats = ?, price = ?, status = ? WHERE id = ?');
                $stmt->bind_param('ssssidisi', $title, $description, $event_date, $venue, $category_id, $total_seats, $price, $status, $event_id);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Event updated successfully!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error updating event: ' . $connection->error]);
                }
                $stmt->close();
                exit;
                break;
                
            case 'delete_event':
                $event_id = intval($_POST['event_id'] ?? 0);
                
                if ($event_id === 0) {
                    $error = "Invalid event ID.";
                } else {
                    // Check if event has bookings
                    $checkStmt = $connection->prepare('SELECT COUNT(*) as count FROM bookings WHERE event_id = ?');
                    $checkStmt->bind_param('i', $event_id);
                    $checkStmt->execute();
                    $result = $checkStmt->get_result();
                    $row = $result->fetch_assoc();
                    
                    if ($row['count'] > 0) {
                        $error = "Cannot delete event with existing bookings. Please cancel all bookings first.";
                    } else {
                        $stmt = $connection->prepare('DELETE FROM events WHERE id = ?');
                        $stmt->bind_param('i', $event_id);
                        if ($stmt->execute()) {
                            $message = "Event deleted successfully!";
                            header("Location: " . $_SERVER['PHP_SELF']);
                            exit;
                        } else {
                            $error = "Error deleting event: " . $connection->error;
                        }
                        $stmt->close();
                    }
                    $checkStmt->close();
                }
                break;
                
            case 'add_category':
                $category_name = trim($_POST['category_name'] ?? '');
                
                if (empty($category_name)) {
                    $error = "Category name is required.";
                } else {
                    $stmt = $connection->prepare("INSERT INTO event_categories (name) VALUES (?)");
                    $stmt->bind_param("s", $category_name);
                    
                    if ($stmt->execute()) {
                        $message = "Category added successfully!";
                        header("Location: " . $_SERVER['PHP_SELF']);
                        exit;
                    } else {
                        $error = "Error adding category: " . $connection->error;
                    }
                }
                break;
                
            case 'edit_category':
                $category_id = intval($_POST['category_id'] ?? 0);
                $category_name = trim($_POST['category_name'] ?? '');
                
                if ($category_id === 0 || empty($category_name)) {
                    $error = "Category ID and name are required.";
                } else {
                    $stmt = $connection->prepare('UPDATE event_categories SET name = ? WHERE id = ?');
                    $stmt->bind_param('si', $category_name, $category_id);
                    if ($stmt->execute()) {
                        $message = "Category updated successfully!";
                        header("Location: " . $_SERVER['PHP_SELF']);
                        exit;
                    } else {
                        $error = "Error updating category: " . $connection->error;
                    }
                    $stmt->close();
                }
                break;
                
            case 'delete_category':
                $category_id = intval($_POST['category_id'] ?? 0);
                
                if ($category_id === 0) {
                    $error = "Invalid category ID.";
                } else {
                    // Check if category is being used by any events
                    $checkStmt = $connection->prepare('SELECT COUNT(*) as count FROM events WHERE category_id = ?');
                    $checkStmt->bind_param('i', $category_id);
                    $checkStmt->execute();
                    $result = $checkStmt->get_result();
                    $row = $result->fetch_assoc();
                    
                    if ($row['count'] > 0) {
                        $error = "Cannot delete category. It is being used by " . $row['count'] . " event(s).";
                    } else {
                        $stmt = $connection->prepare('DELETE FROM event_categories WHERE id = ?');
                        $stmt->bind_param('i', $category_id);
                        if ($stmt->execute()) {
                            $message = "Category deleted successfully!";
                            header("Location: " . $_SERVER['PHP_SELF']);
                            exit;
                        } else {
                            $error = "Error deleting category: " . $connection->error;
                        }
                        $stmt->close();
                    }
                    $checkStmt->close();
                }
                break;
                
            case 'add_booking':
                $user_id = intval($_POST['user_id'] ?? 0);
                $event_id = intval($_POST['event_id'] ?? 0);
                $seats = intval($_POST['seats'] ?? 0);
                $status = $_POST['status'] ?? 'confirmed';
                
                if ($user_id === 0 || $event_id === 0 || $seats <= 0) {
                    echo json_encode(['success' => false, 'message' => 'All fields are required and seats must be greater than 0.']);
                    exit;
                }
                
                // Generate booking number
                $booking_number = 'BK' . str_pad(mt_rand(1, 9999999), 7, '0', STR_PAD_LEFT);
                
                // Check if event has enough available seats
                $checkStmt = $connection->prepare("SELECT total_seats FROM events WHERE id = ?");
                $checkStmt->bind_param('i', $event_id);
                $checkStmt->execute();
                $result = $checkStmt->get_result();
                $event = $result->fetch_assoc();
                
                if ($event) {
                    // Get current bookings for this event
                    $bookedStmt = $connection->prepare("SELECT SUM(seats) as booked_seats FROM bookings WHERE event_id = ? AND status != 'cancelled'");
                    $bookedStmt->bind_param('i', $event_id);
                    $bookedStmt->execute();
                    $bookedResult = $bookedStmt->get_result();
                    $booked = $bookedResult->fetch_assoc();
                    $bookedSeats = $booked['booked_seats'] ?? 0;
                    
                    if (($bookedSeats + $seats) <= $event['total_seats']) {
                        $stmt = $connection->prepare("INSERT INTO bookings (user_id, event_id, booking_number, seats, status, booking_date) VALUES (?, ?, ?, ?, ?, NOW())");
                        $stmt->bind_param("iisis", $user_id, $event_id, $booking_number, $seats, $status);
                        
                        if ($stmt->execute()) {
                            echo json_encode(['success' => true, 'message' => 'Booking added successfully! Booking Number: ' . $booking_number]);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Error adding booking: ' . $connection->error]);
                        }
                        $stmt->close();
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Not enough available seats. Available: ' . ($event['total_seats'] - $bookedSeats) . ', Requested: ' . $seats]);
                    }
                    $bookedStmt->close();
                } else {
                    echo json_encode(['success' => false, 'message' => 'Event not found.']);
                }
                $checkStmt->close();
                exit;
                
                break;
                
            case 'edit_booking':
                $booking_id = intval($_POST['booking_id'] ?? 0);
                $seats = intval($_POST['seats'] ?? 0);
                $status = $_POST['status'] ?? '';
                
                if ($booking_id === 0 || $seats <= 0 || empty($status)) {
                    echo json_encode(['success' => false, 'message' => 'All fields are required and seats must be greater than 0.']);
                    exit;
                }
                
                $stmt = $connection->prepare('UPDATE bookings SET seats = ?, status = ? WHERE id = ?');
                $stmt->bind_param('isi', $seats, $status, $booking_id);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Booking updated successfully!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error updating booking: ' . $connection->error]);
                }
                $stmt->close();
                exit;
                break;
                
            case 'delete_booking':
                $booking_id = intval($_POST['booking_id'] ?? 0);
                
                if ($booking_id === 0) {
                    echo json_encode(['success' => false, 'message' => 'Invalid booking ID.']);
                    exit;
                }
                
                $stmt = $connection->prepare('DELETE FROM bookings WHERE id = ?');
                $stmt->bind_param('i', $booking_id);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Booking deleted successfully!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error deleting booking: ' . $connection->error]);
                }
                $stmt->close();
                exit;
                
                break;
        }
    }
}

// Handle API requests for dynamic content
if (isset($_GET['api']) && $_GET['api'] === 'data') {
    // Start clean output buffer and suppress any warnings/notices that might corrupt JSON output
    if (ob_get_level()) ob_end_clean();
    ob_start();
    error_reporting(E_ERROR | E_PARSE);
    ini_set('display_errors', 0);
    
    header('Content-Type: application/json');
    
    // Check database connection
    if (!isset($connection) || $connection->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    $action = $_GET['action'] ?? '';
    $response = ['success' => false, 'data' => null];
    
    switch ($action) {
        case 'stats':
            $response = ['success' => true, 'data' => $stats];
            break;
            
        case 'recent_users':
            $result = $connection->query("SELECT u.id, u.username, u.email, u.created_at, r.role 
                                        FROM users u 
                                        JOIN roles r ON u.role_id = r.id 
                                        ORDER BY u.created_at DESC LIMIT 10");
            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            $response = ['success' => true, 'data' => $users];
            break;
            
        case 'all_users':
            $result = $connection->query("SELECT u.id, u.username, u.email, u.created_at, u.role_id, r.role 
                                        FROM users u 
                                        JOIN roles r ON u.role_id = r.id 
                                        ORDER BY u.id ASC");
            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            $response = ['success' => true, 'data' => $users];
            break;
            
        case 'user_details':
            $user_id = intval($_GET['id'] ?? 0);
            if ($user_id > 0) {
                $stmt = $connection->prepare("SELECT u.*, r.role FROM users u 
                                            JOIN roles r ON u.role_id = r.id 
                                            WHERE u.id = ?");
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                if ($user) {
                    unset($user['password']); // Don't send password hash
                    $response = ['success' => true, 'data' => $user];
                } else {
                    $response = ['success' => false, 'message' => 'User not found'];
                }
                $stmt->close();
            }
            break;
            
        case 'roles':
            $result = $connection->query("SELECT id, role FROM roles ORDER BY id");
            $roles = [];
            while ($row = $result->fetch_assoc()) {
                $roles[] = $row;
            }
            $response = ['success' => true, 'data' => $roles];
            break;
            
        case 'pending_events':
            $result = $connection->query("SELECT e.*, u.username as organizer_name, c.category as category_name
                                        FROM events e 
                                        LEFT JOIN users u ON e.organizer_id = u.id 
                                        LEFT JOIN event_categories c ON e.category_id = c.id
                                        WHERE e.status = 'pending'
                                        ORDER BY e.created_at DESC");
            $events = [];
            while ($row = $result->fetch_assoc()) {
                $events[] = $row;
            }
            $response = ['success' => true, 'data' => $events];
            break;
            
        case 'all_events':
            try {
                $result = $connection->query("SELECT e.*, u.username as organizer_name, e.category as category_name
                                            FROM events e 
                                            LEFT JOIN users u ON e.organizer_id = u.id 
                                            ORDER BY e.created_at DESC");
                if (!$result) {
                    $response = ['success' => false, 'message' => 'Database error: ' . $connection->error];
                    break;
                }
                $events = [];
                while ($row = $result->fetch_assoc()) {
                    // Add missing columns with default values for compatibility
                    $row['venue'] = $row['venue'] ?? 'TBD';
                    $row['event_date'] = $row['event_date'] ?? $row['created_at'];
                    $row['total_seats'] = $row['seats'];
                    $events[] = $row;
                }
                $response = ['success' => true, 'data' => $events];
            } catch (Exception $e) {
                $response = ['success' => false, 'message' => 'Error fetching events: ' . $e->getMessage()];
            }
            break;
            
        case 'event_details':
            $event_id = intval($_GET['id'] ?? 0);
            if ($event_id > 0) {
                $stmt = $connection->prepare("SELECT e.*, u.username as organizer_name, c.category as category_name
                                            FROM events e 
                                            LEFT JOIN users u ON e.organizer_id = u.id 
                                            LEFT JOIN event_categories c ON e.category_id = c.id
                                            WHERE e.id = ?");
                $stmt->bind_param('i', $event_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $event = $result->fetch_assoc();
                if ($event) {
                    $response = ['success' => true, 'data' => $event];
                } else {
                    $response = ['success' => false, 'message' => 'Event not found'];
                }
                $stmt->close();
            }
            break;
            
        case 'categories':
            try {
                $result = $connection->query("SELECT id, name FROM event_categories ORDER BY name");
                if (!$result) {
                    $response = ['success' => false, 'message' => 'Database error: ' . $connection->error];
                    break;
                }
                $categories = [];
                while ($row = $result->fetch_assoc()) {
                    $categories[] = $row;
                }
                $response = ['success' => true, 'data' => $categories];
            } catch (Exception $e) {
                $response = ['success' => false, 'message' => 'Error fetching categories: ' . $e->getMessage()];
            }
            break;
            
        case 'recent_activity':
            // Simulate activity feed
            $activities = [
                ['type' => 'user_register', 'message' => 'New user registered', 'time' => '2 minutes ago'],
                ['type' => 'event_created', 'message' => 'New event submitted for approval', 'time' => '15 minutes ago'],
                ['type' => 'booking_made', 'message' => 'New booking completed', 'time' => '1 hour ago'],
                ['type' => 'payment_received', 'message' => 'Payment processed successfully', 'time' => '2 hours ago'],
            ];
            $response = ['success' => true, 'data' => $activities];
            break;
            
        case 'all_bookings':
            try {
                $result = $connection->query("SELECT b.*, u.username, u.email, e.title as event_title, e.price as event_price
                                            FROM bookings b 
                                            LEFT JOIN users u ON b.user_id = u.id 
                                            LEFT JOIN events e ON b.event_id = e.id
                                            ORDER BY b.booking_date DESC");
                if (!$result) {
                    $response = ['success' => false, 'message' => 'Database error: ' . $connection->error];
                    break;
                }
                $bookings = [];
                while ($row = $result->fetch_assoc()) {
                    // Calculate total amount
                    $row['total_amount'] = $row['seats'] * $row['event_price'];
                    $bookings[] = $row;
                }
                $response = ['success' => true, 'data' => $bookings];
            } catch (Exception $e) {
                $response = ['success' => false, 'message' => 'Error fetching bookings: ' . $e->getMessage()];
            }
            break;
            
        case 'booking_details':
            $booking_id = intval($_GET['id'] ?? 0);
            if ($booking_id > 0) {
                try {
                    $stmt = $connection->prepare("SELECT b.*, u.username, u.email, e.title as event_title, e.venue, e.event_date, e.price as event_price
                                                FROM bookings b 
                                                LEFT JOIN users u ON b.user_id = u.id 
                                                LEFT JOIN events e ON b.event_id = e.id
                                                WHERE b.id = ?");
                    $stmt->bind_param('i', $booking_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $booking = $result->fetch_assoc();
                    if ($booking) {
                        $booking['total_amount'] = $booking['seats'] * $booking['event_price'];
                        $response = ['success' => true, 'data' => $booking];
                    } else {
                        $response = ['success' => false, 'message' => 'Booking not found'];
                    }
                    $stmt->close();
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => 'Error fetching booking: ' . $e->getMessage()];
                }
            }
            break;
            
        case 'available_events':
            try {
                $result = $connection->query("SELECT id, title, venue, event_date, price, total_seats 
                                            FROM events 
                                            WHERE status = 'approved' AND event_date > NOW()
                                            ORDER BY event_date ASC");
                if (!$result) {
                    $response = ['success' => false, 'message' => 'Database error: ' . $connection->error];
                    break;
                }
                $events = [];
                while ($row = $result->fetch_assoc()) {
                    $events[] = $row;
                }
                $response = ['success' => true, 'data' => $events];
            } catch (Exception $e) {
                $response = ['success' => false, 'message' => 'Error fetching events: ' . $e->getMessage()];
            }
            break;
    }
    
    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="../assets/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Modern Admin SPA Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0a0a0a;
            color: #fff;
            overflow-x: hidden;
            line-height: 1.6;
        }
        
        .admin-layout {
            display: flex;
            min-height: 100vh;
            position: relative;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1a1a1a 0%, #2a2a2a 100%);
            border-right: 1px solid #333;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: fixed;
            height: 100vh;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.3);
        }
        
        .sidebar.collapsed {
            width: 70px;
        }
        
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid #333;
            background: linear-gradient(135deg, #DC143C, #FF6B6B);
            position: relative;
            overflow: hidden;
        }
        
        .sidebar-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.1);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .sidebar-header:hover::before {
            opacity: 1;
        }
        
        .admin-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .admin-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: bold;
            color: #000;
            flex-shrink: 0;
        }
        
        .admin-info {
            flex: 1;
            min-width: 0;
        }
        
        .admin-name {
            font-weight: 700;
            font-size: 1.1em;
            color: #fff;
            margin-bottom: 2px;
        }
        
        .admin-role {
            font-size: 0.8em;
            color: rgba(255, 255, 255, 0.8);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .sidebar.collapsed .admin-info {
            display: none;
        }
        
        /* Navigation Styles */
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .nav-section {
            margin-bottom: 30px;
        }
        
        .nav-section-title {
            padding: 0 20px 10px;
            font-size: 0.75em;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        
        .sidebar.collapsed .nav-section-title {
            display: none;
        }
        
        .nav-item {
            margin-bottom: 5px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #ccc;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            cursor: pointer;
        }
        
        .nav-link:hover {
            background: rgba(255, 215, 0, 0.1);
            color: #FFD700;
            transform: translateX(5px);
        }
        
        .nav-link.active {
            background: linear-gradient(90deg, rgba(255, 215, 0, 0.2), rgba(255, 215, 0, 0.1));
            color: #FFD700;
            border-right: 3px solid #FFD700;
        }
        
        .nav-icon {
            width: 20px;
            height: 20px;
            margin-right: 15px;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .nav-text {
            flex: 1;
            font-weight: 500;
        }
        
        .nav-badge {
            background: #DC143C;
            color: white;
            font-size: 0.7em;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: bold;
            min-width: 18px;
            text-align: center;
        }
        
        .sidebar.collapsed .nav-text,
        .sidebar.collapsed .nav-badge {
            display: none;
        }
        
        /* Quick Stats in Sidebar */
        .sidebar-stats {
            margin-top: auto;
            padding: 20px;
            border-top: 1px solid #333;
        }
        
        .quick-stat {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #333;
        }
        
        .quick-stat:last-child {
            border-bottom: none;
        }
        
        .stat-label {
            font-size: 0.8em;
            color: #888;
        }
        
        .stat-value {
            font-weight: bold;
            color: #FFD700;
        }
        
        .sidebar.collapsed .sidebar-stats {
            display: none;
        }
        
        /* Logout Button */
        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid #333;
        }
        
        .logout-btn {
            width: 100%;
            background: linear-gradient(135deg, #DC143C, #FF6B6B);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 20, 60, 0.3);
        }
        
        .sidebar.collapsed .logout-btn .logout-text {
            display: none;
        }
        
        /* Main Content Area */
        .main-content {
            flex: 1;
            margin-left: 280px;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #121212;
            min-height: 100vh;
        }
        
        .sidebar.collapsed + .main-content {
            margin-left: 70px;
        }
        
        /* Top Navigation */
        .topnav {
            background: linear-gradient(90deg, #1a1a1a, #2a2a2a);
            padding: 0 30px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #333;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .topnav-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .sidebar-toggle {
            background: none;
            border: none;
            color: #ccc;
            font-size: 20px;
            cursor: pointer;
            padding: 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .sidebar-toggle:hover {
            background: rgba(255, 215, 0, 0.1);
            color: #FFD700;
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #888;
            font-size: 0.9em;
        }
        
        .breadcrumb-item {
            color: #ccc;
        }
        
        .breadcrumb-item.active {
            color: #FFD700;
            font-weight: 600;
        }
        
        .topnav-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-input {
            background: #2a2a2a;
            border: 1px solid #444;
            color: #fff;
            padding: 8px 40px 8px 15px;
            border-radius: 20px;
            width: 300px;
            outline: none;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            border-color: #FFD700;
            box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.2);
        }
        
        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
        }
        
        .notifications {
            position: relative;
            cursor: pointer;
        }
        
        .notification-bell {
            font-size: 20px;
            color: #ccc;
            transition: color 0.3s ease;
        }
        
        .notification-bell:hover {
            color: #FFD700;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #DC143C;
            color: white;
            font-size: 0.7em;
            padding: 2px 5px;
            border-radius: 10px;
            min-width: 16px;
            text-align: center;
        }
        
        .profile-dropdown {
            position: relative;
        }
        
        .profile-trigger {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: background 0.3s ease;
        }
        
        .profile-trigger:hover {
            background: rgba(255, 215, 0, 0.1);
        }
        
        .profile-avatar-small {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
            color: #000;
        }
        
        /* Content Area */
        .content-area {
            padding: 30px;
            max-width: 100%;
            overflow-x: auto;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 2.5em;
            font-weight: 700;
            color: #fff;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .page-subtitle {
            color: #888;
            font-size: 1.1em;
        }
        
        /* Dashboard Specific Styles */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #1e1e1e, #2a2a2a);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid #333;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #FFD700, #FFA500);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            border-color: #FFD700;
        }
        
        .stat-card:hover::before {
            transform: scaleX(1);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .stat-title {
            color: #ccc;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #000;
        }
        
        .stat-value {
            font-size: 2.5em;
            font-weight: 700;
            color: #fff;
            margin-bottom: 10px;
        }
        
        .stat-change {
            font-size: 0.8em;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .stat-change.positive {
            color: #28a745;
        }
        
        .stat-change.negative {
            color: #dc3545;
        }
        
        /* Content Sections */
        .content-section {
            display: none;
            animation: fadeInUp 0.5s ease-out;
        }
        
        .content-section.active {
            display: block;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .section-card {
            background: linear-gradient(135deg, #1e1e1e, #2a2a2a);
            border-radius: 15px;
            border: 1px solid #333;
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .section-header {
            padding: 20px 25px;
            border-bottom: 1px solid #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-title {
            font-size: 1.3em;
            font-weight: 600;
            color: #fff;
        }
        
        .section-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-size: 0.9em;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #000;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #DC143C, #FF6B6B);
            color: #fff;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745, #34ce57);
            color: #fff;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .section-content {
            padding: 25px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar.collapsed + .main-content {
                margin-left: 0;
            }
            
            .search-input {
                width: 200px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .content-area {
                padding: 20px 15px;
            }
            
            .topnav {
                padding: 0 15px;
            }
        }
        
        /* Loading States */
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            color: #888;
        }
        
        .spinner {
            width: 30px;
            height: 30px;
            border: 3px solid #333;
            border-top: 3px solid #FFD700;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: #1e1e1e;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .data-table th {
            background: #2c2c2c;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #FFD700;
            border-bottom: 1px solid #333;
        }
        
        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #333;
            color: #ccc;
        }
        
        .data-table tr:hover {
            background: rgba(255, 215, 0, 0.05);
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        /* User Management Styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group select {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: flex-start;
        }
        
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .modal-header {
            padding: 20px 30px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #333;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-close:hover {
            color: #000;
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .role-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .role-1 { background: #dc3545; color: white; } /* Admin */
        .role-3 { background: #28a745; color: white; } /* User */
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7em;
            font-weight: 600;
        }
        
        .badge-info {
            background: #17a2b8;
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8em;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .table-footer {
            padding: 15px 0;
            border-top: 1px solid #eee;
            color: #666;
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-warning {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid #ffc107;
            color: #856404;
        }
        
        /* Event Management Styles */
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-approved { background: #28a745; color: white; }
        .status-pending { background: #ffc107; color: #000; }
        .status-rejected { background: #dc3545; color: white; }
        
        textarea {
            width: 100%;
            min-height: 100px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
        }
        
        textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="admin-profile">
                    <div class="admin-avatar">
                        <?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?>
                    </div>
                    <div class="admin-info">
                        <div class="admin-name"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></div>
                        <div class="admin-role">System Administrator</div>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <div class="nav-item">
                        <a href="#" class="nav-link active" data-section="dashboard">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <span class="nav-text">Dashboard</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="#" class="nav-link" data-section="analytics">
                            <i class="nav-icon fas fa-chart-line"></i>
                            <span class="nav-text">Analytics</span>
                        </a>
                    </div>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Management</div>
                    <div class="nav-item">
                        <a href="#" class="nav-link" data-section="users">
                            <i class="nav-icon fas fa-users"></i>
                            <span class="nav-text">Users</span>
                            <span class="nav-badge" id="users-count"><?= $stats['total_users'] ?></span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="#" class="nav-link" data-section="events">
                            <i class="nav-icon fas fa-calendar-alt"></i>
                            <span class="nav-text">Events</span>
                            <span class="nav-badge" id="pending-events"><?= $stats['pending_events'] ?></span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="#" class="nav-link" data-section="bookings">
                            <i class="nav-icon fas fa-ticket-alt"></i>
                            <span class="nav-text">Bookings</span>
                        </a>
                    </div>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Content</div>
                    <div class="nav-item">
                        <a href="#" class="nav-link" data-section="content">
                            <i class="nav-icon fas fa-edit"></i>
                            <span class="nav-text">Content</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="#" class="nav-link" data-section="categories">
                            <i class="nav-icon fas fa-tags"></i>
                            <span class="nav-text">Categories</span>
                        </a>
                    </div>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">System</div>
                    <div class="nav-item">
                        <a href="#" class="nav-link" data-section="reports">
                            <i class="nav-icon fas fa-file-alt"></i>
                            <span class="nav-text">Reports</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="#" class="nav-link" data-section="settings">
                            <i class="nav-icon fas fa-cog"></i>
                            <span class="nav-text">Settings</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="#" class="nav-link" data-section="security">
                            <i class="nav-icon fas fa-shield-alt"></i>
                            <span class="nav-text">Security</span>
                        </a>
                    </div>
                </div>
            </nav>
            
            <div class="sidebar-stats">
                <div class="quick-stat">
                    <span class="stat-label">Today's Users</span>
                    <span class="stat-value" id="today-users"><?= $stats['new_users_today'] ?></span>
                </div>
                <div class="quick-stat">
                    <span class="stat-label">Today's Bookings</span>
                    <span class="stat-value" id="today-bookings"><?= $stats['bookings_today'] ?></span>
                </div>
                <div class="quick-stat">
                    <span class="stat-label">System Status</span>
                    <span class="stat-value" style="color: #28a745;">Online</span>
                </div>
            </div>
            
            <div class="sidebar-footer">
                <button class="logout-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="logout-text">Logout</span>
                </button>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Navigation -->
            <div class="topnav">
                <div class="topnav-left">
                    <button class="sidebar-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="breadcrumb">
                        <span class="breadcrumb-item">Admin</span>
                        <i class="fas fa-chevron-right"></i>
                        <span class="breadcrumb-item active" id="current-page">Dashboard</span>
                    </div>
                </div>
                
                <div class="topnav-right">
                    <div class="search-box">
                        <input type="text" class="search-input" placeholder="Search...">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                    
                    <div class="notifications">
                        <i class="fas fa-bell notification-bell"></i>
                        <span class="notification-badge">3</span>
                    </div>
                    
                    <div class="profile-dropdown">
                        <div class="profile-trigger">
                            <div class="profile-avatar-small">
                                <?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?>
                            </div>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Content Area -->
            <div class="content-area">
                <!-- Dashboard Section -->
                <div id="dashboard-section" class="content-section active">
                    <div class="page-header">
                        <h1 class="page-title">Dashboard</h1>
                        <p class="page-subtitle">Welcome back! Here's an overview of your system.</p>
                    </div>
                    
                    <!-- Statistics Grid -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-title">Total Users</div>
                                <div class="stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                            <div class="stat-value" id="stat-users"><?= $stats['total_users'] ?></div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-title">Total Events</div>
                                <div class="stat-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                            </div>
                            <div class="stat-value" id="stat-events"><?= $stats['total_events'] ?></div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-title">Total Bookings</div>
                                <div class="stat-icon">
                                    <i class="fas fa-ticket-alt"></i>
                                </div>
                            </div>
                            <div class="stat-value" id="stat-bookings"><?= $stats['total_bookings'] ?></div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-title">Total Revenue</div>
                                <div class="stat-icon">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                            </div>
                            <div class="stat-value" id="stat-revenue">$<?= number_format($stats['total_revenue'], 0) ?></div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">Recent Activity</h3>
                            <div class="section-actions">
                                <button class="btn btn-primary" onclick="refreshActivity()">
                                    <i class="fas fa-sync"></i>
                                    Refresh
                                </button>
                            </div>
                        </div>
                        <div class="section-content">
                            <div id="recent-activity-content">
                                <div class="loading">
                                    <div class="spinner"></div>
                                    Loading recent activity...
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">Quick Actions</h3>
                        </div>
                        <div class="section-content">
                            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                <button onclick="showSection('users')" class="btn btn-primary">
                                    <i class="fas fa-users-cog"></i>
                                    Manage Users
                                </button>
                                <button onclick="showSection('events')" class="btn btn-primary">
                                    <i class="fas fa-calendar-check"></i>
                                    Manage Events
                                </button>
                                <button onclick="showSection('categories')" class="btn btn-primary">
                                    <i class="fas fa-tags"></i>
                                    Manage Categories
                                </button>
                                <button onclick="showSection('bookings')" class="btn btn-primary">
                                    <i class="fas fa-ticket-alt"></i>
                                    Manage Bookings
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Users Section -->
                <div id="users-section" class="content-section">
                    <div class="page-header">
                        <h1 class="page-title">User Management</h1>
                        <p class="page-subtitle">Manage all system users and their roles.</p>
                    </div>
                    
                    <!-- Add User Form -->
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">Add New User</h3>
                            <button id="toggle-add-user" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> Add User
                            </button>
                        </div>
                        <div id="add-user-form" class="section-content" style="display: none;">
                            <form id="add-user-form-element" method="post">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="add_user">
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="new-username">Username</label>
                                        <input type="text" id="new-username" name="username" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="new-email">Email Address</label>
                                        <input type="email" id="new-email" name="email" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="new-password">Password</label>
                                        <input type="password" id="new-password" name="password" required minlength="8">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="new-role">User Role</label>
                                        <select id="new-role" name="role_id" required>
                                            <option value="">Select Role</option>
                                            <option value="1">Admin</option>
                                            <option value="3">Regular User</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save"></i> Add User
                                    </button>
                                    <button type="button" id="cancel-add-user" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Users List -->
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">All Users</h3>
                            <div class="section-actions">
                                <button id="refresh-users" class="btn btn-outline">
                                    <i class="fas fa-sync"></i> Refresh
                                </button>
                            </div>
                        </div>
                        <div class="section-content">
                            <div id="users-content">
                                <div class="loading">
                                    <div class="spinner"></div>
                                    Loading users...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Edit User Modal -->
                <div id="edit-user-modal" class="modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Edit User</h3>
                            <button class="modal-close" onclick="closeEditUserModal()">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form id="edit-user-form" method="post">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="edit_user">
                                <input type="hidden" id="edit-user-id" name="user_id">
                                
                                <div class="form-group">
                                    <label for="edit-username">Username</label>
                                    <input type="text" id="edit-username" name="username" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="edit-email">Email Address</label>
                                    <input type="email" id="edit-email" name="email" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="edit-role">User Role</label>
                                    <select id="edit-role" name="role_id" required>
                                        <option value="">Select Role</option>
                                        <option value="1">Admin</option>
                                        <option value="3">Regular User</option>
                                    </select>
                                </div>
                                
                                <div id="edit-user-warning" style="display: none;" class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    You are editing your own account. Role changes are not allowed for security reasons.
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update User
                                    </button>
                                    <button type="button" onclick="closeEditUserModal()" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Edit Category Modal -->
                <div id="edit-category-modal" class="modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Edit Category</h3>
                            <button class="modal-close" onclick="closeEditCategoryModal()">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form id="edit-category-form" method="post">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="edit_category">
                                <input type="hidden" id="edit-category-id" name="category_id">
                                
                                <div class="form-group">
                                    <label for="edit-category-name">Category Name</label>
                                    <input type="text" id="edit-category-name" name="category_name" required>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Category
                                    </button>
                                    <button type="button" onclick="closeEditCategoryModal()" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Events Section -->
                <div id="events-section" class="content-section">
                    <div class="page-header">
                        <h1 class="page-title">Event Management</h1>
                        <p class="page-subtitle">Manage events and approve pending submissions.</p>
                    </div>
                    
                    <!-- Add Event Form -->
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">Add New Event</h3>
                            <button id="toggle-add-event" class="btn btn-primary">
                                <i class="fas fa-calendar-plus"></i> Add Event
                            </button>
                        </div>
                        <div id="add-event-form" class="section-content" style="display: none;">
                            <form id="add-event-form-element" method="post">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="add_event">
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="new-event-title">Event Title</label>
                                        <input type="text" id="new-event-title" name="title" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="new-event-category">Category</label>
                                        <input type="text" id="new-event-category" name="category" placeholder="e.g., Music, Sports, Arts" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="new-event-capacity">Total Seats</label>
                                        <input type="number" id="new-event-capacity" name="total_seats" min="1" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="new-event-price">Price ($)</label>
                                        <input type="number" id="new-event-price" name="price" min="0" step="0.01" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="new-event-venue">Venue (Optional)</label>
                                        <input type="text" id="new-event-venue" name="venue" placeholder="Event location">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="new-event-status">Status</label>
                                        <select id="new-event-status" name="status">
                                            <option value="approved">Approved</option>
                                            <option value="pending">Pending</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save"></i> Add Event
                                    </button>
                                    <button type="button" id="cancel-add-event" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Event Status Filters -->
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">All Events</h3>
                            <div class="section-actions">
                                <button id="filter-all-events" class="btn btn-outline active">All</button>
                                <button id="filter-pending-events" class="btn btn-outline">Pending</button>
                                <button id="filter-approved-events" class="btn btn-outline">Approved</button>
                                <button id="filter-rejected-events" class="btn btn-outline">Rejected</button>
                                <button id="refresh-events" class="btn btn-outline">
                                    <i class="fas fa-sync"></i> Refresh
                                </button>
                            </div>
                        </div>
                        <div class="section-content">
                            <div id="events-content">
                                <div class="loading">
                                    <div class="spinner"></div>
                                    Loading events...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Edit Event Modal -->
                <div id="edit-event-modal" class="modal" style="display: none;">
                    <div class="modal-content" style="max-width: 600px;">
                        <div class="modal-header">
                            <h3>Edit Event</h3>
                            <button class="modal-close" onclick="closeEditEventModal()">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form id="edit-event-form" method="post">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="edit_event">
                                <input type="hidden" id="edit-event-id" name="event_id">
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="edit-event-title">Event Title</label>
                                        <input type="text" id="edit-event-title" name="title" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="edit-event-category">Category</label>
                                        <select id="edit-event-category" name="category_id" required>
                                            <option value="">Select Category</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="edit-event-date">Event Date</label>
                                        <input type="datetime-local" id="edit-event-date" name="event_date" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="edit-event-location">Venue</label>
                                        <input type="text" id="edit-event-location" name="venue" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="edit-event-capacity">Total Seats</label>
                                        <input type="number" id="edit-event-capacity" name="total_seats" min="1" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="edit-event-price">Price ($)</label>
                                        <input type="number" id="edit-event-price" name="price" min="0" step="0.01" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="edit-event-status">Status</label>
                                        <select id="edit-event-status" name="status" required>
                                            <option value="pending">Pending</option>
                                            <option value="approved">Approved</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="edit-event-description">Description</label>
                                    <textarea id="edit-event-description" name="description" rows="4" required></textarea>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Event
                                    </button>
                                    <button type="button" onclick="closeEditEventModal()" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Analytics Section -->
                <div id="analytics-section" class="content-section">
                    <div class="page-header">
                        <h1 class="page-title">Analytics</h1>
                        <p class="page-subtitle">System performance and usage analytics.</p>
                    </div>
                    
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">Performance Metrics</h3>
                        </div>
                        <div class="section-content">
                            <p>Analytics dashboard coming soon...</p>
                        </div>
                    </div>
                </div>
                
                <div id="bookings-section" class="content-section">
                    <div class="page-header">
                        <h1 class="page-title">Booking Management</h1>
                        <p class="page-subtitle">View and manage all event bookings.</p>
                    </div>
                    
                    <!-- Booking Filters -->
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">All Bookings</h3>
                            <div class="section-actions">
                                <button id="filter-all-bookings" class="btn btn-outline active">All</button>
                                <button id="filter-confirmed-bookings" class="btn btn-outline">Confirmed</button>
                                <button id="filter-pending-bookings" class="btn btn-outline">Pending</button>
                                <button id="filter-cancelled-bookings" class="btn btn-outline">Cancelled</button>
                                <button id="refresh-bookings" class="btn btn-outline">
                                    <i class="fas fa-sync"></i> Refresh
                                </button>
                            </div>
                        </div>
                        <div class="section-content">
                            <div id="bookings-content">
                                <div class="loading">
                                    <div class="spinner"></div>
                                    Loading bookings...
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Add Manual Booking Form -->
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">Add Manual Booking</h3>
                            <button id="toggle-add-booking" class="btn btn-primary">
                                <i class="fas fa-ticket-alt"></i> Add Booking
                            </button>
                        </div>
                        <div id="add-booking-form" class="section-content" style="display: none;">
                            <form id="add-booking-form-element" method="post">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="add_booking">
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="booking-user">User</label>
                                        <select id="booking-user" name="user_id" required>
                                            <option value="">Select User</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="booking-event">Event</label>
                                        <select id="booking-event" name="event_id" required>
                                            <option value="">Select Event</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="booking-seats">Number of Seats</label>
                                        <input type="number" id="booking-seats" name="seats" min="1" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="booking-status">Status</label>
                                        <select id="booking-status" name="status">
                                            <option value="confirmed">Confirmed</option>
                                            <option value="pending">Pending</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save"></i> Add Booking
                                    </button>
                                    <button type="button" id="cancel-add-booking" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Edit Booking Modal -->
                <div id="edit-booking-modal" class="modal" style="display: none;">
                    <div class="modal-content" style="max-width: 500px;">
                        <div class="modal-header">
                            <h3>Edit Booking</h3>
                            <button class="modal-close" onclick="closeEditBookingModal()">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form id="edit-booking-form-element" method="post">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="edit_booking">
                                <input type="hidden" id="edit-booking-id" name="booking_id">
                                
                                <div class="form-group">
                                    <label for="edit-booking-seats">Number of Seats</label>
                                    <input type="number" id="edit-booking-seats" name="seats" min="1" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="edit-booking-status">Status</label>
                                    <select id="edit-booking-status" name="status" required>
                                        <option value="confirmed">Confirmed</option>
                                        <option value="pending">Pending</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save"></i> Update Booking
                                    </button>
                                    <button type="button" onclick="closeEditBookingModal()" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div id="content-section" class="content-section">
                    <div class="page-header">
                        <h1 class="page-title">Content Management</h1>
                        <p class="page-subtitle">Manage site content and pages.</p>
                    </div>
                    
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">Content Tools</h3>
                        </div>
                        <div class="section-content">
                            <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 20px;">
                                <button onclick="showSection('events')" class="btn btn-primary">
                                    <i class="fas fa-calendar-alt"></i>
                                    Manage Events
                                </button>
                                <button onclick="showSection('categories')" class="btn btn-primary">
                                    <i class="fas fa-tags"></i>
                                    Manage Categories
                                </button>
                                <a href="../index.php" class="btn btn-success">
                                    <i class="fas fa-eye"></i>
                                    View Public Site
                                </a>
                            </div>
                            <p>Manage all content displayed on the public site including events, categories, and promotional materials.</p>
                        </div>
                    </div>
                </div>
                
                <div id="categories-section" class="content-section">
                    <div class="page-header">
                        <h1 class="page-title">Category Management</h1>
                        <p class="page-subtitle">Manage event categories and classifications.</p>
                    </div>
                    
                    <!-- Add Category Form -->
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">Add New Category</h3>
                        </div>
                        <div class="section-content">
                            <form id="addCategoryForm" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="add_category">
                                <div style="margin-bottom: 20px;">
                                    <div class="form-group">
                                        <label for="category_name">Category Name</label>
                                        <input type="text" id="category_name" name="category_name" class="form-control" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-plus"></i>
                                    Add Category
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Categories List -->
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">All Categories</h3>
                            <div class="section-actions">
                                <button class="btn btn-secondary" onclick="loadCategories()">
                                    <i class="fas fa-refresh"></i>
                                    Refresh
                                </button>
                            </div>
                        </div>
                        <div class="section-content">
                            <div id="categoriesTable">
                                <div class="loading-state">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    Loading categories...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="reports-section" class="content-section">
                    <div class="page-header">
                        <h1 class="page-title">Reports</h1>
                        <p class="page-subtitle">Generate and view system reports.</p>
                    </div>
                    
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">Available Reports</h3>
                        </div>
                        <div class="section-content">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                                <div style="background: #2a2a2a; padding: 20px; border-radius: 8px; border: 1px solid #333;">
                                    <h4 style="color: #FFD700; margin-bottom: 10px;">User Activity Report</h4>
                                    <p style="color: #ccc; margin-bottom: 15px;">View user registration trends and activity patterns.</p>
                                    <button onclick="showSection('users')" class="btn btn-primary" style="cursor: pointer;">
                                        <i class="fas fa-chart-line"></i>
                                        View Users
                                    </button>
                                </div>
                                <div style="background: #2a2a2a; padding: 20px; border-radius: 8px; border: 1px solid #333;">
                                    <h4 style="color: #FFD700; margin-bottom: 10px;">Event Performance</h4>
                                    <p style="color: #ccc; margin-bottom: 15px;">Analyze event success rates and booking patterns.</p>
                                    <button onclick="showSection('events')" class="btn btn-primary" style="cursor: pointer;">
                                        <i class="fas fa-calendar-check"></i>
                                        View Events
                                    </button>
                                </div>
                                <div style="background: #2a2a2a; padding: 20px; border-radius: 8px; border: 1px solid #333;">
                                    <h4 style="color: #FFD700; margin-bottom: 10px;">Booking Analytics</h4>
                                    <p style="color: #ccc; margin-bottom: 15px;">Track booking trends and revenue generation.</p>
                                    <button onclick="showSection('bookings')" class="btn btn-primary">
                                        <i class="fas fa-ticket-alt"></i>
                                        View Bookings
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="settings-section" class="content-section">
                    <div class="page-header">
                        <h1 class="page-title">System Settings</h1>
                        <p class="page-subtitle">Configure system-wide settings.</p>
                    </div>
                    
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">Administration Settings</h3>
                        </div>
                        <div class="section-content">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                                <div style="background: #2a2a2a; padding: 20px; border-radius: 8px; border: 1px solid #333;">
                                    <h4 style="color: #FFD700; margin-bottom: 10px;">User Management</h4>
                                    <p style="color: #ccc; margin-bottom: 15px;">Add, edit, and manage user accounts and roles.</p>
                                    <button onclick="showSection('users')" class="btn btn-primary" style="cursor: pointer;">
                                        <i class="fas fa-users"></i>
                                        Manage Users
                                    </button>
                                </div>
                                <div style="background: #2a2a2a; padding: 20px; border-radius: 8px; border: 1px solid #333;">
                                    <h4 style="color: #FFD700; margin-bottom: 10px;">Category Settings</h4>
                                    <p style="color: #ccc; margin-bottom: 15px;">Configure event categories and classifications.</p>
                                    <a onclick="showSection('categories')" class="btn btn-primary" style="cursor: pointer;">
                                        <i class="fas fa-tags"></i>
                                        Manage Categories
                                    </a>
                                </div>
                                <div style="background: #2a2a2a; padding: 20px; border-radius: 8px; border: 1px solid #333;">
                                    <h4 style="color: #FFD700; margin-bottom: 10px;">Site Navigation</h4>
                                    <p style="color: #ccc; margin-bottom: 15px;">View and access the public website.</p>
                                    <a href="../index.php" class="btn btn-success">
                                        <i class="fas fa-external-link-alt"></i>
                                        View Site
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="security-section" class="content-section">
                    <div class="page-header">
                        <h1 class="page-title">Security</h1>
                        <p class="page-subtitle">Manage security settings and logs.</p>
                    </div>
                    
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">Security Overview</h3>
                        </div>
                        <div class="section-content">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                                <div style="background: #2a2a2a; padding: 20px; border-radius: 8px; border: 1px solid #333;">
                                    <h4 style="color: #28a745; margin-bottom: 10px;">Session Security</h4>
                                    <p style="color: #ccc; margin-bottom: 15px;">Active session timeout: 30 minutes</p>
                                    <span style="color: #28a745; font-weight: bold;">✓ Enabled</span>
                                </div>
                                <div style="background: #2a2a2a; padding: 20px; border-radius: 8px; border: 1px solid #333;">
                                    <h4 style="color: #28a745; margin-bottom: 10px;">CSRF Protection</h4>
                                    <p style="color: #ccc; margin-bottom: 15px;">Form security tokens active</p>
                                    <span style="color: #28a745; font-weight: bold;">✓ Enabled</span>
                                </div>
                                <div style="background: #2a2a2a; padding: 20px; border-radius: 8px; border: 1px solid #333;">
                                    <h4 style="color: #28a745; margin-bottom: 10px;">Database Security</h4>
                                    <p style="color: #ccc; margin-bottom: 15px;">Prepared statements in use</p>
                                    <span style="color: #28a745; font-weight: bold;">✓ Secured</span>
                                </div>
                            </div>
                            
                            <div style="margin-top: 30px; padding: 20px; background: #1e1e1e; border-radius: 8px; border: 1px solid #333;">
                                <h4 style="color: #FFD700; margin-bottom: 15px;">Quick Security Actions</h4>
                                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                    <button onclick="showSection('users')" class="btn btn-primary" style="cursor: pointer;">
                                        <i class="fas fa-user-shield"></i>
                                        Review User Accounts
                                    </button>
                                    <a href="../auth/admin-login.php" class="btn btn-success">
                                        <i class="fas fa-key"></i>
                                        Admin Login Log
                                    </a>
                                    <a href="../auth/logout.php" class="btn btn-danger">
                                        <i class="fas fa-sign-out-alt"></i>
                                        Secure Logout
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- JavaScript for SPA functionality -->
    <script>
        // Global variables
        let sidebarCollapsed = false;
        let currentSection = 'dashboard';
        
        // Initialize the SPA
        document.addEventListener('DOMContentLoaded', function() {
            initializeSPA();
            loadDashboardData();
            initializeUserManagement();
            initializeEventManagement();
            initializeCategoryManagement();
        });
        
        function initializeEventManagement() {
            // Toggle add event form
            const toggleEventButton = document.getElementById('toggle-add-event');
            const addEventForm = document.getElementById('add-event-form');
            const cancelEventButton = document.getElementById('cancel-add-event');
            
            if (toggleEventButton) {
                toggleEventButton.addEventListener('click', function() {
                    if (addEventForm.style.display === 'none') {
                        addEventForm.style.display = 'block';
                        toggleEventButton.innerHTML = '<i class="fas fa-minus"></i> Hide Form';
                    } else {
                        addEventForm.style.display = 'none';
                        toggleEventButton.innerHTML = '<i class="fas fa-calendar-plus"></i> Add Event';
                    }
                });
            }
            
            if (cancelEventButton) {
                cancelEventButton.addEventListener('click', function() {
                    addEventForm.style.display = 'none';
                    toggleEventButton.innerHTML = '<i class="fas fa-calendar-plus"></i> Add Event';
                    document.getElementById('add-event-form-element').reset();
                });
            }
            
            // Refresh events button
            const refreshEventsButton = document.getElementById('refresh-events');
            if (refreshEventsButton) {
                refreshEventsButton.addEventListener('click', function() {
                    loadAllEvents();
                });
            }
            
            // Event filter buttons
            const filterButtons = document.querySelectorAll('[id^="filter-"][id$="-events"]');
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all filter buttons
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Filter events based on button ID
                    const filter = this.id.replace('filter-', '').replace('-events', '');
                    filterEvents(filter);
                });
            });
            
            // Handle form submissions
            const addEventFormElement = document.getElementById('add-event-form-element');
            if (addEventFormElement) {
                addEventFormElement.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    
                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        location.reload();
                    })
                    .catch(error => {
                        console.error('Error adding event:', error);
                        showAlert('Error adding event', 'error');
                    });
                });
            }
            
            const editEventFormElement = document.getElementById('edit-event-form');
            if (editEventFormElement) {
                editEventFormElement.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    
                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        location.reload();
                    })
                    .catch(error => {
                        console.error('Error updating event:', error);
                        showAlert('Error updating event', 'error');
                    });
                });
            }
        }
        
        function filterEvents(filter) {
            const action = filter === 'all' ? 'all_events' : 'pending_events';
            
            fetch(`?api=data&action=${action}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let filteredEvents = data.data;
                        
                        if (filter !== 'all' && filter !== 'pending') {
                            filteredEvents = data.data.filter(event => event.status === filter);
                        }
                        
                        displayEvents(filteredEvents);
                    }
                })
                .catch(error => {
                    console.error('Error filtering events:', error);
                });
        }
        
        function initializeUserManagement() {
            // Toggle add user form
            const toggleButton = document.getElementById('toggle-add-user');
            const addUserForm = document.getElementById('add-user-form');
            const cancelButton = document.getElementById('cancel-add-user');
            
            if (toggleButton) {
                toggleButton.addEventListener('click', function() {
                    if (addUserForm.style.display === 'none') {
                        addUserForm.style.display = 'block';
                        toggleButton.innerHTML = '<i class="fas fa-minus"></i> Hide Form';
                    } else {
                        addUserForm.style.display = 'none';
                        toggleButton.innerHTML = '<i class="fas fa-user-plus"></i> Add User';
                    }
                });
            }
            
            if (cancelButton) {
                cancelButton.addEventListener('click', function() {
                    addUserForm.style.display = 'none';
                    toggleButton.innerHTML = '<i class="fas fa-user-plus"></i> Add User';
                    document.getElementById('add-user-form-element').reset();
                });
            }
            
            // Refresh users button
            const refreshButton = document.getElementById('refresh-users');
            if (refreshButton) {
                refreshButton.addEventListener('click', function() {
                    loadUsersData();
                });
            }
            
            // Handle form submissions
            const addUserFormElement = document.getElementById('add-user-form-element');
            if (addUserFormElement) {
                addUserFormElement.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    
                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        // Reload the page to see changes and show any messages
                        location.reload();
                    })
                    .catch(error => {
                        console.error('Error adding user:', error);
                        showAlert('Error adding user', 'error');
                    });
                });
            }
            
            const editUserFormElement = document.getElementById('edit-user-form');
            if (editUserFormElement) {
                editUserFormElement.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    
                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        // Reload the page to see changes
                        location.reload();
                    })
                    .catch(error => {
                        console.error('Error updating user:', error);
                        showAlert('Error updating user', 'error');
                    });
                });
            }
            
            // Modal click outside to close
            window.addEventListener('click', function(event) {
                const modal = document.getElementById('edit-user-modal');
                if (event.target === modal) {
                    closeEditUserModal();
                }
            });
        }
        
        function initializeCategoryManagement() {
            // Add Category Form Handler
            const addCategoryFormElement = document.getElementById('addCategoryForm');
            if (addCategoryFormElement) {
                addCategoryFormElement.addEventListener('submit', function(e) {
                    e.preventDefault();
                    console.log('Category form submitted');
                    
                    const formData = new FormData(this);
                    console.log('Category form data:', Object.fromEntries(formData));
                    
                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        console.log('Category submission response:', data);
                        // Reset form and reload categories
                        addCategoryFormElement.reset();
                        loadCategories();
                        showAlert('Category added successfully!', 'success');
                    })
                    .catch(error => {
                        console.error('Error adding category:', error);
                        showAlert('Error adding category', 'error');
                    });
                });
            } else {
                console.error('addCategoryForm element not found');
            }
        }
        
        function initializeSPA() {
            // Add click handlers for navigation
            document.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const section = this.getAttribute('data-section');
                    if (section) {
                        showSection(section);
                    }
                });
            });
            
            // Load initial data
            refreshActivity();
        }
        
        function showSection(sectionName) {
            console.log('showSection called with:', sectionName);
            
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Show target section
            const targetSection = document.getElementById(sectionName + '-section');
            if (targetSection) {
                console.log('Target section found:', targetSection);
                targetSection.classList.add('active');
            } else {
                console.error('Target section not found:', sectionName + '-section');
            }
            
            // Update navigation
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            
            const activeNavLink = document.querySelector(`[data-section="${sectionName}"]`);
            if (activeNavLink) {
                activeNavLink.classList.add('active');
            }
            
            // Update breadcrumb
            document.getElementById('current-page').textContent = 
                sectionName.charAt(0).toUpperCase() + sectionName.slice(1);
            
            // Load section-specific data
            console.log('About to call loadSectionData for:', sectionName);
            loadSectionData(sectionName);
            
            currentSection = sectionName;
        }
        
        function loadSectionData(section) {
            switch(section) {
                case 'dashboard':
                    loadDashboardData();
                    break;
                case 'users':
                    loadUsersData();
                    break;
                case 'events':
                    loadEventsData();
                    break;
                case 'bookings':
                    loadBookingsData();
                    break;
                case 'categories':
                    loadCategories();
                    break;
                case 'analytics':
                    loadAnalyticsData();
                    break;
            }
        }
        
        function loadDashboardData() {
            // Refresh stats
            fetch('?api=data&action=stats')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateStats(data.data);
                    }
                })
                .catch(error => console.error('Error loading stats:', error));
        }
        
        function updateStats(stats) {
            document.getElementById('stat-users').textContent = stats.total_users;
            document.getElementById('stat-events').textContent = stats.total_events;
            document.getElementById('stat-bookings').textContent = stats.total_bookings;
            document.getElementById('stat-revenue').textContent = '$' + parseInt(stats.total_revenue).toLocaleString();
            
            // Update sidebar stats
            document.getElementById('today-users').textContent = stats.new_users_today;
            document.getElementById('today-bookings').textContent = stats.bookings_today;
            document.getElementById('users-count').textContent = stats.total_users;
            document.getElementById('pending-events').textContent = stats.pending_events;
        }
        
        function refreshActivity() {
            const activityContent = document.getElementById('recent-activity-content');
            activityContent.innerHTML = '<div class="loading"><div class="spinner"></div>Loading recent activity...</div>';
            
            fetch('?api=data&action=recent_activity')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayActivity(data.data);
                    }
                })
                .catch(error => {
                    console.error('Error loading activity:', error);
                    activityContent.innerHTML = '<p style="color: #dc3545;">Error loading activity feed.</p>';
                });
        }
        
        function displayActivity(activities) {
            const activityContent = document.getElementById('recent-activity-content');
            let html = '<div style="display: flex; flex-direction: column; gap: 15px;">';
            
            activities.forEach(activity => {
                html += `
                    <div style="display: flex; align-items: center; gap: 15px; padding: 15px; background: #2a2a2a; border-radius: 8px; border-left: 3px solid #FFD700;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #FFD700, #FFA500); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-${getActivityIcon(activity.type)}" style="color: #000;"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="color: #fff; font-weight: 600;">${activity.message}</div>
                            <div style="color: #888; font-size: 0.8em;">${activity.time}</div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            activityContent.innerHTML = html;
        }
        
        function getActivityIcon(type) {
            const icons = {
                'user_register': 'user-plus',
                'event_created': 'calendar-plus',
                'booking_made': 'ticket-alt',
                'payment_received': 'dollar-sign'
            };
            return icons[type] || 'info-circle';
        }
        
        function loadUsersData() {
            const usersContent = document.getElementById('users-content');
            usersContent.innerHTML = '<div class="loading"><div class="spinner"></div>Loading users...</div>';
            
            fetch('?api=data&action=all_users')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayUsers(data.data);
                    }
                })
                .catch(error => {
                    console.error('Error loading users:', error);
                    usersContent.innerHTML = '<p style="color: #dc3545;">Error loading users.</p>';
                });
        }
        
        function displayUsers(users) {
            const usersContent = document.getElementById('users-content');
            let html = `
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            users.forEach(user => {
                const isCurrentUser = user.id === <?= $_SESSION['user_id'] ?>;
                const joinDate = new Date(user.created_at).toLocaleDateString();
                
                html += `
                    <tr>
                        <td>${user.id}</td>
                        <td>
                            <div class="user-info">
                                <strong>${user.username}</strong>
                                ${isCurrentUser ? '<span class="badge badge-info">You</span>' : ''}
                            </div>
                        </td>
                        <td>${user.email}</td>
                        <td>
                            <span class="role-badge role-${user.role_id}">
                                ${user.role}
                            </span>
                        </td>
                        <td>${joinDate}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-primary" onclick="editUser(${user.id})" title="Edit User">
                                    <i class="fas fa-edit"></i>
                                </button>
                                ${!isCurrentUser ? `
                                    <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id}, '${user.username}')" title="Delete User">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                ` : ''}
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
                
                <div class="table-footer">
                    <p>Total users: ${users.length}</p>
                </div>
            `;
            
            usersContent.innerHTML = html;
        }
        
        function editUser(userId) {
            fetch(`?api=data&action=user_details&id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const user = data.data;
                        const isCurrentUser = user.id === <?= $_SESSION['user_id'] ?>;
                        
                        document.getElementById('edit-user-id').value = user.id;
                        document.getElementById('edit-username').value = user.username;
                        document.getElementById('edit-email').value = user.email;
                        document.getElementById('edit-role').value = user.role_id;
                        
                        // Handle current user role restriction
                        const roleSelect = document.getElementById('edit-role');
                        const warning = document.getElementById('edit-user-warning');
                        
                        if (isCurrentUser) {
                            roleSelect.disabled = true;
                            warning.style.display = 'block';
                        } else {
                            roleSelect.disabled = false;
                            warning.style.display = 'none';
                        }
                        
                        document.getElementById('edit-user-modal').style.display = 'block';
                    } else {
                        showAlert('Error loading user details: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error loading user details:', error);
                    showAlert('Error loading user details', 'error');
                });
        }
        
        function closeEditUserModal() {
            document.getElementById('edit-user-modal').style.display = 'none';
        }
        
        function deleteUser(userId, username) {
            if (confirm(`Are you sure you want to delete user "${username}"?`)) {
                const formData = new FormData();
                formData.append('action', 'delete_user');
                formData.append('user_id', userId);
                formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    // Reload the page to see changes
                    location.reload();
                })
                .catch(error => {
                    console.error('Error deleting user:', error);
                    showAlert('Error deleting user', 'error');
                });
            }
        }
        
        // Category Management Functions
        function loadCategories() {
            console.log('loadCategories called');
            fetch('?api=data&action=categories')
                .then(response => {
                    console.log('Categories response received:', response.status, response.statusText);
                    return response.json();
                })
                .then(data => {
                    console.log('Categories data parsed:', data);
                    if (data.success) {
                        console.log('Categories data successful, calling renderCategoriesTable with:', data.data);
                        renderCategoriesTable(data.data);
                    } else {
                        console.error('Categories API error:', data);
                        document.getElementById('categoriesTable').innerHTML = '<p class="text-danger">Error loading categories: ' + (data.message || 'Unknown error') + '</p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading categories:', error);
                    document.getElementById('categoriesTable').innerHTML = '<p class="text-danger">Error loading categories.</p>';
                });
        }

        function renderCategoriesTable(categories) {
            let html = '';
            
            if (categories.length === 0) {
                html = '<p class="text-muted">No categories found.</p>';
            } else {
                html = `
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                categories.forEach(category => {
                    html += `
                        <tr>
                            <td>${category.id}</td>
                            <td>${category.name}</td>
                            <td>
                                <button onclick="editCategory(${category.id}, '${category.name}')" 
                                        class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button onclick="deleteCategory(${category.id}, '${category.name}')" 
                                        class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                    `;
                });
                
                html += `
                            </tbody>
                        </table>
                    </div>
                `;
            }
            
            document.getElementById('categoriesTable').innerHTML = html;
        }

        function editCategory(categoryId, name) {
            document.getElementById('edit-category-id').value = categoryId;
            document.getElementById('edit-category-name').value = name;
            document.getElementById('edit-category-modal').style.display = 'block';
        }

        function closeEditCategoryModal() {
            document.getElementById('edit-category-modal').style.display = 'none';
        }

        function deleteCategory(categoryId, name) {
            if (confirm(`Are you sure you want to delete category "${name}"?\n\nNote: Categories with associated events cannot be deleted.`)) {
                const formData = new FormData();
                formData.append('action', 'delete_category');
                formData.append('category_id', categoryId);
                formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    // Reload the page to see changes
                    location.reload();
                })
                .catch(error => {
                    console.error('Error deleting category:', error);
                    showAlert('Error deleting category', 'error');
                });
            }
        }
        
        function loadEventsData() {
            console.log('loadEventsData called');
            const eventsContent = document.getElementById('events-content');
            if (!eventsContent) {
                console.error('events-content element not found!');
                return;
            }
            
            eventsContent.innerHTML = '<div class="loading"><div class="spinner"></div>Loading events...</div>';
            console.log('Loading message set');
            
            // Load categories for dropdowns
            console.log('Fetching categories...');
            fetch('?api=data&action=categories')
                .then(response => {
                    console.log('Categories response received:', response);
                    return response.json();
                })
                .then(data => {
                    console.log('Categories data parsed:', data);
                    if (data.success) {
                        populateEventCategories(data.data);
                    } else {
                        console.error('Categories API error:', data);
                    }
                })
                .catch(error => {
                    console.error('Error loading categories:', error);
                });
            
            // Load all events
            console.log('About to call loadAllEvents');
            loadAllEvents();
        }
        
        function loadAllEvents() {
            console.log('loadAllEvents called');
            fetch('?api=data&action=all_events')
                .then(response => {
                    console.log('Events response received:', response.status, response.statusText);
                    return response.json();
                })
                .then(data => {
                    console.log('Events data parsed:', data);
                    if (data.success) {
                        console.log('Events data successful, calling displayEvents with:', data.data);
                        displayEvents(data.data);
                    } else {
                        console.error('Events API error:', data);
                        document.getElementById('events-content').innerHTML = '<p style="color: #dc3545;">Error loading events: ' + (data.message || 'Unknown error') + '</p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading events:', error);
                    document.getElementById('events-content').innerHTML = '<p style="color: #dc3545;">Error loading events.</p>';
                });
        }
        
        function populateEventCategories(categories) {
            const newCategorySelect = document.getElementById('new-event-category');
            const editCategorySelect = document.getElementById('edit-event-category');
            
            if (newCategorySelect) {
                newCategorySelect.innerHTML = '<option value="">Select Category</option>';
                categories.forEach(category => {
                    newCategorySelect.innerHTML += `<option value="${category.id}">${category.name}</option>`;
                });
            }
            
            if (editCategorySelect) {
                editCategorySelect.innerHTML = '<option value="">Select Category</option>';
                categories.forEach(category => {
                    editCategorySelect.innerHTML += `<option value="${category.id}">${category.name}</option>`;
                });
            }
        }
        
        function displayEvents(events) {
            console.log('displayEvents called with:', events);
            const eventsContent = document.getElementById('events-content');
            if (!eventsContent) {
                console.error('events-content element not found in displayEvents!');
                return;
            }
            
            let html = `
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Date</th>
                                <th>Venue</th>
                                <th>Seats</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Organizer</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            events.forEach(event => {
                console.log('Processing event:', event);
                const eventDate = event.created_at ? new Date(event.created_at).toLocaleDateString() : 'N/A';
                const statusClass = event.status === 'approved' ? 'status-approved' : 
                                   event.status === 'rejected' ? 'status-rejected' : 'status-pending';
                
                html += `
                    <tr>
                        <td>${event.id}</td>
                        <td><strong>${event.title}</strong></td>
                        <td>${event.category || 'Uncategorized'}</td>
                        <td>${eventDate}</td>
                        <td>${event.venue || 'TBD'}</td>
                        <td>${event.seats}</td>
                        <td>$${parseFloat(event.price).toFixed(2)}</td>
                        <td>
                            <span class="status-badge ${statusClass}">
                                ${event.status.charAt(0).toUpperCase() + event.status.slice(1)}
                            </span>
                        </td>
                        <td>${event.organizer_name || 'Unknown'}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-primary" onclick="editEvent(${event.id})" title="Edit Event">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteEvent(${event.id}, '${event.title}')" title="Delete Event">
                                    <i class="fas fa-trash"></i>
                                </button>
                                ${event.status === 'pending' ? `
                                    <button class="btn btn-sm btn-success" onclick="approveEvent(${event.id})" title="Approve Event">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning" onclick="rejectEvent(${event.id})" title="Reject Event">
                                        <i class="fas fa-times"></i>
                                    </button>
                                ` : ''}
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
                
                <div class="table-footer">
                    <p>Total events: ${events.length}</p>
                </div>
            `;
            
            eventsContent.innerHTML = html;
        }
        
        function editEvent(eventId) {
            fetch(`?api=data&action=event_details&id=${eventId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const event = data.data;
                        
                        document.getElementById('edit-event-id').value = event.id;
                        document.getElementById('edit-event-title').value = event.title;
                        document.getElementById('edit-event-description').value = event.description;
                        document.getElementById('edit-event-category').value = event.category_id;
                        
                        // Format datetime for input
                        const eventDate = new Date(event.event_date);
                        const formattedDate = eventDate.toISOString().slice(0, 16);
                        document.getElementById('edit-event-date').value = formattedDate;
                        
                        document.getElementById('edit-event-location').value = event.venue;
                        document.getElementById('edit-event-capacity').value = event.total_seats;
                        document.getElementById('edit-event-price').value = event.price;
                        document.getElementById('edit-event-status').value = event.status;
                        
                        // Load categories and select the current one
                        loadEventCategories(event.category_id);
                        
                        document.getElementById('edit-event-modal').style.display = 'block';
                    } else {
                        showAlert('Error loading event details: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error loading event details:', error);
                    showAlert('Error loading event details', 'error');
                });
        }
        
        // Load event categories for dropdown
        function loadEventCategories(selectedCategoryId = null) {
            fetch('?api=data&action=categories')
                .then(response => response.json())
                .then(data => {
                    const categorySelect = document.getElementById('edit-event-category');
                    categorySelect.innerHTML = '<option value="">Select Category</option>';
                    
                    if (data.success && data.data) {
                        data.data.forEach(category => {
                            const option = document.createElement('option');
                            option.value = category.id;
                            option.textContent = category.name;
                            if (selectedCategoryId && category.id == selectedCategoryId) {
                                option.selected = true;
                            }
                            categorySelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading categories:', error);
                });
        }
        
        function closeEditEventModal() {
            document.getElementById('edit-event-modal').style.display = 'none';
            document.getElementById('edit-event-form').reset();
        }
        
        function deleteEvent(eventId, eventTitle) {
            if (confirm(`Are you sure you want to delete event "${eventTitle}"?`)) {
                const formData = new FormData();
                formData.append('action', 'delete_event');
                formData.append('event_id', eventId);
                formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    location.reload();
                })
                .catch(error => {
                    console.error('Error deleting event:', error);
                    showAlert('Error deleting event', 'error');
                });
            }
        }
        
        function approveEvent(eventId) {
            updateEventStatus(eventId, 'approved');
        }
        
        function rejectEvent(eventId) {
            updateEventStatus(eventId, 'rejected');
        }
        
        function updateEventStatus(eventId, status) {
            const formData = new FormData();
            formData.append('action', status === 'approved' ? 'approve_event' : 'reject_event');
            formData.append('event_id', eventId);
            formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                location.reload();
            })
            .catch(error => {
                console.error('Error updating event status:', error);
                showAlert('Error updating event status', 'error');
            });
        }
        
        function refreshPendingEvents() {
            const eventsContent = document.getElementById('pending-events-content');
            eventsContent.innerHTML = '<div class="loading"><div class="spinner"></div>Loading pending events...</div>';
            
            fetch('?api=data&action=pending_events')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayPendingEvents(data.data);
                    }
                })
                .catch(error => {
                    console.error('Error loading events:', error);
                    eventsContent.innerHTML = '<p style="color: #dc3545;">Error loading events.</p>';
                });
        }
        
        function displayPendingEvents(events) {
            const eventsContent = document.getElementById('pending-events-content');
            
            if (events.length === 0) {
                eventsContent.innerHTML = '<p style="color: #888; text-align: center; padding: 40px;">No pending events found.</p>';
                return;
            }
            
            let html = `
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Event Title</th>
                            <th>Organizer</th>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            events.forEach(event => {
                html += `
                    <tr>
                        <td>${event.title}</td>
                        <td>${event.organizer_name || 'Unknown'}</td>
                        <td>${new Date(event.event_date).toLocaleDateString()}</td>
                        <td>${event.location}</td>
                        <td>
                            <button class="btn btn-success" style="padding: 4px 8px; font-size: 0.8em;">Approve</button>
                            <button class="btn btn-danger" style="padding: 4px 8px; font-size: 0.8em;">Reject</button>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            eventsContent.innerHTML = html;
        }
        
        function loadAnalyticsData() {
            // Placeholder for analytics
            console.log('Loading analytics data...');
        }
        
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
            sidebarCollapsed = !sidebarCollapsed;
        }
        
        // Booking Management Functions
        // Booking management section initialization
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle add booking form
            const toggleAddBookingBtn = document.getElementById('toggle-add-booking');
            const addBookingForm = document.getElementById('add-booking-form');
            const cancelAddBookingBtn = document.getElementById('cancel-add-booking');
            
            if (toggleAddBookingBtn && addBookingForm) {
                toggleAddBookingBtn.addEventListener('click', function() {
                    if (addBookingForm.style.display === 'none' || addBookingForm.style.display === '') {
                        addBookingForm.style.display = 'block';
                        toggleAddBookingBtn.innerHTML = '<i class="fas fa-minus"></i> Hide Form';
                    } else {
                        addBookingForm.style.display = 'none';
                        toggleAddBookingBtn.innerHTML = '<i class="fas fa-ticket-alt"></i> Add Booking';
                    }
                });
            }
            
            if (cancelAddBookingBtn) {
                cancelAddBookingBtn.addEventListener('click', function() {
                    addBookingForm.style.display = 'none';
                    toggleAddBookingBtn.innerHTML = '<i class="fas fa-ticket-alt"></i> Add Booking';
                    document.getElementById('add-booking-form-element').reset();
                });
            }
            
            // Booking filter buttons
            const filterButtons = {
                'filter-all-bookings': () => loadAllBookings(),
                'filter-confirmed-bookings': () => loadFilteredBookings('confirmed'),
                'filter-pending-bookings': () => loadFilteredBookings('pending'),
                'filter-cancelled-bookings': () => loadFilteredBookings('cancelled'),
                'refresh-bookings': () => loadAllBookings()
            };
            
            Object.entries(filterButtons).forEach(([id, handler]) => {
                const btn = document.getElementById(id);
                if (btn) {
                    btn.addEventListener('click', function() {
                        // Remove active class from all filter buttons
                        document.querySelectorAll('.section-actions .btn').forEach(b => b.classList.remove('active'));
                        // Add active class to clicked button
                        this.classList.add('active');
                        // Execute filter
                        handler();
                    });
                }
            });
            
            // Add booking form submission
            const addBookingFormElement = document.getElementById('add-booking-form-element');
            if (addBookingFormElement) {
                addBookingFormElement.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    
                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showAlert(data.message, 'success');
                            this.reset();
                            addBookingForm.style.display = 'none';
                            toggleAddBookingBtn.innerHTML = '<i class="fas fa-ticket-alt"></i> Add Booking';
                            loadAllBookings();
                        } else {
                            showAlert(data.message || 'Failed to add booking', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('An error occurred while adding the booking', 'error');
                    });
                });
            }
            
            // Edit booking form submission
            const editBookingFormElement = document.getElementById('edit-booking-form-element');
            if (editBookingFormElement) {
                editBookingFormElement.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    
                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showAlert(data.message, 'success');
                            closeEditBookingModal();
                            loadAllBookings();
                        } else {
                            showAlert(data.message || 'Failed to update booking', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('An error occurred while updating the booking', 'error');
                    });
                });
            }
        });

        // Load filtered bookings
        function loadFilteredBookings(status) {
            console.log('Loading filtered bookings for status:', status);
            const bookingsContent = document.getElementById('bookings-content');
            if (!bookingsContent) return;
            
            bookingsContent.innerHTML = '<div class="loading"><div class="spinner"></div>Loading bookings...</div>';
            
            fetch(`?api=data&action=all_bookings&status=${status}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Filter bookings by status
                        const filteredBookings = data.data.filter(booking => booking.status === status);
                        displayBookings(filteredBookings);
                    } else {
                        document.getElementById('bookings-content').innerHTML = '<p style="color: #dc3545;">Error loading bookings: ' + (data.message || 'Unknown error') + '</p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading filtered bookings:', error);
                    document.getElementById('bookings-content').innerHTML = '<p style="color: #dc3545;">Error loading bookings.</p>';
                });
        }

        function loadBookingsData() {
            console.log('loadBookingsData called');
            const bookingsContent = document.getElementById('bookings-content');
            if (!bookingsContent) {
                console.error('bookings-content element not found!');
                return;
            }
            
            bookingsContent.innerHTML = '<div class="loading"><div class="spinner"></div>Loading bookings...</div>';
            
            // Load users and events for dropdowns
            loadUsersForBooking();
            loadEventsForBooking();
            
            // Load all bookings
            loadAllBookings();
        }
        
        function loadAllBookings() {
            console.log('loadAllBookings called');
            fetch('?api=data&action=all_bookings')
                .then(response => {
                    console.log('Bookings response received:', response.status, response.statusText);
                    return response.json();
                })
                .then(data => {
                    console.log('Bookings data parsed:', data);
                    if (data.success) {
                        console.log('Bookings data successful, calling displayBookings with:', data.data);
                        displayBookings(data.data);
                    } else {
                        console.error('Bookings API error:', data);
                        document.getElementById('bookings-content').innerHTML = '<p style="color: #dc3545;">Error loading bookings: ' + (data.message || 'Unknown error') + '</p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading bookings:', error);
                    document.getElementById('bookings-content').innerHTML = '<p style="color: #dc3545;">Error loading bookings.</p>';
                });
        }
        
        function displayBookings(bookings) {
            console.log('displayBookings called with:', bookings);
            const bookingsContent = document.getElementById('bookings-content');
            if (!bookingsContent) {
                console.error('bookings-content element not found in displayBookings!');
                return;
            }
            
            let html = `
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Booking Number</th>
                                <th>User</th>
                                <th>Event</th>
                                <th>Seats</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Booking Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            if (bookings.length === 0) {
                html += '<tr><td colspan="9" style="text-align: center; padding: 40px;">No bookings found</td></tr>';
            } else {
                bookings.forEach(booking => {
                    console.log('Processing booking:', booking);
                    const bookingDate = new Date(booking.booking_date).toLocaleDateString();
                    const statusClass = booking.status === 'confirmed' ? 'status-approved' : 
                                       booking.status === 'cancelled' ? 'status-rejected' : 'status-pending';
                    
                    html += `
                        <tr>
                            <td>${booking.id}</td>
                            <td><strong>${booking.booking_number}</strong></td>
                            <td>${booking.username || 'Unknown'}</td>
                            <td>${booking.event_title || 'Unknown Event'}</td>
                            <td>${booking.seats}</td>
                            <td>$${parseFloat(booking.total_amount || 0).toFixed(2)}</td>
                            <td>
                                <span class="status-badge ${statusClass}">
                                    ${booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}
                                </span>
                            </td>
                            <td>${bookingDate}</td>
                            <td>
                                <button onclick="editBooking(${booking.id})" class="btn btn-sm btn-outline" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteBooking(${booking.id})" class="btn btn-sm btn-danger" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
            }
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            bookingsContent.innerHTML = html;
        }
        
        function loadUsersForBooking() {
            fetch('?api=data&action=all_users')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const userSelect = document.getElementById('booking-user');
                        if (userSelect) {
                            userSelect.innerHTML = '<option value="">Select User</option>';
                            data.data.forEach(user => {
                                userSelect.innerHTML += `<option value="${user.id}">${user.username} (${user.email})</option>`;
                            });
                        }
                    }
                })
                .catch(error => console.error('Error loading users for booking:', error));
        }
        
        function loadEventsForBooking() {
            fetch('?api=data&action=available_events')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const eventSelect = document.getElementById('booking-event');
                        if (eventSelect) {
                            eventSelect.innerHTML = '<option value="">Select Event</option>';
                            data.data.forEach(event => {
                                const eventDate = new Date(event.event_date).toLocaleDateString();
                                eventSelect.innerHTML += `<option value="${event.id}">${event.title} - ${event.venue} (${eventDate})</option>`;
                            });
                        }
                    }
                })
                .catch(error => console.error('Error loading events for booking:', error));
        }
        
        function editBooking(bookingId) {
            fetch(`?api=data&action=booking_details&id=${bookingId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const booking = data.data;
                        document.getElementById('edit-booking-id').value = booking.id;
                        document.getElementById('edit-booking-seats').value = booking.seats;
                        document.getElementById('edit-booking-status').value = booking.status;
                        
                        document.getElementById('edit-booking-modal').style.display = 'block';
                    } else {
                        alert('Error loading booking details: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error loading booking details:', error);
                    alert('Error loading booking details.');
                });
        }
        
        function closeEditBookingModal() {
            document.getElementById('edit-booking-modal').style.display = 'none';
        }
        
        function deleteBooking(bookingId) {
            if (confirm('Are you sure you want to delete this booking?')) {
                const formData = new FormData();
                formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');
                formData.append('action', 'delete_booking');
                formData.append('booking_id', bookingId);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');
                        loadAllBookings();
                    } else {
                        showAlert(data.message || 'Failed to delete booking', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error deleting booking:', error);
                    showAlert('Error deleting booking', 'error');
                });
            }
        }

        // Handle edit event form submission
        document.addEventListener('DOMContentLoaded', function() {
            const editEventForm = document.getElementById('edit-event-form');
            if (editEventForm) {
                editEventForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    
                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showAlert('Event updated successfully', 'success');
                            closeEditEventModal();
                            loadEventsData();
                        } else {
                            showAlert(data.message || 'Failed to update event', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('An error occurred while updating the event', 'error');
                    });
                });
            }
        });

        function deleteEvent(eventId) {
            if (confirm('Are you sure you want to delete this event?')) {
                const formData = new FormData();
                formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');
                formData.append('action', 'delete_event');
                formData.append('event_id', eventId);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    // Reload the page to see changes
                    location.reload();
                })
                .catch(error => {
                    console.error('Error deleting event:', error);
                    showAlert('Error deleting event', 'error');
                });
            }
        }
        
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../auth/logout.php';
            }
        }
        
        // Auto-refresh data every 30 seconds
        setInterval(() => {
            if (currentSection === 'dashboard') {
                loadDashboardData();
            }
        }, 30000);
    </script>
</body>
</html>
