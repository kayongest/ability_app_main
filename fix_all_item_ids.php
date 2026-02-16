<?php
require_once 'includes/database_fix.php';

echo "<h2>Fixing Item IDs in Database</h2>";

try {
    $db = new DatabaseFix();
    $conn = $db->getConnection();
    
    echo "<p>Starting fix process...</p>";
    
    // 1. Check current state
    $checkResult = $conn->query("SELECT COUNT(*) as total, MIN(id) as min_id, MAX(id) as max_id FROM items");
    $checkRow = $checkResult->fetch_assoc();
    
    echo "<p>Current state: {$checkRow['total']} items, Min ID: {$checkRow['min_id']}, Max ID: {$checkRow['max_id']}</p>";
    
    // 2. Check for items with ID = 0
    $zeroIdResult = $conn->query("SELECT COUNT(*) as zero_count FROM items WHERE id = 0");
    $zeroRow = $zeroIdResult->fetch_assoc();
    
    echo "<p>Items with ID = 0: {$zeroRow['zero_count']}</p>";
    
    if ($zeroRow['zero_count'] > 0) {
        echo "<p>Fixing IDs...</p>";
        
        // 3. Create a temporary table with proper IDs
        $conn->query("CREATE TEMPORARY TABLE temp_items AS 
            SELECT 
                ROW_NUMBER() OVER (ORDER BY created_at, serial_number) as new_id,
                item_name, serial_number, category, brand, model, department,
                description, specifications, brand_model, condition, 
                stock_location, storage_location, notes, quantity, status,
                image, qr_code, created_at, updated_at, tags, 
                last_scanned, current_location
            FROM items 
            ORDER BY created_at, serial_number");
        
        // 4. Backup original table
        $conn->query("CREATE TABLE items_backup_" . date('Ymd_His') . " LIKE items");
        $conn->query("INSERT INTO items_backup_" . date('Ymd_His') . " SELECT * FROM items");
        
        echo "<p>Backup created: items_backup_" . date('Ymd_His') . "</p>";
        
        // 5. Truncate original table (empty it)
        $conn->query("TRUNCATE TABLE items");
        
        // 6. Insert fixed data
        $conn->query("INSERT INTO items (
            id, item_name, serial_number, category, brand, model, department,
            description, specifications, brand_model, condition, 
            stock_location, storage_location, notes, quantity, status,
            image, qr_code, created_at, updated_at, tags, 
            last_scanned, current_location
        ) SELECT * FROM temp_items");
        
        // 7. Set AUTO_INCREMENT
        $maxIdResult = $conn->query("SELECT MAX(id) as max_id FROM items");
        $maxRow = $maxIdResult->fetch_assoc();
        $nextId = $maxRow['max_id'] + 1;
        
        $conn->query("ALTER TABLE items AUTO_INCREMENT = $nextId");
        
        // 8. Drop temporary table
        $conn->query("DROP TEMPORARY TABLE temp_items");
        
        // 9. Verify fix
        $verifyResult = $conn->query("SELECT COUNT(*) as total, MIN(id) as min_id, MAX(id) as max_id FROM items");
        $verifyRow = $verifyResult->fetch_assoc();
        
        echo "<p style='color: green; font-weight: bold;'>✅ Fix completed!</p>";
        echo "<p>New state: {$verifyRow['total']} items, Min ID: {$verifyRow['min_id']}, Max ID: {$verifyRow['max_id']}</p>";
        echo "<p>AUTO_INCREMENT set to: $nextId</p>";
        
        // 10. Check for duplicates
        $duplicateCheck = $conn->query("
            SELECT id, COUNT(*) as count 
            FROM items 
            GROUP BY id 
            HAVING count > 1
        ");
        
        if ($duplicateCheck->num_rows > 0) {
            echo "<p style='color: red;'>Warning: Found duplicate IDs!</p>";
            while ($dupRow = $duplicateCheck->fetch_assoc()) {
                echo "<p>ID {$dupRow['id']} appears {$dupRow['count']} times</p>";
            }
        } else {
            echo "<p style='color: green;'>✓ All IDs are unique</p>";
        }
        
    } else {
        echo "<p style='color: green;'>No items with ID = 0 found. Database is already correct.</p>";
    }
    
    // 11. Show sample of items
    echo "<h3>Sample of Items (first 10):</h3>";
    $sampleResult = $conn->query("SELECT id, item_name, serial_number FROM items ORDER BY id LIMIT 10");
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Item Name</th><th>Serial Number</th></tr>";
    while ($sampleRow = $sampleResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$sampleRow['id']}</td>";
        echo "<td>{$sampleRow['item_name']}</td>";
        echo "<td>{$sampleRow['serial_number']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Run this script once to fix all IDs</li>";
    echo "<li>After fixing IDs, regenerate QR codes (they should now contain correct IDs)</li>";
    echo "<li>Test batch submission - it should now work properly</li>";
    echo "</ol>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection and table structure.</p>";
}
?>