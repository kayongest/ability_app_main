<?php
// test_quickchart.php
$data = [
    'id' => 999,
    'name' => 'Test Item',
    'serial' => 'TEST-123',
    'location' => 'Test Location',
    'url' => 'http://127.0.0.1/ability_app-master/items/view.php?id=999',
    'type' => 'equipment',
    'timestamp' => time(),
    'system' => 'aBility Manager'
];

$dataString = json_encode($data, JSON_UNESCAPED_SLASHES);
$url = "https://quickchart.io/qr?text=" . urlencode($dataString) . "&size=300&margin=1";

echo "<h2>Testing QuickChart.io</h2>";
echo "<p>URL: " . htmlspecialchars($url) . "</p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$image = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p>HTTP Code: $httpCode</p>";
echo "<p>Error: " . ($error ?: 'None') . "</p>";
echo "<p>Image size: " . strlen($image) . " bytes</p>";

if ($httpCode === 200 && $image) {
    file_put_contents('test_quickchart_qr.png', $image);
    echo "<p style='color:green;'>✓ QR code generated!</p>";
    echo "<img src='test_quickchart_qr.png' alt='QR Code'><br>";
    echo "<p>Scan this QR to see: " . htmlspecialchars($dataString) . "</p>";
} else {
    echo "<p style='color:red;'>✗ Failed to generate QR</p>";
}