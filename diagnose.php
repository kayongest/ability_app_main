<?php
header('Content-Type: text/plain');

echo "=== PHP Configuration Check ===\n\n";

// Check PHP version
echo "PHP Version: " . phpversion() . "\n";

// Check required extensions
$required_extensions = ['mysqli', 'gd', 'curl', 'json', 'mbstring'];
echo "\nRequired Extensions:\n";
foreach ($required_extensions as $ext) {
    echo "- $ext: " . (extension_loaded($ext) ? "✓" : "✗") . "\n";
}

// Check file permissions
echo "\n=== File Permissions ===\n";
$paths = [
    __DIR__,
    __DIR__ . '/uploads',
    __DIR__ . '/uploads/items',
    __DIR__ . '/qrcodes',
    __DIR__ . '/includes',
    __DIR__ . '/config'
];

foreach ($paths as $path) {
    if (file_exists($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $writable = is_writable($path);
        echo "$path: $perms, " . ($writable ? "writable" : "NOT writable") . "\n";
    } else {
        echo "$path: Does not exist\n";
    }
}

// Check database connection
echo "\n=== Database Connection Test ===\n";
try {
    require_once __DIR__ . '/config/database.php';
    // Adjust based on your actual database.php content
    $conn = new mysqli('localhost', 'root', '', 'ability_db');
    if ($conn->connect_error) {
        echo "Database connection failed: " . $conn->connect_error . "\n";
    } else {
        echo "Database connection successful!\n";
        $conn->close();
    }
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}

// Check Apache modules
echo "\n=== Apache Modules ===\n";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    $required_modules = ['mod_rewrite', 'mod_headers'];
    foreach ($required_modules as $module) {
        echo "- $module: " . (in_array($module, $modules) ? "✓" : "✗") . "\n";
    }
}

// Memory limit
echo "\n=== PHP Settings ===\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "\n";
echo "Post Max Size: " . ini_get('post_max_size') . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . "\n";