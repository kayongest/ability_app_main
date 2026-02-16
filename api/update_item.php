<?php
// api/update_item.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$response = [
    'success' => false,
    'message' => ''
];

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Authentication required', 401);
    }

    // Check for POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('POST method required', 405);
    }

    // Get root directory
    $rootDir = realpath(dirname(__FILE__) . '/..');

    // Include database connection
    $dbFile = $rootDir . '/includes/db_connect.php';
    if (!file_exists($dbFile)) {
        throw new Exception('Database configuration not found');
    }

    require_once $dbFile;

    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();

    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Get form data
    $item_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $item_name = isset($_POST['item_name']) ? trim($_POST['item_name']) : '';
    $serial_number = isset($_POST['serial_number']) ? trim($_POST['serial_number']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $department = isset($_POST['department']) ? trim($_POST['department']) : '';
    $brand = isset($_POST['brand']) ? trim($_POST['brand']) : '';
    $model = isset($_POST['model']) ? trim($_POST['model']) : '';
    $brand_model = isset($_POST['brand_model']) ? trim($_POST['brand_model']) : '';
    $condition = isset($_POST['condition']) ? trim($_POST['condition']) : 'good';
    $stock_location = isset($_POST['stock_location']) ? trim($_POST['stock_location']) : '';
    $storage_location = isset($_POST['storage_location']) ? trim($_POST['storage_location']) : '';
    $current_location = isset($_POST['current_location']) ? trim($_POST['current_location']) : '';
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'available';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $specifications = isset($_POST['specifications']) ? trim($_POST['specifications']) : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    $tags = isset($_POST['tags']) ? trim($_POST['tags']) : '';

    // Validate required fields
    if ($item_id <= 0) {
        throw new Exception('Invalid item ID');
    }

    if (empty($item_name)) {
        throw new Exception('Item name is required');
    }

    if (empty($serial_number)) {
        throw new Exception('Serial number is required');
    }

    // Update item
    $sql = "UPDATE items SET 
                item_name = ?,
                serial_number = ?,
                category = ?,
                department = ?,
                brand = ?,
                model = ?,
                brand_model = ?,
                `condition` = ?,
                stock_location = ?,
                storage_location = ?,
                current_location = ?,
                quantity = ?,
                status = ?,
                description = ?,
                specifications = ?,
                notes = ?,
                tags = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param(
        "sssssssssssisssssi",
        $item_name,
        $serial_number,
        $category,
        $department,
        $brand,
        $model,
        $brand_model,
        $condition,
        $stock_location,
        $storage_location,
        $current_location,
        $quantity,
        $status,
        $description,
        $specifications,
        $notes,
        $tags,
        $item_id
    );

    if (!$stmt->execute()) {
        throw new Exception('Update failed: ' . $stmt->error);
    }

    // Handle accessories if provided
    if (isset($_POST['accessories']) && !empty($_POST['accessories'])) {
        // Delete existing accessories
        $delStmt = $conn->prepare("DELETE FROM item_accessories WHERE item_id = ?");
        $delStmt->bind_param("i", $item_id);
        $delStmt->execute();
        $delStmt->close();

        // Parse accessories
        $accessory_ids = json_decode($_POST['accessories'], true);

        if (is_array($accessory_ids) && count($accessory_ids) > 0) {
            // Insert new accessories
            $insStmt = $conn->prepare("INSERT INTO item_accessories (item_id, accessory_id) VALUES (?, ?)");
            foreach ($accessory_ids as $acc_id) {
                if (is_numeric($acc_id)) {
                    $insStmt->bind_param("ii", $item_id, $acc_id);
                    $insStmt->execute();
                }
            }
            $insStmt->close();
        }
    }

    $response['success'] = true;
    $response['message'] = 'Item updated successfully';

    $stmt->close();
    $db->close();
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
exit();
