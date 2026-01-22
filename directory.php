<?php
// debug_paths.php - Place this in the same directory as your bootstrap.php
echo "<h3>Debugging File Paths</h3>";
echo "Current file: " . __FILE__ . "<br>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Parent directory: " . dirname(__DIR__) . "<br>";

echo "<h4>Checking for files:</h4>";

// Check bootstrap.php
$bootstrap_path = __DIR__ . '/bootstrap.php';
echo "bootstrap.php path: $bootstrap_path<br>";
echo "bootstrap.php exists: " . (file_exists($bootstrap_path) ? 'YES' : 'NO') . "<br>";

// Check functions.php from current directory
$functions_path1 = __DIR__ . '/includes/functions.php';
echo "functions.php path (relative): $functions_path1<br>";
echo "functions.php exists: " . (file_exists($functions_path1) ? 'YES' : 'NO') . "<br>";

// Check functions.php from parent directory
$functions_path2 = dirname(__DIR__) . '/includes/functions.php';
echo "functions.php path (parent): $functions_path2<br>";
echo "functions.php exists: " . (file_exists($functions_path2) ? 'YES' : 'NO') . "<br>";

echo "<h4>Project Structure:</h4>";
echo "<pre>";
// List files in current directory
echo "Files in " . __DIR__ . ":\n";
foreach (scandir(__DIR__) as $file) {
    if ($file != '.' && $file != '..') {
        echo "  - $file\n";
    }
}

// List files in includes directory if it exists
$includes_dir = __DIR__ . '/includes';
if (is_dir($includes_dir)) {
    echo "\nFiles in " . $includes_dir . ":\n";
    foreach (scandir($includes_dir) as $file) {
        if ($file != '.' && $file != '..') {
            echo "  - $file\n";
        }
    }
}
echo "</pre>";
?>