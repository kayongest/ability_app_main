<?php
// fix_batch_scans_technicians.php
require_once 'config/database.php';

$conn = getConnection();

echo "<h1>Fixing batch_scans requested_by references</h1>";

// 1. Get all unique requested_by values that are not NULL
$sql = "SELECT DISTINCT requested_by FROM batch_scans WHERE requested_by IS NOT NULL";
$result = $conn->query($sql);

$valid_user_ids = [];
while ($row = $result->fetch_assoc()) {
    $valid_user_ids[] = $row['requested_by'];
}

echo "<h2>Currently used requested_by values:</h2>";
echo "<pre>" . print_r($valid_user_ids, true) . "</pre>";

// 2. Check which ones exist in users table
$invalid_ids = [];
foreach ($valid_user_ids as $user_id) {
    $check_sql = "SELECT id FROM users WHERE id = $user_id";
    $check_result = $conn->query($check_sql);
    if ($check_result->num_rows === 0) {
        $invalid_ids[] = $user_id;
    }
}

echo "<h2>Invalid user IDs (not in users table):</h2>";
if (empty($invalid_ids)) {
    echo "<p style='color: green;'>✓ All requested_by values are valid user IDs</p>";
} else {
    echo "<p style='color: red;'>✗ Invalid user IDs found: " . implode(', ', $invalid_ids) . "</p>";

    // Get first valid technician ID
    $valid_tech_sql = "SELECT id FROM users WHERE role IN ('technician', 'tech', 'user') LIMIT 1";
    $valid_result = $conn->query($valid_tech_sql);
    $valid_tech = $valid_result->fetch_assoc();
    $default_tech_id = $valid_tech['id'] ?? 1;

    echo "<p>Will update invalid IDs to: $default_tech_id</p>";

    // Update invalid IDs
    foreach ($invalid_ids as $invalid_id) {
        $update_sql = "UPDATE batch_scans SET requested_by = $default_tech_id WHERE requested_by = $invalid_id";
        if ($conn->query($update_sql)) {
            echo "<p>✓ Updated requested_by $invalid_id to $default_tech_id</p>";
        }
    }
}

// 3. Update NULL requested_by to use submitted_by
$null_sql = "UPDATE batch_scans SET requested_by = submitted_by WHERE requested_by IS NULL";
if ($conn->query($null_sql)) {
    $affected = $conn->affected_rows;
    echo "<p style='color: green;'>✓ Updated $affected NULL requested_by to use submitted_by</p>";
}

$conn->close();
