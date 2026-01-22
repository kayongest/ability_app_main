<?php
// api/quick_search.php - UPDATED VERSION with item details
session_start();

require_once dirname(__DIR__) . '/includes/database_fix.php';
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json');

// Check authentication
if (!function_exists('isLoggedIn') || !isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}


$search = $_POST['search'] ?? '';

if (empty($search)) {
    echo json_encode(['success' => false, 'message' => 'Search term required']);
    exit();
}

try {
    $db = new DatabaseFix();
    $conn = $db->getConnection();

    // Get summary statistics for the item
    $summarySql = "
        SELECT 
            item_name,
            SUM(quantity) as total,
            SUM(CASE WHEN status = 'available' THEN quantity ELSE 0 END) as available,
            SUM(CASE WHEN status = 'in_use' THEN quantity ELSE 0 END) as in_use,
            SUM(CASE WHEN status = 'checked-out' THEN quantity ELSE 0 END) as checked_out,
            SUM(CASE WHEN status = 'maintenance' THEN quantity ELSE 0 END) as maintenance,
            SUM(CASE WHEN status = 'reserved' THEN quantity ELSE 0 END) as reserved
        FROM items 
        WHERE item_name LIKE ? 
        AND status NOT IN ('disposed', 'lost')
        GROUP BY item_name
        ORDER BY total DESC
        LIMIT 1
    ";

    $searchTerm = "%" . $search . "%";
    $stmt = $conn->prepare($summarySql);
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $item_name = $row['item_name'];
        
        // Get detailed information for "in_use" items including locations
        $detailsSql = "
            SELECT 
                id,
                serial_number,
                quantity,
                status,
                stock_location,
                current_location,
                department,
                notes,
                updated_at
            FROM items 
            WHERE item_name LIKE ?
            AND status = 'in_use'
            ORDER BY updated_at DESC
            LIMIT 10
        ";
        
        $detailsStmt = $conn->prepare($detailsSql);
        $detailsStmt->bind_param("s", $searchTerm);
        $detailsStmt->execute();
        $detailsResult = $detailsStmt->get_result();
        
        $in_use_items = [];
        while ($detailRow = $detailsResult->fetch_assoc()) {
            $in_use_items[] = [
                'id' => $detailRow['id'],
                'serial_number' => $detailRow['serial_number'],
                'quantity' => $detailRow['quantity'],
                'stock_location' => $detailRow['stock_location'],
                'current_location' => $detailRow['current_location'],
                'department' => $detailRow['department'],
                'notes' => $detailRow['notes'],
                'updated_at' => $detailRow['updated_at']
            ];
        }
        
        $detailsStmt->close();

        echo json_encode([
            'success' => true,
            'item_name' => $item_name,
            'total' => (int)$row['total'],
            'available' => (int)$row['available'],
            'in_use' => (int)$row['in_use'],
            'checked_out' => (int)$row['checked_out'],
            'maintenance' => (int)$row['maintenance'],
            'reserved' => (int)$row['reserved'],
            'in_use_items' => $in_use_items  // Detailed info for in-use items
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
    }

    $stmt->close();
    $db->close();
} catch (Exception $e) {
    error_log("Quick search error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

// After getting the response, add debug output
error_log("Search term: " . $search);
error_log("Found items: " . json_encode($in_use_items));
?>