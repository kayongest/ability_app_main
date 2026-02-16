<?php
// check_scans.php - Place this in your root directory
require_once 'includes/db_connect.php';

$db = getDatabase();
$conn = $db->getConnection();

echo "<h2>Checking Scans Table</h2>";

// Check table structure
echo "<h3>Table Structure:</h3>";
$result = $conn->query("DESCRIBE scans");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check all data in scans table
echo "<h3>All Scan Records:</h3>";
$result = $conn->query("SELECT * FROM scans ORDER BY id DESC");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Timestamp</th><th>Type</th><th>Item ID</th><th>User ID</th><th>Location</th><th>Notes</th></tr>";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['scan_timestamp'] . "</td>";
        echo "<td>" . $row['scan_type'] . "</td>";
        echo "<td>" . $row['item_id'] . "</td>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['location'] . "</td>";
        echo "<td>" . substr($row['notes'] ?? '', 0, 50) . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='7'>No scans found in the database</td></tr>";
}
echo "</table>";

// Check items table
echo "<h3>Items Table (first 10):</h3>";
$result = $conn->query("SELECT id, item_name, serial_number FROM items LIMIT 10");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>Serial</th></tr>";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['item_name'] . "</td>";
        echo "<td>" . $row['serial_number'] . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='3'>No items found</td></tr>";
}
echo "</table>";

// Test the JOIN query
echo "<h3>Testing JOIN Query:</h3>";
$sql = "SELECT 
            s.id,
            s.scan_timestamp,
            s.scan_type,
            s.scan_method,
            s.location,
            s.notes,
            s.user_id,
            s.item_id,
            i.item_name,
            i.serial_number,
            i.category,
            u.username,
            u.full_name
        FROM scans s
        LEFT JOIN items i ON s.item_id = i.id
        LEFT JOIN users u ON s.user_id = u.id
        ORDER BY s.scan_timestamp DESC
        LIMIT 10";

$result = $conn->query($sql);

if ($result) {
    echo "Query executed successfully. Found " . $result->num_rows . " records.<br><br>";
    
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Timestamp</th><th>Type</th><th>Item Name</th><th>Serial</th><th>User</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['scan_timestamp'] . "</td>";
            echo "<td>" . $row['scan_type'] . "</td>";
            echo "<td>" . ($row['item_name'] ?? 'NULL') . "</td>";
            echo "<td>" . ($row['serial_number'] ?? 'NULL') . "</td>";
            echo "<td>" . ($row['full_name'] ?? $row['username'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No results from JOIN query.<br>";
        echo "Possible issues:";
        echo "<ul>";
        echo "<li>Scans table is empty</li>";
        echo "<li>Item IDs in scans don't match any items</li>";
        echo "<li>Column names might be different</li>";
        echo "</ul>";
    }
} else {
    echo "Query failed: " . $conn->error . "<br>";
}

$conn->close();
?>