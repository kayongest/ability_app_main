<?php
// fix_paths.php - Run this once to fix bootstrap.php

$bootstrap_file = 'includes/bootstrap.php';

if (file_exists($bootstrap_file)) {
    // Read the file
    $content = file_get_contents($bootstrap_file);

    // Find and replace the wrong BASE_URL
    $old_url = "'http://' . \$_SERVER['HTTP_HOST'] . '/ability_app_main/'";
    $new_url = "'http://' . \$_SERVER['HTTP_HOST'] . '/ability_app_main/'";

    $updated_content = str_replace($old_url, $new_url, $content);

    // Also fix the duplicate definition
    $updated_content = preg_replace(
        '/\$base_url = .*?;\s*define\(\'BASE_URL\', \'http:\/\/\' . \$_SERVER\[\'HTTP_HOST\'\] . \'\/ability_app_main\/\'\);/s',
        'define(\'BASE_URL\', $protocol . \'://\' . $host . \'/ability_app_main/\');',
        $updated_content
    );

    // Write back
    file_put_contents($bootstrap_file, $updated_content);

    echo "<h2>✓ bootstrap.php Fixed!</h2>";
    echo "<p>Changed BASE_URL from /ability_app_main/ to /ability_app_main/</p>";

    // Show the fixed lines
    echo "<h3>Updated lines:</h3>";
    echo "<pre>";
    $lines = explode("\n", $updated_content);
    foreach ($lines as $num => $line) {
        if (strpos($line, 'BASE_URL') !== false) {
            echo ($num + 1) . ": " . htmlspecialchars($line) . "\n";
        }
    }
    echo "</pre>";
} else {
    echo "<h2>❌ bootstrap.php not found at: $bootstrap_file</h2>";
}
