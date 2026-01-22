<?php
// test_api_path.php
echo "Testing API path...<br>";
echo "Current URL: " . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "<br>";

$apiPath = __DIR__ . '/api/items/create.php';
echo "Full path to API: " . $apiPath . "<br>";
echo "API file exists: " . (file_exists($apiPath) ? "YES" : "NO") . "<br>";

if (file_exists($apiPath)) {
    echo "API file size: " . filesize($apiPath) . " bytes<br>";
    echo "API file permissions: " . substr(sprintf('%o', fileperms($apiPath)), -4) . "<br>";
}

// Test relative path from dashboard
echo "<br>Relative path test:<br>";
echo "From dashboard.php (root) to api/items/create.php: 'api/items/create.php'<br>";
echo "Using 'api/items/create.php' would go to: 'htdocs/api/items/create.php' (WRONG!)<br>";
?>