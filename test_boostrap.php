<?php
// test_bootstrap.php
echo "<h1>Testing bootstrap.php</h1>";

// Test 1: Check if file exists
if (file_exists('includes/bootstrap.php')) {
    echo "<p style='color:green'>✓ bootstrap.php file exists</p>";
    
    // Test 2: Try to include it
    try {
        require_once 'includes/bootstrap.php';
        echo "<p style='color:green'>✓ bootstrap.php loaded successfully</p>";
        
        // Test 3: Check for required functions
        $required_functions = ['isLoggedIn'];
        foreach ($required_functions as $func) {
            if (function_exists($func)) {
                echo "<p style='color:green'>✓ Function $func() exists</p>";
            } else {
                echo "<p style='color:red'>✗ Function $func() NOT found</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p style='color:red'>✗ Error loading bootstrap.php: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red'>✗ bootstrap.php file NOT found at 'includes/bootstrap.php'</p>";
    echo "<p>Current directory: " . __DIR__ . "</p>";
}

// Test session
session_start();
echo "<h2>Session Info</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>