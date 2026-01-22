<?php
// check_tables.php
require_once 'includes/db_connect.php';

$db = getDatabase();
$conn = $db->getConnection();

echo "<h2>Checking Database Tables</h2>";

// List all tables in the database
$result = $conn->query("SHOW TABLES");
echo "<h3>All Tables in Database:</h3>";
echo "<ul>";
while ($row = $result->fetch_array()) {
    echo "<li>" . $row[0] . "</li>";
}
echo "</ul>";

// Check if specific tables exist
$tables = ['items', 'scans', 'users'];

foreach ($tables as $table) {
    echo "<h3>Checking '$table' table:</h3>";
    
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✓ Table '$table' exists<br>";
        
        // Show table structure
        $descResult = $conn->query("DESCRIBE $table");
        if ($descResult) {
            echo "Table structure:<br>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            while ($row = $descResult->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "✗ Error describing table: " . $conn->error . "<br>";
        }
    } else {
        echo "✗ Table '$table' does NOT exist<br>";
    }
    echo "<hr>";
}

$conn->close();
?>