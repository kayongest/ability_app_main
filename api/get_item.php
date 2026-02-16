<?php
// api/get_item.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id) {
        throw new Exception('Item ID required');
    }
    
    $db = getConnection();
    
    // Get item details
    $stmt = $db->prepare("SELECT * FROM items WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Item not found');
    }
    
    $item = $result->fetch_assoc();
    
    // Get accessories for this item
    $accStmt = $db->prepare("
        SELECT a.id, a.name 
        FROM accessories a
        JOIN item_accessories ia ON a.id = ia.accessory_id
        WHERE ia.item_id = ?
    ");
    $accStmt->bind_param("i", $id);
    $accStmt->execute();
    $accResult = $accStmt->get_result();
    
    $accessories = [];
    $accessoryIds = [];
    while ($acc = $accResult->fetch_assoc()) {
        $accessories[] = $acc['name'];
        $accessoryIds[] = $acc['id'];
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $item['id'],
            'item_name' => $item['item_name'],
            'serial_number' => $item['serial_number'],
            'category' => $item['category'],
            'status' => $item['status'],
            'condition' => $item['condition'],
            'brand' => $item['brand'],
            'model' => $item['model'],
            'brand_model' => $item['brand_model'],
            'stock_location' => $item['stock_location'],
            'storage_location' => $item['storage_location'],
            'department' => $item['department'],
            'quantity' => $item['quantity'],
            'description' => $item['description'],
            'specifications' => $item['specifications'],
            'notes' => $item['notes'],
            'tags' => $item['tags'],
            'image' => $item['image'],
            'qr_code' => $item['qr_code'],
            'created_at' => $item['created_at'],
            'updated_at' => $item['updated_at'],
            'last_scanned' => $item['last_scanned'],
            'accessories' => $accessories,
            'accessory_ids' => $accessoryIds
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>