<?php
// simple_test.php - Completely separate from your app
session_start();

// Hardcode the correct path
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/ability_app_main/';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Simple Test</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .test { margin: 10px 0; padding: 10px; background: #f0f0f0; }
    </style>
</head>
<body>
    <h1>Simple Navigation Test</h1>
    
    <div class='test'>
        <h3>Test Links:</h3>
        <a href='{$base_url}dashboard.php'>Dashboard</a><br>
        <a href='{$base_url}scan.php'>Scan Single</a><br>
        <a href='{$base_url}scan_2.php'>Scan Bulk</a><br>
        <a href='{$base_url}login.php'>Login</a><br>
        <a href='{$base_url}logout.php'>Logout</a>
    </div>
    
    <div class='test'>
        <h3>Session Info:</h3>
        <pre>" . print_r($_SESSION, true) . "</pre>
    </div>
    
    <div class='test'>
        <h3>Base URL:</h3>
        $base_url
    </div>
</body>
</html>";
?>