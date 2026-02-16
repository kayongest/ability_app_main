<?php
// debug_database_extra.php
require_once 'config/database.php';

$conn = getConnection();

echo "<h1>Checking All Batch-Related Tables</h1>";

// Check each batch-related table
$batch_tables = ['batches', 'batch_items', 'batch_scans', 'scan_batches', 'scan_logs'];

foreach ($batch_tables as $table) {
    echo "<h2>Table: $table</h2>";

    // Check if table exists
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows === 0) {
        echo "<p style='color: orange;'>Table does not exist</p>";
        continue;
    }

    // Count rows
    $result = $conn->query("SELECT COUNT(*) as count FROM $table");
    $row = $result->fetch_assoc();
    $count = $row['count'];

    echo "<p>Rows: $count</p>";

    if ($count > 0) {
        // Show some sample data
        $result = $conn->query("SELECT * FROM $table ORDER BY id DESC LIMIT 3");
        echo "<table border='1'>";

        // Get column names
        $columns = [];
        $result_meta = $conn->query("SHOW COLUMNS FROM $table");
        echo "<tr>";
        while ($col = $result_meta->fetch_assoc()) {
            echo "<th>" . $col['Field'] . "</th>";
            $columns[] = $col['Field'];
        }
        echo "</tr>";

        // Show data
        while ($data = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($columns as $col) {
                echo "<td>";
                if (strlen($data[$col] ?? '') > 50) {
                    echo substr($data[$col], 0, 50) . "...";
                } else {
                    echo htmlspecialchars($data[$col] ?? '');
                }
                echo "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
}

$conn->close();
