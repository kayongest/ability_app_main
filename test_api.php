<?php
// test_paths.php - Check file locations
echo "<h2>File Path Test</h2>";

// Current directory
echo "<p><strong>Current directory:</strong> " . __DIR__ . "</p>";

// Check if api/items directory exists
$apiItemsDir = __DIR__ . '/api/items';
echo "<p><strong>API Items directory:</strong> " . $apiItemsDir . "</p>";
echo "<p><strong>Directory exists:</strong> " . (is_dir($apiItemsDir) ? 'YES' : 'NO') . "</p>";

if (is_dir($apiItemsDir)) {
    // List files in the directory
    $files = scandir($apiItemsDir);
    echo "<p><strong>Files in api/items:</strong></p>";
    echo "<ul>";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $filePath = $apiItemsDir . '/' . $file;
            echo "<li>" . $file . " - " . (file_exists($filePath) ? 'Exists' : 'Missing') . "</li>";
        }
    }
    echo "</ul>";
}

// Test the URL path
echo "<p><strong>Test URL:</strong> <a href='http://127.0.0.1/ability_app-master/api/items/list.php' target='_blank'>http://127.0.0.1/ability_app-master/api/items/list.php</a></p>";
echo "<p><strong>Test direct file access:</strong> <a href='api/items/list.php' target='_blank'>api/items/list.php</a></p>";

// Check if we can access the file
$listFile = __DIR__ . '/api/items/list.php';
echo "<p><strong>list.php file path:</strong> " . $listFile . "</p>";
echo "<p><strong>File exists:</strong> " . (file_exists($listFile) ? 'YES' : 'NO') . "</p>";

if (file_exists($listFile)) {
    echo "<p><strong>File size:</strong> " . filesize($listFile) . " bytes</p>";
    echo "<p><strong>File permissions:</strong> " . substr(sprintf('%o', fileperms($listFile)), -4) . "</p>";
    
    // Test reading the file
    $content = file_get_contents($listFile);
    echo "<p><strong>First 200 chars:</strong> <pre>" . htmlspecialchars(substr($content, 0, 200)) . "...</pre></p>";
}
?>