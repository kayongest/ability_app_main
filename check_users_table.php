<?php
// check_users_table.php
require_once 'config/database.php';

$conn = getConnection();

echo "<h1>Current Users Table Structure</h1>";

// Check table structure
$sql = "DESCRIBE users";
$result = $conn->query($sql);

echo "<table border='1'>";
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

// Check existing data
echo "<h2>Existing Users (First 10):</h2>";
$sql = "SELECT id, username, full_name, role, department, email FROM users LIMIT 10";
$result = $conn->query($sql);

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Department</th><th>Email</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
    echo "<td>" . htmlspecialchars($row['full_name'] ?? '') . "</td>";
    echo "<td>" . ($row['role'] ?? 'N/A') . "</td>";
    echo "<td>" . htmlspecialchars($row['department'] ?? '') . "</td>";
    echo "<td>" . htmlspecialchars($row['email'] ?? '') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check what roles exist
echo "<h2>Existing Roles Distribution:</h2>";
$sql = "SELECT role, COUNT(*) as count FROM users WHERE role IS NOT NULL GROUP BY role";
$result = $conn->query($sql);

echo "<table border='1'>";
echo "<tr><th>Role</th><th>Count</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['role'] . "</td>";
    echo "<td>" . $row['count'] . "</td>";
    echo "</tr>";
}
echo "</table>";

$conn->close();
