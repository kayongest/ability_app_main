<?php
// debug_users.php
require_once 'config/database.php';

$conn = getConnection();

echo "<h1>Debug Users Table for Technician Selection</h1>";

// 1. Get all users that should appear as technicians
$sql = "SELECT 
            id, 
            username, 
            full_name, 
            email, 
            department, 
            role, 
            is_active,
            created_at
        FROM users 
        WHERE is_active = 1 
        ORDER BY full_name, username";

$result = $conn->query($sql);

if (!$result) {
    die("<p style='color: red;'>Query failed: " . $conn->error . "</p>");
}

echo "<h2>All Active Users (" . $result->num_rows . "):</h2>";
echo "<table border='1' style='width: 100%;'>";
echo "<tr>
        <th>ID</th>
        <th>Username</th>
        <th>Full Name</th>
        <th>Email</th>
        <th>Department</th>
        <th>Role</th>
        <th>Active</th>
        <th>Created</th>
      </tr>";

$technician_count = 0;
$stock_controller_count = 0;

while ($row = $result->fetch_assoc()) {
    $role = $row['role'] ?? 'user';
    $is_technician = in_array($role, ['technician', 'tech', 'user']);
    $is_stock_controller = in_array($role, ['admin', 'stock_controller', 'supervisor']);
    
    if ($is_technician) $technician_count++;
    if ($is_stock_controller) $stock_controller_count++;
    
    $row_color = $is_technician ? '#e8f5e8' : ($is_stock_controller ? '#fff3cd' : '');
    
    echo "<tr style='background-color: $row_color'>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td><strong>" . htmlspecialchars($row['username']) . "</strong></td>";
    echo "<td>" . htmlspecialchars($row['full_name'] ?? 'N/A') . "</td>";
    echo "<td>" . htmlspecialchars($row['email'] ?? '') . "</td>";
    echo "<td>" . htmlspecialchars($row['department'] ?? '') . "</td>";
    echo "<td><span class='badge'>" . $role . "</span></td>";
    echo "<td>" . ($row['is_active'] ? '✅' : '❌') . "</td>";
    echo "<td>" . $row['created_at'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Summary:</h2>";
echo "<p>Total Active Users: " . $result->num_rows . "</p>";
echo "<p>Potential Technicians: $technician_count</p>";
echo "<p>Potential Stock Controllers: $stock_controller_count</p>";

// 2. Check what the API returns
echo "<h2>Testing API Endpoints:</h2>";

echo "<h3>1. <a href='api/technicians/get_all.php' target='_blank'>api/technicians/get_all.php</a></h3>";
echo "<p>This should return all technicians in JSON format.</p>";

echo "<h3>2. <a href='api/users/get_technicians.php' target='_blank'>api/users/get_technicians.php</a></h3>";
echo "<p>Alternative endpoint for getting technicians.</p>";

// 3. Test session data
session_start();
echo "<h2>Current Session Data:</h2>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

$conn->close();
?>