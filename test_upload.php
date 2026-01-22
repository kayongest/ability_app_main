<?php
// test_upload.php - Test image upload and QR generation
echo "<h2>Testing File Upload and QR Generation</h2>";

// Test directory permissions
$rootDir = __DIR__;
$uploadsDir = $rootDir . '/uploads/items';
$qrcodeDir = $rootDir . '/qrcodes';

echo "<h3>Directory Permissions:</h3>";
echo "<ul>";
echo "<li>Root Directory: $rootDir - " . (is_writable($rootDir) ? "Writable" : "NOT Writable") . "</li>";
echo "<li>Uploads Directory: $uploadsDir - " . (is_dir($uploadsDir) ? "Exists" : "Does not exist") . " - " . (is_writable($uploadsDir) ? "Writable" : "NOT Writable") . "</li>";
echo "<li>QR Codes Directory: $qrcodeDir - " . (is_dir($qrcodeDir) ? "Exists" : "Does not exist") . " - " . (is_writable($qrcodeDir) ? "Writable" : "NOT Writable") . "</li>";
echo "</ul>";

// Create directories if they don't exist
if (!is_dir($uploadsDir)) {
    if (mkdir($uploadsDir, 0755, true)) {
        echo "<p style='color:green'>Created uploads directory</p>";
    } else {
        echo "<p style='color:red'>Failed to create uploads directory</p>";
    }
}

if (!is_dir($qrcodeDir)) {
    if (mkdir($qrcodeDir, 0755, true)) {
        echo "<p style='color:green'>Created qrcodes directory</p>";
    } else {
        echo "<p style='color:red'>Failed to create qrcodes directory</p>";
    }
}

// Test PHP settings
echo "<h3>PHP Settings:</h3>";
echo "<ul>";
echo "<li>upload_max_filesize: " . ini_get('upload_max_filesize') . "</li>";
echo "<li>post_max_size: " . ini_get('post_max_size') . "</li>";
echo "<li>max_execution_time: " . ini_get('max_execution_time') . "</li>";
echo "<li>memory_limit: " . ini_get('memory_limit') . "</li>";
echo "<li>allow_url_fopen: " . (ini_get('allow_url_fopen') ? "Enabled" : "Disabled") . "</li>";
echo "</ul>";

// Test cURL
echo "<h3>cURL Test:</h3>";
if (function_exists('curl_version')) {
    echo "<p style='color:green'>cURL is enabled</p>";
} else {
    echo "<p style='color:orange'>cURL is not enabled (QR generation may fail)</p>";
}

// Test Google Charts API
echo "<h3>Google Charts API Test:</h3>";
$testUrl = "https://chart.googleapis.com/chart?cht=qr&chs=100x100&chl=test&choe=UTF-8";
$testImage = @file_get_contents($testUrl);
if ($testImage && strlen($testImage) > 100) {
    echo "<p style='color:green'>Google Charts API is accessible</p>";
} else {
    echo "<p style='color:orange'>Google Charts API may be blocked. Trying cURL fallback...</p>";
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $testImage = curl_exec($ch);
        curl_close($ch);
        
        if ($testImage && strlen($testImage) > 100) {
            echo "<p style='color:green'>Google Charts API accessible via cURL</p>";
        } else {
            echo "<p style='color:red'>Google Charts API not accessible</p>";
        }
    }
}

// Test form
echo "<h3>Test Form:</h3>";
?>
<form method="POST" action="/ability_app-master/api/items/create.php" enctype="multipart/form-data" target="_blank">
    <input type="text" name="item_name" value="Test Item" required><br>
    <input type="text" name="serial_number" value="TEST-<?php echo time(); ?>" required><br>
    <input type="text" name="category" value="Test" required><br>
    <input type="file" name="item_image"><br>
    <button type="submit">Test API Directly</button>
</form>
<?php
echo "<p>Note: This will open the API response in a new tab. Check browser console for JavaScript errors.</p>";