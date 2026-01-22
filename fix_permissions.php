<?php
// fix_permissions.php - Fix directory permissions
echo "<h2>Fixing Directory Permissions</h2>";

$rootDir = __DIR__;
$directories = [
    '/uploads',
    '/uploads/items',
    '/qrcodes',
    '/api/items',
    '/includes'
];

foreach ($directories as $dir) {
    $fullPath = $rootDir . $dir;
    
    if (!is_dir($fullPath)) {
        if (mkdir($fullPath, 0755, true)) {
            echo "<p style='color:green'>Created directory: $dir</p>";
        } else {
            echo "<p style='color:red'>Failed to create directory: $dir</p>";
        }
    } else {
        echo "<p>Directory exists: $dir</p>";
    }
    
    // Try to set permissions
    if (is_dir($fullPath)) {
        if (chmod($fullPath, 0755)) {
            echo "<p style='color:green'>Set permissions on: $dir</p>";
        } else {
            echo "<p style='color:orange'>Could not change permissions on: $dir</p>";
        }
    }
}

// Create .htaccess for uploads directory
$uploadsHtaccess = $rootDir . '/uploads/.htaccess';
if (!file_exists($uploadsHtaccess)) {
    $htaccessContent = "Options -Indexes\n<FilesMatch \"\.(php|php5|phtml)$\">\nDeny from all\n</FilesMatch>";
    file_put_contents($uploadsHtaccess, $htaccessContent);
    echo "<p style='color:green'>Created .htaccess for uploads directory</p>";
}

echo "<h3>Current Permissions:</h3>";
foreach ($directories as $dir) {
    $fullPath = $rootDir . $dir;
    if (is_dir($fullPath)) {
        $perms = fileperms($fullPath);
        $perms = substr(sprintf('%o', $perms), -4);
        $writable = is_writable($fullPath);
        echo "<p>$dir: Permissions $perms, " . ($writable ? "Writable" : "NOT Writable") . "</p>";
    }
}
?>