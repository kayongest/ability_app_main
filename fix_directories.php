<?php
// fix_directories.php
echo "<h2>Fixing Directory Structure</h2>";

$directories = [
    'uploads/items',
    'qrcodes'
];

foreach ($directories as $dir) {
    $fullPath = __DIR__ . '/' . $dir;
    
    if (!is_dir($fullPath)) {
        if (mkdir($fullPath, 0755, true)) {
            echo "<p style='color:green;'>✓ Created directory: $dir</p>";
        } else {
            echo "<p style='color:red;'>✗ Failed to create directory: $dir</p>";
        }
    } else {
        echo "<p>✓ Directory exists: $dir</p>";
    }
    
    // Check permissions
    if (is_dir($fullPath)) {
        $perms = substr(sprintf('%o', fileperms($fullPath)), -4);
        $writable = is_writable($fullPath);
        echo "<p>Permissions for $dir: $perms (writable: " . ($writable ? 'Yes' : 'No') . ")</p>";
    }
}

// List all files in project root
echo "<h3>Project Structure:</h3>";
echo "<pre>";
system("ls -la " . escapeshellarg(__DIR__));
echo "</pre>";

// List uploads directory
echo "<h3>Uploads Directory:</h3>";
echo "<pre>";
if (is_dir(__DIR__ . '/uploads')) {
    system("ls -la " . escapeshellarg(__DIR__ . '/uploads'));
} else {
    echo "Uploads directory doesn't exist";
}
echo "</pre>";