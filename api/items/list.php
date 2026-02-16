<?php
// api/items/list.php - FIXED VERSION
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    // Get parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'id';
    $order = isset($_GET['order']) ? $_GET['order'] : 'desc';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $category = isset($_GET['category']) ? $_GET['category'] : '';

    // Validate parameters
    $page = max(1, $page);
    $limit = max(1, min(100, $limit));
    $offset = ($page - 1) * $limit;

    // Allowed sort columns - UPDATED to match your database
    $allowedSort = ['id', 'item_name', 'category', 'quantity', 'status', 'created_at'];
    $sort = in_array($sort, $allowedSort) ? $sort : 'id';
    $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

    // Get database connection
    $db = getConnection();

    if (!$db) {
        throw new Exception('Database connection failed');
    }

    // Build WHERE clause - UPDATED to use your actual column names
    $whereConditions = [];
    $params = [];
    $types = "";

    if (!empty($search)) {
        $whereConditions[] = "(item_name LIKE ? OR serial_number LIKE ? OR description LIKE ? OR brand LIKE ? OR model LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "sssss";
    }

    if (!empty($category)) {
        $whereConditions[] = "category = ?";
        $params[] = $category;
        $types .= "s";
    }

    $whereClause = empty($whereConditions) ? "" : "WHERE " . implode(" AND ", $whereConditions);

    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM items $whereClause";
    $countStmt = $db->prepare($countQuery);

    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }

    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalItems = $countResult->fetch_assoc()['total'];
    $totalPages = $totalItems > 0 ? ceil($totalItems / $limit) : 1;

    // Get items for current page - UPDATED to use correct column names
    $query = "SELECT * FROM items $whereClause ORDER BY $sort $order LIMIT ? OFFSET ?";
    $stmt = $db->prepare($query);

    // Add limit and offset to params
    $paramsWithLimit = $params;
    $typesWithLimit = $types;
    $paramsWithLimit[] = $limit;
    $paramsWithLimit[] = $offset;
    $typesWithLimit .= "ii";

    if (!empty($paramsWithLimit)) {
        $stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        // Format the data for frontend - map to expected field names
        $items[] = [
            'id' => $row['id'],
            'item_name' => $row['item_name'],
            'serial_number' => $row['serial_number'],
            'category' => $row['category'],
            'brand' => $row['brand'],
            'model' => $row['model'],
            'brand_model' => $row['brand_model'],
            'description' => $row['description'],
            'status' => $row['status'],
            'condition' => $row['condition'],
            'stock_location' => $row['stock_location'],
            'current_location' => $row['current_location'],
            'quantity' => $row['quantity'],
            'image' => $row['image'],
            'qr_code' => $row['qr_code'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }

    // Return response - NOTE: using 'items' not 'data' to match frontend
    echo json_encode([
        'success' => true,
        'items' => $items,  // Changed from 'data' to 'items'
        'pagination' => [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'itemsPerPage' => $limit,
            'hasNext' => $page < $totalPages,
            'hasPrev' => $page > 1
        ],
        'filters' => [
            'search' => $search,
            'category' => $category,
            'sort' => $sort,
            'order' => $order
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Items API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>