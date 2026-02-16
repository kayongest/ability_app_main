<?php
// test_stats.php - Simple debug file
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Information</h2>";
echo "<pre>";

// Test 1: Check if we can find bootstrap.php
$rootDir = dirname(__FILE__);
echo "Root directory: " . $rootDir . "\n\n";

// Test 2: Check for bootstrap.php
$bootstrapPath = $rootDir . '/bootstrap.php';
echo "Bootstrap path: " . $bootstrapPath . "\n";
echo "Bootstrap exists: " . (file_exists($bootstrapPath) ? 'YES' : 'NO') . "\n\n";

// Test 3: Include bootstrap if it exists
if (file_exists($bootstrapPath)) {
    require_once $bootstrapPath;
    
    // Test 4: Check if functions exist
    echo "isLoggedIn function exists: " . (function_exists('isLoggedIn') ? 'YES' : 'NO') . "\n";
    
    // Test 5: Check database connection
    $dbConnectPath = $rootDir . '/includes/db_connect.php';
    if (file_exists($dbConnectPath)) {
        require_once $dbConnectPath;
        echo "db_connect.php included successfully\n";
        
        try {
            $db = getDatabase();
            echo "Database instance created: " . ($db ? 'YES' : 'NO') . "\n";
            
            if ($db) {
                $conn = $db->getConnection();
                echo "Database connection: " . ($conn ? 'YES' : 'NO') . "\n";
                
                // Test 6: Check if scans table exists
                $result = $conn->query("SHOW TABLES LIKE 'scans'");
                echo "Scans table exists: " . ($result && $result->num_rows > 0 ? 'YES' : 'NO') . "\n";
            }
        } catch (Exception $e) {
            echo "Database error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "db_connect.php NOT found at: " . $dbConnectPath . "\n";
    }
} else {
    echo "Bootstrap file not found!\n";
}

echo "</pre>";
?>