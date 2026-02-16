<?php
// check_technicians_table.php - Check technicians table structure
require_once 'config/database.php';

try {
    $conn = getConnection();

    $result = $conn->query('DESCRIBE technicians');

    echo "Technicians table structure:\n";
    echo str_repeat("-", 50) . "\n";

    while($row = $result->fetch_assoc()) {
        printf("%-20s %-15s %s\n",
            $row['Field'],
            $row['Type'],
            $row['Null'] === 'NO' ? 'NOT NULL' : 'NULL'
        );
    }

    echo "\n✅ Technicians table structure verified\n";

    $conn->close();
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>
