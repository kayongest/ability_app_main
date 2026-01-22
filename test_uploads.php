<?php
$rootDir = __DIR__;
$uploadsDir = $rootDir . '/uploads/items';
$qrcodeDir = $rootDir . '/qrcodes';

echo "Root Directory: $rootDir\n<br>";
echo "Uploads Directory: $uploadsDir\n<br>";
echo "QR Directory: $qrcodeDir\n<br>";
echo "Uploads exists: " . (is_dir($uploadsDir) ? 'YES' : 'NO') . "\n<br>";
echo "QR exists: " . (is_dir($qrcodeDir) ? 'YES' : 'NO') . "\n<br>";
echo "Uploads writable: " . (is_writable($uploadsDir) ? 'YES' : 'NO') . "\n<br>";
echo "QR writable: " . (is_writable($qrcodeDir) ? 'YES' : 'NO') . "\n<br>";

// List files in uploads
if (is_dir($uploadsDir)) {
    echo "\n<br>Files in uploads/items:\n<br>";
    $files = scandir($uploadsDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "- $file\n<br>";
        }
    }
}