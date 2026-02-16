<?php
// check_batches.php
require_once 'config/database.php';

$conn = getConnection();

echo "<h1>Batches in Database</h1>";

// Check if batches table exists
$result = $conn->query("SHOW TABLES LIKE 'batches'");
if ($result->num_rows === 0) {
    echo "<p style='color: red;'>The 'batches' table does not exist!</p>";
    exit;
}

// Get all batches
$sql = "SELECT id, batch_id, batch_name, submitted_at, approval_status, 
               requested_by, submitted_by, approved_by 
        FROM batches 
        ORDER BY submitted_at DESC";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    echo "<p style='color: orange;'>No batches found in the database.</p>";
    echo "<p>You need to submit a new batch first.</p>";
} else {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr>
            <th>ID</th>
            <th>Batch ID</th>
            <th>Name</th>
            <th>Submitted</th>
            <th>Status</th>
            <th>Requested By</th>
            <th>Submitted By</th>
            <th>Actions</th>
          </tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td><code>" . htmlspecialchars($row['batch_id']) . "</code></td>";
        echo "<td>" . htmlspecialchars($row['batch_name'] ?? 'N/A') . "</td>";
        echo "<td>" . $row['submitted_at'] . "</td>";
        echo "<td>" . $row['approval_status'] . "</td>";
        echo "<td>" . ($row['requested_by'] ?: 'N/A') . "</td>";
        echo "<td>" . ($row['submitted_by'] ?: 'N/A') . "</td>";
        echo "<td>
                <a href='ajax/get_batch_details.php?batch_id=" . urlencode($row['batch_id']) . "' target='_blank'>
                    View JSON
                </a>
              </td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Also check users table
echo "<h2>Users in Database</h2>";
$result = $conn->query("SELECT id, username, full_name, role, department FROM users ORDER BY id");
if ($result->num_rows === 0) {
    echo "<p style='color: orange;'>No users found.</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Department</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['full_name'] ?? '') . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "<td>" . htmlspecialchars($row['department'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();
?>