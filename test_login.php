<?php
// test_login.php
session_start();

// Set all session variables for testing
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';
$_SESSION['logged_in'] = true;

echo "Test login successful!";
echo "<br><br>";
echo "Session variables set:";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
echo "<br><br>";
echo "<a href='technicians.php'>Go to Technicians Page</a>";
echo "<br>";
echo "<a href='dashboard.php'>Go to Dashboard</a>";
?>