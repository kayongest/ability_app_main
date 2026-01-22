<?php
// api/quick_search.php - FIXED VERSION
session_start();

// Include required files
require_once dirname(__DIR__) . '/includes/database_fix.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Check authentication
if (!function_exists('isLoggedIn') || !isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get search term
$search = $_POST['search'] ?? '';

if (empty($search)) {
    echo json_encode(['success' => false, 'message' => 'Search term required']);
    exit();
}

try {
    $db = new DatabaseFix();
    $conn = $db->getConnection();
    
    // SIMPLIFIED VERSION - Gets all statuses dynamically
    // SIMPLE VERSION - Just get totals by status
$sql = "
    SELECT 
        item_name,
        SUM(quantity) as total,
        GROUP_CONCAT(CONCAT(status, ':', quantity) SEPARATOR ',') as status_details
    FROM items 
    WHERE item_name LIKE ? 
    AND status NOT IN ('disposed', 'lost')
    GROUP BY item_name
    LIMIT 1
";

// Then parse the status_details in PHP
if ($row = $result->fetch_assoc()) {
    $status_details = [];
    $pairs = explode(',', $row['status_details']);
    foreach ($pairs as $pair) {
        list($status, $quantity) = explode(':', $pair);
        $status_details[$status] = (int)$quantity;
    }
    
    echo json_encode([
        'success' => true,
        'item_name' => $row['item_name'],
        'total' => (int)$row['total'],
        'status_details' => $status_details
    ]);
}
    
    // Prepare the statement
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("SQL prepare error: " . $conn->error);
    }
    
    $searchTerm = "%" . $search . "%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    $total = 0;
    $item_name = '';
    
    while ($row = $result->fetch_assoc()) {
        if (empty($item_name)) {
            $item_name = $row['item_name'];
        }
        
        $status = $row['status'];
        $count = (int)$row['status_count'];
        $total += $count;
        
        // Map status names for consistency
        $status_key = str_replace(['-', ' '], '_', strtolower($status));
        
        if (!isset($items[$status_key])) {
            $items[$status_key] = 0;
        }
        $items[$status_key] += $count;
    }
    
    if (!empty($item_name)) {
        // Return with all statuses found
        echo json_encode([
            'success' => true,
            'item_name' => $item_name,
            'total' => $total,
            'available' => $items['available'] ?? 0,
            'in_use' => $items['in_use'] ?? 0,
            'checked_out' => ($items['checked_out'] ?? 0) + ($items['checkedout'] ?? 0) + ($items['checked_out'] ?? 0),
            'maintenance' => $items['maintenance'] ?? 0,
            'reserved' => $items['reserved'] ?? 0
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
    }
    
    $stmt->close();
    $db->close();
    
} catch (Exception $e) {
    error_log("Quick search error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Search error: ' . $e->getMessage()]);
}
?>