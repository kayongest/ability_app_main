<?php
// api/items/list.php - FIXED VERSION WITH SORTING
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Get the root directory path
$rootDir = realpath(__DIR__ . '/../..');

// Simple response
$response = [
    'success' => false,
    'message' => '',
    'items' => [],
    'total' => 0,
    'count' => 0,
    'page' => 1,
    'total_pages' => 0,
    'limit' => 10
];

try {
    // Include required files
    require_once $rootDir . '/includes/db_connect.php';
    require_once $rootDir . '/includes/functions.php';

    // Simple session check
    session_start();
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not logged in', 401);
    }

    // Get database connection using YOUR Database class
    $db = new Database();
    $conn = $db->getConnection();

    if (!$conn) {
        throw new Exception('Could not establish database connection');
    }

    // Get query parameters with defaults
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, min(100, intval($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

    // Get sorting parameters
    $sortField = $_GET['sort'] ?? 'id';
    $sortOrder = strtoupper($_GET['order'] ?? 'DESC');

    // Validate sort field to prevent SQL injection
    $allowedSortFields = ['id', 'item_name', 'serial_number', 'category', 'status', 'stock_location', 'created_at', 'updated_at'];
    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'id';
    }

    // Validate sort order
    $sortOrder = ($sortOrder === 'ASC') ? 'ASC' : 'DESC';

    // Build WHERE clause from filters
    $whereConditions = [];
    $params = [];
    $types = '';

    // Category filter
    if (!empty($_GET['category'])) {
        $whereConditions[] = "category = ?";
        $params[] = $_GET['category'];
        $types .= 's';
    }

    // Status filter
    if (!empty($_GET['status'])) {
        $whereConditions[] = "status = ?";
        $params[] = $_GET['status'];
        $types .= 's';
    }

    // Location filter - Use either stock_location or storage_location
    if (!empty($_GET['location'])) {
        $whereConditions[] = "(stock_location = ? OR storage_location = ?)";
        $params[] = $_GET['location'];
        $params[] = $_GET['location'];
        $types .= 'ss';
    }

    // Search filter
    if (!empty($_GET['search'])) {
        $search = "%{$_GET['search']}%";
        $whereConditions[] = "(item_name LIKE ? OR serial_number LIKE ? OR description LIKE ? OR brand_model LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $types .= 'ssss';
    }

    // Build WHERE clause
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = "WHERE " . implode(" AND ", $whereConditions);
    }

    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM items $whereClause";
    $countStmt = $conn->prepare($countSql);

    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }

    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalRow = $countResult->fetch_assoc();
    $totalItems = $totalRow['total'] ?? 0;
    $countStmt->close();

    // Get items with pagination and sorting
    $paramsForSelect = $params;
    $typesForSelect = $types;
    
    // Build the SQL query with sorting
    $sql = "SELECT * FROM items $whereClause ORDER BY $sortField $sortOrder";
    
    if ($limit > 0) {
        $sql .= " LIMIT ? OFFSET ?";
        $paramsForSelect[] = $limit;
        $paramsForSelect[] = $offset;
        $typesForSelect .= 'ii';
    }

    $stmt = $conn->prepare($sql);
    if (!empty($paramsForSelect)) {
        $stmt->bind_param($typesForSelect, ...$paramsForSelect);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];

    while ($row = $result->fetch_assoc()) {
        // Format the data for display
        $items[] = [
            'id' => $row['id'],
            'name' => $row['item_name'],
            'serial_number' => $row['serial_number'],
            'category' => $row['category'],
            'description' => $row['description'],
            'status' => $row['status'],
            'stock_location' => $row['stock_location'] ?: $row['storage_location'],
            'value' => null,
            'updated_at' => $row['updated_at'] ?? $row['created_at'],
            'quantity' => $row['quantity'],
            'brand_model' => $row['brand_model'],
            'condition' => $row['condition'],
            'qr_code' => $row['qr_code'] ?? null
        ];
    }

    $stmt->close();

    // Calculate total pages
    $totalPages = $limit > 0 ? ceil($totalItems / $limit) : 1;

    // Build success response
    $response['success'] = true;
    $response['message'] = 'Items loaded successfully';
    $response['items'] = $items;
    $response['total'] = $totalItems;
    $response['count'] = count($items);
    $response['page'] = $page;
    $response['total_pages'] = $totalPages;
    $response['limit'] = $limit;
    $response['sort'] = $sortField;
    $response['order'] = $sortOrder;

    $db->close();
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response, JSON_PRETTY_PRINT);
exit();