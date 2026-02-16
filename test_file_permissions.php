<?php
// Test file and directory permissions
echo "=== File & Directory Permissions Check ===" . PHP_EOL . PHP_EOL;

$directories_to_check = array(
    'qrcodes',
    'uploads',
    'uploads/items',
    'uploads/qrcodes',
    'api/items'
);

$results = array();

foreach ($directories_to_check as $dir) {
    $status = array(
        'exists' => false,
        'readable' => false,
        'writable' => false,
        'permissions' => 'N/A'
    );
    
    if (file_exists($dir)) {
        $status['exists'] = true;
        $status['readable'] = is_readable($dir);
        $status['writable'] = is_writable($dir);
        $perms = fileperms($dir);
        $status['permissions'] = substr(sprintf('%o', $perms), -4);
    }
    
    $results[$dir] = $status;
}

// Display results
foreach ($results as $dir => $status) {
    echo "Directory: $dir" . PHP_EOL;
    
    if ($status['exists']) {
        echo "  Exists: YES" . PHP_EOL;
        echo "  Readable: " . ($status['readable'] ? 'YES' : 'NO') . PHP_EOL;
        echo "  Writable: " . ($status['writable'] ? 'YES' : 'NO') . PHP_EOL;
        echo "  Permissions: " . $status['permissions'] . PHP_EOL;
        
        if (!$status['writable']) {
            echo "  [WARNING] Directory is not writable!" . PHP_EOL;
        }
    } else {
        echo "  Exists: NO" . PHP_EOL;
        echo "  [WARNING] Directory does not exist!" . PHP_EOL;
    }
    
    echo PHP_EOL;
}

// Summary
echo "=== Summary ===" . PHP_EOL;
$issues = 0;
foreach ($results as $dir => $status) {
    if (!$status['exists'] || !$status['writable']) {
        $issues++;
    }
}

if ($issues > 0) {
    echo "Issues found: $issues directories need attention" . PHP_EOL;
    echo "Action required: Create missing directories or fix permissions" . PHP_EOL;
} else {
    echo "All directories exist and are writable!" . PHP_EOL;
}
?>
