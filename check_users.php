<?php
// check_users.php - Check current users and their roles
require_once 'config/database.php';

try {
    $conn = getConnection();

    echo "Current Users in Database:\n";
    echo str_repeat("=", 60) . "\n";

    $result = $conn->query('SELECT id, username, email, role, department, is_active FROM users LIMIT 10');

    if ($result->num_rows === 0) {
        echo "No users found in database.\n";
        echo "You may need to run the import script or create users manually.\n";
    } else {
        while($row = $result->fetch_assoc()) {
            printf("ID: %-2d | Username: %-15s | Role: %-12s | Active: %s\n",
                $row['id'],
                $row['username'],
                $row['role'],
                $row['is_active'] ? 'Yes' : 'No'
            );
        }
    }

    echo "\nAvailable roles: admin, manager, user, stock_manager, stock_controller, tech_lead, technician, driver\n";
    echo "Only 'admin' and 'manager' roles can access User Management.\n";

    $conn->close();
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>
