<?php
// test_qr_compatibility.php
echo "Testing existing QR code functionality:\n";

// Include your existing QR code library (if any)
if (file_exists('your_existing_qr_library.php')) {
    include 'your_existing_qr_library.php';
    echo "✓ Existing library loaded\n";
}

// Include Composer autoloader (new libraries)
require 'vendor/autoload.php';
echo "✓ Composer autoloader loaded\n";

// Test if existing functions still work
if (function_exists('your_existing_qr_function')) {
    echo "✓ Existing QR function exists\n";
}

echo "\nTest completed - check if everything works\n";
?>