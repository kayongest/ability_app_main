<?php
// check_batch_13_details.php
require_once 'config/database.php';

$conn = getConnection();

echo "<h1>Detailed View of Batch ID 13</h1>";

$sql = "SELECT * FROM batch_scans WHERE id = 13";
$result = $conn->query($sql);
$batch = $result->fetch_assoc();

echo "<h2>Batch Data:</h2>";
echo "<pre>" . print_r($batch, true) . "</pre>";

// Check users table for submitted_by user (id=2)
$sql = "SELECT * FROM users WHERE id = 2";
$result = $conn->query($sql);
$user = $result->fetch_assoc();

echo "<h2>Submitted By User (ID=2):</h2>";
echo "<pre>" . print_r($user, true) . "</pre>";

// Check if there's a requested_by user
if ($batch['requested_by']) {
    $sql = "SELECT * FROM users WHERE id = " . $batch['requested_by'];
    $result = $conn->query($sql);
    $tech = $result->fetch_assoc();
    
    echo "<h2>Requested By User (ID={$batch['requested_by']}):</h2>";
    echo "<pre>" . print_r($tech, true) . "</pre>";
} else {
    echo "<h2 style='color: red;'>requested_by is NULL - No technician assigned!</h2>";
}

$conn->close();
?>