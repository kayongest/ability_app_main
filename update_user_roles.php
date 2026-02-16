<?php
// update_user_roles.php - Update users table to support expanded roles
require_once 'config/database.php';

try {
    $conn = getConnection();

    // Alter the users table to support expanded roles
    $sql = "ALTER TABLE users MODIFY COLUMN role ENUM('admin','manager','user','stock_manager','stock_controller','tech_lead','technician','driver') DEFAULT 'user'";

    if ($conn->query($sql) === TRUE) {
        echo "✅ Users table updated successfully with expanded roles\n";
        echo "Available roles: admin, manager, user, stock_manager, stock_controller, tech_lead, technician, driver\n";
    } else {
        echo "❌ Error updating users table: " . $conn->error . "\n";
    }

    $conn->close();
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>
