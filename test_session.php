<?php
session_start();
echo "<h3>Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Checking login status:</h3>";
echo "isLoggedIn() function result: " . (function_exists('isLoggedIn') ? var_export(isLoggedIn(), true) : 'Function not found');
?>