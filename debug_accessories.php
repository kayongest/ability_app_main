<?php
// debug_accessories.php
session_start();
require_once 'includes/database_fix.php';
require_once 'includes/functions.php';

echo "<pre>";
echo "=== Accessory Debug Information ===\n\n";

try {
    $db = new DatabaseFix();
    $conn = $db->getConnection();
    
    // Check if tables exist
    echo "1. Checking database tables:\n";
    $tables = ['accessories', 'item_accessories', 'items'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        echo "   - $table: " . ($result->num_rows > 0 ? "✓ Exists" : "✗ Missing") . "\n";
    }
    
    echo "\n2. Checking accessories:\n";
    $result = $conn->query("SELECT COUNT(*) as count FROM accessories WHERE is_active = 1");
    $row = $result->fetch_assoc();
    echo "   - Active accessories: " . $row['count'] . "\n";
    
    echo "\n3. Checking items:\n";
    $result = $conn->query("SELECT COUNT(*) as count FROM items");
    $row = $result->fetch_assoc();
    echo "   - Total items: " . $row['count'] . "\n";
    
    echo "\n4. Checking API file:\n";
    $apiFile = 'api/accessories/bulk_assign.php';
    if (file_exists($apiFile)) {
        echo "   - API file exists: ✓\n";
        
        // Check file permissions
        $perms = fileperms($apiFile);
        echo "   - File permissions: " . substr(sprintf('%o', $perms), -4) . "\n";
    } else {
        echo "   - API file missing: ✗\n";
    }
    
    echo "\n5. Testing database connection:\n";
    echo "   - Connection: " . ($conn->ping() ? "✓ Working" : "✗ Failed") . "\n";
    
    echo "\n6. Testing sample query:\n";
    $testStmt = $conn->prepare("SELECT 1 as test");
    if ($testStmt) {
        $testStmt->execute();
        $result = $testStmt->get_result();
        $row = $result->fetch_assoc();
        echo "   - Query test: " . ($row['test'] == 1 ? "✓ Passed" : "✗ Failed") . "\n";
        $testStmt->close();
    } else {
        echo "   - Query test: ✗ Failed to prepare statement\n";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== End Debug ===\n";
echo "</pre>";
?>