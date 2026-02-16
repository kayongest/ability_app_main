<?php
// api/get_item.php - Complete working version
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Authentication required. Please login.', 401);
    }

    // Check for GET request
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('GET method required', 405);
    }

    // Get item ID
    $item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($item_id <= 0) {
        throw new Exception('Invalid item ID');
    }

    // Get root directory
    $rootDir = realpath(dirname(__FILE__) . '/..');

    // Include database connection
    $dbFile = $rootDir . '/includes/db_connect.php';
    $funcFile = $rootDir . '/includes/functions.php';

    if (!file_exists($dbFile)) {
        throw new Exception('Database configuration not found');
    }

    require_once $dbFile;
    require_once $funcFile;

    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();

    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Get item details
    $sql = "SELECT 
                i.*,
                GROUP_CONCAT(DISTINCT a.name SEPARATOR ', ') as accessory_names
            FROM items i
            LEFT JOIN item_accessories ia ON i.id = ia.item_id
            LEFT JOIN accessories a ON ia.accessory_id = a.id
            WHERE i.id = ?
            GROUP BY i.id";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Format data - FIXED: Removed duplicate description
        $itemData = [
            'id' => $row['id'],
            'item_name' => $row['item_name'] ?? '',
            'serial_number' => $row['serial_number'] ?? '',
            'category' => $row['category'] ?? '',
            'department' => $row['department'] ?? '',
            'description' => $row['description'] ?? '',  // Keep only ONE description field
            'brand' => $row['brand'] ?? '',
            'model' => $row['model'] ?? '',
            'brand_model' => $row['brand_model'] ?? '',
            'condition' => $row['condition'] ?? 'good',
            'stock_location' => $row['stock_location'] ?? '',
            'storage_location' => $row['storage_location'] ?? '',  // ADD THIS
            'quantity' => $row['quantity'] ?? 1,
            'status' => $row['status'] ?? 'available',
            'notes' => $row['notes'] ?? '',
            'specifications' => $row['specifications'] ?? '',      // ADD THIS
            'tags' => $row['tags'] ?? '',                         // ADD THIS
            'image' => $row['image'] ?? '',
            'qr_code' => $row['qr_code'] ?? '',
            'accessories' => $row['accessory_names'] ?? '',
            'accessory_ids' => [],                                // ADD THIS
            'created_at' => $row['created_at'] ?? '',
            'updated_at' => $row['updated_at'] ?? ''
        ];

        // Fetch accessory_ids separately
        $accessoryIdsSql = "SELECT accessory_id FROM item_accessories WHERE item_id = ?";
        $accStmt = $conn->prepare($accessoryIdsSql);
        if ($accStmt) {
            $accStmt->bind_param("i", $item_id);
            $accStmt->execute();
            $accResult = $accStmt->get_result();
            $accessoryIds = [];
            while ($accRow = $accResult->fetch_assoc()) {
                $accessoryIds[] = $accRow['accessory_id'];
            }
            $itemData['accessory_ids'] = $accessoryIds;
            $accStmt->close();
        }

        $response['success'] = true;
        $response['data'] = $itemData;
        $response['message'] = 'Item loaded successfully';
    } else {
        throw new Exception('Item not found');
    }

    $stmt->close();
    $db->close();
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    $response['code'] = $e->getCode();

    // Set appropriate HTTP status
    $httpCode = $e->getCode() && $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($httpCode);
}

// Output JSON
echo json_encode($response);
exit();
?>