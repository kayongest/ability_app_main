<?php
// api/quick_search.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize response
$response = [
    'success' => false,
    'items' => [],
    'count' => 0,
    'message' => ''
];

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Authentication required', 401);
    }

    // Get search term
    $search = isset($_GET['q']) ? trim($_GET['q']) : '';

    if (strlen($search) < 2) {
        $response['success'] = true;
        $response['message'] = 'Search term too short';
        echo json_encode($response);
        exit();
    }

    // Get database connection
    require_once '../config/database.php';

    // Use your database connection method
    // Option 1: If you have a Database class
    if (class_exists('Database')) {
        $db = new Database();
        $conn = $db->getConnection();
    }
    // Option 2: If you have a getConnection function
    else if (function_exists('getConnection')) {
        $conn = getConnection();
    }
    // Option 3: Direct connection
    else {
        $conn = new mysqli('localhost', 'root', '', 'ability_db');
    }

    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Prepare search term with wildcards
    $searchTerm = "%$search%";

    // Search in multiple fields
    $sql = "SELECT 
                id, 
                item_name, 
                serial_number, 
                category, 
                brand, 
                model, 
                description,
                status,
                `condition`,
                stock_location,
                quantity,
                image
            FROM items 
            WHERE item_name LIKE ? 
               OR serial_number LIKE ? 
               OR category LIKE ? 
               OR brand LIKE ? 
               OR model LIKE ? 
               OR description LIKE ?
            ORDER BY 
                CASE 
                    WHEN item_name LIKE ? THEN 1
                    WHEN serial_number LIKE ? THEN 2
                    WHEN category LIKE ? THEN 3
                    ELSE 4
                END,
                item_name ASC
            LIMIT 30";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    // Bind parameters - 9 parameters total (6 for WHERE, 3 for CASE)
    $stmt->bind_param(
        "sssssssss",
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm
    );

    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        // Ensure all fields exist
        $items[] = [
            'id' => (int)$row['id'],
            'item_name' => $row['item_name'] ?? '',
            'serial_number' => $row['serial_number'] ?? '',
            'category' => $row['category'] ?? '',
            'brand' => $row['brand'] ?? '',
            'model' => $row['model'] ?? '',
            'description' => $row['description'] ?? '',
            'status' => $row['status'] ?? 'unknown',
            'condition' => $row['condition'] ?? '',
            'stock_location' => $row['stock_location'] ?? '',
            'quantity' => (int)($row['quantity'] ?? 1),
            'image' => $row['image'] ?? ''
        ];
    }

    $response['success'] = true;
    $response['items'] = $items;
    $response['count'] = count($items);
    $response['message'] = 'Search completed';

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
exit();
