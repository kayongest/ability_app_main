<?php
// Test PHP Extensions
echo "=== PHP Extension Check ===" . PHP_EOL . PHP_EOL;

$required_extensions = array(
    'mysqli' => 'Database connectivity',
    'gd' => 'Image processing & QR code generation',
    'curl' => 'External API calls',
    'fileinfo' => 'File type detection',
    'mbstring' => 'Multi-byte string handling',
    'json' => 'JSON encoding/decoding',
    'session' => 'Session management',
    'zip' => 'ZIP file operations'
);

$missing = array();
$loaded = array();

foreach ($required_extensions as $ext => $purpose) {
    if (extension_loaded($ext)) {
        echo "[OK] $ext - $purpose" . PHP_EOL;
        $loaded[] = $ext;
    } else {
        echo "[MISSING] $ext - $purpose" . PHP_EOL;
        $missing[] = $ext;
    }
}

echo PHP_EOL . "=== Summary ===" . PHP_EOL;
echo "Loaded: " . count($loaded) . "/" . count($required_extensions) . PHP_EOL;

if (!empty($missing)) {
    echo PHP_EOL . "Missing extensions:" . PHP_EOL;
    foreach ($missing as $ext) {
        echo "  - $ext" . PHP_EOL;
    }
    echo PHP_EOL . "Action required: Enable these extensions in php.ini" . PHP_EOL;
} else {
    echo PHP_EOL . "All required extensions are loaded!" . PHP_EOL;
}

// Check PHP version
echo PHP_EOL . "=== PHP Version ===" . PHP_EOL;
echo "Version: " . PHP_VERSION . PHP_EOL;
echo "Required: >= 7.4" . PHP_EOL;

if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    echo "Status: OK" . PHP_EOL;
} else {
    echo "Status: UPGRADE NEEDED" . PHP_EOL;
}
?>
