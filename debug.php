<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/database_fix.php';
require_once 'includes/functions.php';

try {
    $db = new DatabaseFix();
    $conn = $db->getConnection();
    
    echo "<h1>Database Debug Info</h1>";
    
    // Test 1: Count items
    $result = $conn->query("SELECT COUNT(*) as total FROM items");
    $row = $result->fetch_assoc();
    echo "<p>Total items in database: " . $row['total'] . "</p>";
    
    // Test 2: Get sample items
    $result = $conn->query("SELECT id, item_name, serial_number, status FROM items LIMIT 10");
    echo "<h2>Sample Items (first 10):</h2>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Name</th><th>Serial</th><th>Status</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['item_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['serial_number']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test 3: Test getRecentItems function
    echo "<h2>Test getRecentItems() function:</h2>";
    $recentItems = getRecentItems($conn, 5);
    echo "<p>Returned " . count($recentItems) . " items</p>";
    if (!empty($recentItems)) {
        echo "<pre>" . print_r($recentItems[0], true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<h1>Error:</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>