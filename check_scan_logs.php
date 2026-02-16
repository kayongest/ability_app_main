<?php
// check_scan_logs.php
require_once 'includes/db_connect.php';

$db = getDatabase();
$conn = $db->getConnection();

echo "<h2>Checking Scan Logs Table</h2>";

// Check scan_logs table structure
echo "<h3>Scan Logs Table Structure:</h3>";
$result = $conn->query("DESCRIBE scan_logs");
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

// Check scan_logs data
echo "<h3>Scan Logs Data (latest 10):</h3>";
$result = $conn->query("SELECT * FROM scan_logs ORDER BY id DESC LIMIT 10");
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
    echo "<tr><td colspan='7'>No scan logs found</td></tr>";
}
echo "</table>";

$conn->close();
?>