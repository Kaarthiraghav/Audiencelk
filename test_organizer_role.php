<?php
// test_organizer_role.php - Test organizer role functionality
include 'includes/db_connect.php';

echo "<h2>🔍 Organizer Role Diagnostic Test</h2>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
.success { color: green; background: #f0f8ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #f0f8ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background: #f2f2f2; }
</style>";

try {
    // Test 1: Check roles table
    echo "<h3>📋 Test 1: Roles Table</h3>";
    $roles_result = $connection->query("SELECT * FROM roles ORDER BY id");
    if ($roles_result) {
        echo "<div class='success'>✅ Roles table accessible</div>";
        echo "<table><tr><th>ID</th><th>Role Name</th></tr>";
        while ($role = $roles_result->fetch_assoc()) {
            echo "<tr><td>{$role['id']}</td><td>{$role['role']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ Error accessing roles table: " . $connection->error . "</div>";
    }

    // Test 2: Check if organizer role exists
    echo "<h3>🎭 Test 2: Organizer Role Check</h3>";
    $organizer_check = $connection->query("SELECT * FROM roles WHERE id = 2 OR LOWER(role) LIKE '%organizer%'");
    if ($organizer_check && $organizer_check->num_rows > 0) {
        echo "<div class='success'>✅ Organizer role found</div>";
        while ($role = $organizer_check->fetch_assoc()) {
            echo "<div class='info'>Role ID: {$role['id']}, Name: {$role['role']}</div>";
        }
    } else {
        echo "<div class='error'>❌ Organizer role not found</div>";
    }

    // Test 3: Check organizer users
    echo "<h3>👥 Test 3: Organizer Users</h3>";
    $organizer_users = $connection->query("SELECT u.id, u.username, u.email, u.role_id, r.role 
                                          FROM users u 
                                          JOIN roles r ON u.role_id = r.id 
                                          WHERE u.role_id = 2 OR LOWER(r.role) LIKE '%organizer%'");
    if ($organizer_users && $organizer_users->num_rows > 0) {
        echo "<div class='success'>✅ Found " . $organizer_users->num_rows . " organizer user(s)</div>";
        echo "<table><tr><th>ID</th><th>Username</th><th>Email</th><th>Role ID</th><th>Role Name</th></tr>";
        while ($user = $organizer_users->fetch_assoc()) {
            echo "<tr><td>{$user['id']}</td><td>{$user['username']}</td><td>{$user['email']}</td><td>{$user['role_id']}</td><td>{$user['role']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ No organizer users found</div>";
    }

    // Test 4: Check registration form role mapping
    echo "<h3>📝 Test 4: Registration Role Mapping</h3>";
    $allowedRoles = [
        2 => 'Event Organizer',
        3 => 'Attendee'
    ];
    echo "<div class='info'>Registration form allows these roles:</div>";
    echo "<table><tr><th>Role ID</th><th>Display Name</th><th>Database Status</th></tr>";
    foreach ($allowedRoles as $role_id => $display_name) {
        $role_check = $connection->query("SELECT role FROM roles WHERE id = $role_id");
        $db_status = $role_check && $role_check->num_rows > 0 ? "✅ Found: " . $role_check->fetch_assoc()['role'] : "❌ Not found";
        echo "<tr><td>$role_id</td><td>$display_name</td><td>$db_status</td></tr>";
    }
    echo "</table>";

    // Test 5: Check organizer dashboard access
    echo "<h3>🏠 Test 5: Organizer Dashboard</h3>";
    $dashboard_file = 'dashboards/organizer_dashboard.php';
    if (file_exists($dashboard_file)) {
        echo "<div class='success'>✅ Organizer dashboard file exists</div>";
        // Check file permissions
        if (is_readable($dashboard_file)) {
            echo "<div class='success'>✅ Dashboard file is readable</div>";
        } else {
            echo "<div class='error'>❌ Dashboard file is not readable</div>";
        }
    } else {
        echo "<div class='error'>❌ Organizer dashboard file missing</div>";
    }

    // Test 6: Test event creation capability
    echo "<h3>🎪 Test 6: Events Table Structure</h3>";
    $table_info = $connection->query("DESCRIBE events");
    if ($table_info) {
        echo "<div class='success'>✅ Events table accessible</div>";
        echo "<table><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($column = $table_info->fetch_assoc()) {
            echo "<tr><td>{$column['Field']}</td><td>{$column['Type']}</td><td>{$column['Null']}</td><td>{$column['Key']}</td><td>{$column['Default']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ Error accessing events table: " . $connection->error . "</div>";
    }

    // Test 7: Create test organizer account
    echo "<h3>🧪 Test 7: Create Test Organizer Account</h3>";
    $test_email = 'test_organizer@example.com';
    $test_username = 'test_organizer';
    $test_password = password_hash('TestOrg123!', PASSWORD_DEFAULT);
    
    // Check if test account already exists
    $existing_check = $connection->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $existing_check->bind_param('ss', $test_email, $test_username);
    $existing_check->execute();
    $existing_result = $existing_check->get_result();
    
    if ($existing_result->num_rows > 0) {
        echo "<div class='info'>ℹ️ Test organizer account already exists</div>";
    } else {
        $create_stmt = $connection->prepare("INSERT INTO users (username, email, password, role_id, created_at) VALUES (?, ?, ?, 2, NOW())");
        $create_stmt->bind_param('sss', $test_username, $test_email, $test_password);
        
        if ($create_stmt->execute()) {
            echo "<div class='success'>✅ Test organizer account created successfully!</div>";
            echo "<div class='info'>📧 Email: $test_email<br>🔑 Password: TestOrg123!</div>";
        } else {
            echo "<div class='error'>❌ Failed to create test account: " . $connection->error . "</div>";
        }
        $create_stmt->close();
    }
    $existing_check->close();

} catch (Exception $e) {
    echo "<div class='error'>❌ Test failed with error: " . $e->getMessage() . "</div>";
}

echo "<h3>📋 Summary</h3>";
echo "<div class='info'>
<strong>If organizer role is not working, check:</strong><br>
1. Role ID 2 should exist in roles table<br>
2. Users should be able to register with role_id = 2<br>
3. Login should redirect organizers to organizer_dashboard.php<br>
4. Dashboard should check for role_id === 2<br><br>

<strong>To register as an organizer:</strong><br>
1. Go to: <a href='auth/register.php'>auth/register.php</a><br>
2. Select 'Event Organizer' role<br>
3. Complete registration<br>
4. Login with credentials<br><br>

<strong>Test Account Created:</strong><br>
📧 Email: test_organizer@example.com<br>
🔑 Password: TestOrg123!<br>
🎭 Role: Event Organizer (ID: 2)
</div>";

$connection->close();
?>