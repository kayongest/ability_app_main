<?php
// test_connection.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

echo "=== Testing Database Connection ===\n\n";

// List all possible config paths
$possible_paths = [
    dirname(__DIR__) . '/config/database.php',
    '../config/database.php',
    '../../config/database.php',
    '../../../config/database.php',
    'config/database.php'
];

echo "Current directory: " . __DIR__ . "\n";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "\n\n";

foreach ($possible_paths as $path) {
    echo "Checking: $path\n";
    if (file_exists($path)) {
        echo "✓ File exists\n";
        
        // Check file contents
        $contents = file_get_contents($path);
        if (strpos($contents, '$pdo') !== false) {
            echo "✓ Contains \$pdo variable\n";
        } else {
            echo "✗ Does NOT contain \$pdo variable\n";
        }
        
        // Try to include it
        try {
            require_once $path;
            echo "✓ Successfully included\n";
            
            if (isset($pdo)) {
                echo "✓ \$pdo is set\n";
                if ($pdo instanceof PDO) {
                    echo "✓ \$pdo is a PDO instance\n";
                    
                    // Test query
                    try {
                        $stmt = $pdo->query("SELECT 1 as test");
                        $result = $stmt->fetch();
                        echo "✓ Database query successful: " . $result['test'] . "\n";
                    } catch (Exception $e) {
                        echo "✗ Database query failed: " . $e->getMessage() . "\n";
                    }
                } else {
                    echo "✗ \$pdo is NOT a PDO instance\n";
                }
            } else {
                echo "✗ \$pdo is NOT set after inclusion\n";
            }
        } catch (Exception $e) {
            echo "✗ Error including file: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✗ File does not exist\n";
    }
    echo "---\n";
}

echo "\n=== Checking Tables ===\n";

// If we have a connection, check tables
if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $tables = ['batches', 'users', 'batch_items', 'items'];
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                $result = $stmt->fetch();
                echo "✓ Table '$table' exists: " . $result['count'] . " rows\n";
            } catch (Exception $e) {
                echo "✗ Table '$table' error: " . $e->getMessage() . "\n";
            }
        }
    } catch (Exception $e) {
        echo "Error checking tables: " . $e->getMessage() . "\n";
    }
}
?>