<?php
// list_tables.php
require_once 'config/database.php';

$conn = getConnection();

echo "<h1>Tables in ability_db database</h1>";

// List all tables
$result = $conn->query("SHOW TABLES");
echo "<table border='1'>";
echo "<tr><th>Table Name</th></tr>";
while ($row = $result->fetch_array()) {
    echo "<tr><td>" . $row[0] . "</td></tr>";
}
echo "</table>";

// Check for specific tables we need
$required_tables = ['users', 'batches', 'items', 'batch_items'];
echo "<h2>Checking required tables:</h2>";
foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✓ Table '$table' exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Table '$table' does NOT exist</p>";
    }
}

$conn->close();
