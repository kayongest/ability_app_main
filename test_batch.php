<?php
// test_batch.php
$conn = new mysqli('localhost', 'root', '', 'ability_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>Batch System Test</h1>";

// Test 1: Check tables exist
$tables = ['batch_scans', 'batch_items', 'users'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<p style='color:green'>✓ Table '$table' exists</p>";
    } else {
        echo "<p style='color:red'>✗ Table '$table' does NOT exist</p>";
    }
}

// Test 2: Check batch data
$batchId = 'BATCH-20260204204958-6b5fa39e';
$result = $conn->query("SELECT * FROM batch_scans WHERE batch_id = '$batchId'");
if ($result->num_rows > 0) {
    $batch = $result->fetch_assoc();
    echo "<p style='color:green'>✓ Batch '$batchId' exists: " . $batch['batch_name'] . "</p>";
    
    // Check items
    $itemsResult = $conn->query("SELECT COUNT(*) as count FROM batch_items WHERE batch_id = '$batchId'");
    $itemsCount = $itemsResult->fetch_assoc()['count'];
    echo "<p style='color:green'>✓ Batch has $itemsCount items</p>";
} else {
    echo "<p style='color:red'>✗ Batch '$batchId' does NOT exist in database</p>";
}

// Show all batches
echo "<h2>All Batches in Database:</h2>";
$result = $conn->query("SELECT batch_id, batch_name, total_items, submitted_at FROM batch_scans ORDER BY submitted_at DESC");
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Batch ID</th><th>Name</th><th>Items</th><th>Date</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['batch_id'] . "</td>";
        echo "<td>" . $row['batch_name'] . "</td>";
        echo "<td>" . $row['total_items'] . "</td>";
        echo "<td>" . $row['submitted_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:orange'>No batches found in database</p>";
}

$conn->close();
?>