<?php
// verify_paths.php
echo "<h3>Path Verification for create.php</h3>";

// Simulate what create.php sees
$currentDir = 'C:/xampp/htdocs/ability_app-master/api/items';
echo "Current directory (simulated): $currentDir<br>";

$upOne = dirname($currentDir); // /api
$upTwo = dirname($upOne);      // /ability_app-master
$realPath = realpath($upTwo);

echo "Up one level: $upOne<br>";
echo "Up two levels: $upTwo<br>";
echo "Real path: $realPath<br>";

// Check if files exist at this path
$filesToCheck = [
    'bootstrap.php',
    'includes/db_connect.php',
    'includes/functions.php',
    'uploads/items',
    'qrcodes'
];

echo "<h3>File/Directory Check:</h3>";
foreach ($filesToCheck as $file) {
    $fullPath = $realPath . '/' . $file;
    $exists = file_exists($fullPath);
    $writable = is_writable($fullPath);
    echo "$file: " . ($exists ? "EXISTS" : "MISSING") . 
         ", " . ($writable ? "WRITABLE" : "NOT WRITABLE") . "<br>";
}
?>