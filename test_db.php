<?php
// test_db.php - Test database connection
echo "<h3>Testing db_connect.php</h3>";

// Read the file
$db_connect_content = file_get_contents('includes/db_connect.php');
echo "<pre>" . htmlspecialchars(substr($db_connect_content, 0, 500)) . "</pre>";

// Try to include it
echo "<h3>Including db_connect.php</h3>";
require_once 'includes/db_connect.php';

// Check what variables are defined
echo "<h3>Checking for connection variable</h3>";
if (isset($conn)) {
    echo "✓ \$conn variable exists<br>";
    
    // Test the connection
    try {
        $stmt = $conn->query("SELECT 1");
        echo "✓ Database query successful<br>";
    } catch (Exception $e) {
        echo "✗ Database query failed: " . htmlspecialchars($e->getMessage()) . "<br>";
    }
} else {
    echo "✗ \$conn variable does NOT exist<br>";
    
    // Check for other possible variable names
    $possible_vars = ['pdo', 'db', 'connection', 'database'];
    foreach ($possible_vars as $var) {
        if (isset($$var)) {
            echo "✓ \$$var variable exists instead<br>";
        }
    }
}

// Show all defined variables
echo "<h3>All defined variables:</h3>";
echo "<pre>";
print_r(get_defined_vars());
echo "</pre>";