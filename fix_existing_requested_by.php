<?php
// fix_existing_requested_by.php
require_once 'config/database.php';

$conn = getConnection();

echo "<h1>Fixing requested_by for Existing Batches</h1>";

// 1. Check if requested_by column exists
$check_sql = "SHOW COLUMNS FROM batch_scans LIKE 'requested_by'";
$result = $conn->query($check_sql);

if ($result->num_rows === 0) {
    echo "<p style='color: red;'>requested_by column doesn't exist! Adding it...</p>";
    
    $add_sql = "ALTER TABLE batch_scans ADD COLUMN requested_by INT AFTER submitted_by";
    if ($conn->query($add_sql)) {
        echo "<p style='color: green;'>✓ Column added successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding column: " . $conn->error . "</p>";
        exit;
    }
}

// 2. Update NULL requested_by to submitted_by (as fallback)
$update_sql = "UPDATE batch_scans SET requested_by = submitted_by WHERE requested_by IS NULL";
if ($conn->query($update_sql)) {
    $affected = $conn->affected_rows;
    echo "<p style='color: green;'>✓ Updated $affected batches (set requested_by = submitted_by)</p>";
} else {
    echo "<p style='color: red;'>✗ Update failed: " . $conn->error . "</p>";
}

// 3. Show results
$sql = "SELECT id, batch_id, submitted_by, requested_by FROM batch_scans ORDER BY id";
$result = $conn->query($sql);

echo "<h2>Current Batches:</h2>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Batch ID</th><th>Submitted By</th><th>Requested By</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['batch_id'] . "</td>";
    echo "<td>" . ($row['submitted_by'] ?: 'NULL') . "</td>";
    echo "<td>" . ($row['requested_by'] ?: 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

$conn->close();
?>