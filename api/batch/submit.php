<?php
// api/batch/submit.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session and check authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get the root path
$rootPath = dirname(dirname(dirname(__FILE__)));

// Include required files
require_once $rootPath . '/includes/database_fix.php';  // ADD THIS LINE
require_once $rootPath . '/includes/db_connect.php';
require_once $rootPath . '/includes/functions.php';

// Get database connection
try {
    $db = new DatabaseFix();
    $conn = $db->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['items']) || !is_array($input['items'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    $userId = $_SESSION['user_id'];
    $userName = $_SESSION['username'] ?? 'Unknown User';

    // Generate batch ID
    $batchId = 'BATCH-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
    $batchName = isset($input['notes']) && !empty($input['notes'])
        ? substr($input['notes'], 0, 50)
        : 'Batch ' . date('Y-m-d H:i');

    $actionApplied = $input['action'] ?? null;
    $locationApplied = $input['location'] ?? null;
    $batchNotes = $input['notes'] ?? null;

    // Calculate statistics
    $totalItems = 0;
    $uniqueItems = count($input['items']);
    $availableItems = 0;
    $inUseItems = 0;
    $maintenanceItems = 0;
    $categories = [];

    foreach ($input['items'] as $item) {
        $quantity = $item['quantity'] ?? 1;
        $totalItems += $quantity;

        $status = $item['status'] ?? 'available';
        switch ($status) {
            case 'available':
                $availableItems++;
                break;
            case 'in_use':
                $inUseItems++;
                break;
            case 'maintenance':
                $maintenanceItems++;
                break;
        }

        if (!empty($item['category'])) {
            $categories[] = $item['category'];
        }
    }

    $categoriesCount = count(array_unique($categories));

    // Check if batch_scans table exists, create if not
    checkAndCreateTables($conn);

    // 1. Insert batch header
    $batchStmt = $conn->prepare("
        INSERT INTO batch_scans (
            batch_id, batch_name, total_items, unique_items, submitted_by,
            action_applied, location_applied, notes, status, submitted_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'completed', NOW())
    ");

    if (!$batchStmt) {
        throw new Exception("Failed to prepare batch statement: " . $conn->error);
    }

    $batchStmt->bind_param(
        "ssiiisss",
        $batchId,
        $batchName,
        $totalItems,
        $uniqueItems,
        $userId,
        $actionApplied,
        $locationApplied,
        $batchNotes
    );

    if (!$batchStmt->execute()) {
        throw new Exception("Failed to create batch: " . $batchStmt->error);
    }
    $batchStmt->close();

    // 2. Insert batch items
    $itemStmt = $conn->prepare("
        INSERT INTO batch_items (
            batch_id, item_id, item_name, serial_number, category,
            original_status, new_status, original_location, new_location,
            quantity, scanned_at, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$itemStmt) {
        throw new Exception("Failed to prepare item statement: " . $conn->error);
    }

    foreach ($input['items'] as $item) {
        $itemId = $item['id'] ?? $item['item_id'] ?? 0;
        $itemName = $item['name'] ?? $item['item_name'] ?? 'Unknown Item';
        $serialNumber = $item['serial_number'] ?? $item['serial'] ?? null;
        $category = $item['category'] ?? $item['category_name'] ?? null;
        $originalStatus = $item['original_status'] ?? $item['status'] ?? 'available';

        // Apply batch action if specified
        $newStatus = $actionApplied ? getStatusFromAction($actionApplied) : $originalStatus;
        $originalLocation = $item['stock_location'] ?? $item['location'] ?? null;
        $newLocation = $locationApplied ?: $originalLocation;
        $quantity = $item['quantity'] ?? 1;
        $scannedAt = $item['added_at'] ?? date('Y-m-d H:i:s');
        $notes = $item['notes'] ?? null;

        $itemStmt->bind_param(
            "sisssssssiss",
            $batchId,
            $itemId,
            $itemName,
            $serialNumber,
            $category,
            $originalStatus,
            $newStatus,
            $originalLocation,
            $newLocation,
            $quantity,
            $scannedAt,
            $notes
        );

        if (!$itemStmt->execute()) {
            throw new Exception("Failed to insert item: " . $itemStmt->error);
        }

        // 3. Update equipment status in main items table if needed
        if ($actionApplied && $itemId > 0) {
            updateEquipmentStatus($conn, $itemId, $newStatus, $newLocation, $userId);
        }
    }
    $itemStmt->close();

    // 4. Insert batch statistics
    $statsStmt = $conn->prepare("
        INSERT INTO batch_statistics (
            batch_id, total_items, available_items, in_use_items,
            maintenance_items, categories_count
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");

    if ($statsStmt) {
        $statsStmt->bind_param(
            "siiiii",
            $batchId,
            $totalItems,
            $availableItems,
            $inUseItems,
            $maintenanceItems,
            $categoriesCount
        );

        if (!$statsStmt->execute()) {
            // Don't throw error, just log it
            error_log("Failed to save statistics: " . $statsStmt->error);
        }
        $statsStmt->close();
    }

    // 5. Log batch action (if table exists)
    $actionStmt = $conn->prepare("
        INSERT INTO batch_actions_log (
            batch_id, user_id, action_type, action_details,
            ip_address, user_agent, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");

    if ($actionStmt) {
        $actionType = $actionApplied ? 'batch_' . $actionApplied : 'batch_submit';
        $actionDetails = json_encode([
            'total_items' => $totalItems,
            'unique_items' => $uniqueItems,
            'action_applied' => $actionApplied,
            'location_applied' => $locationApplied
        ]);
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        $actionStmt->bind_param(
            "sissss",
            $batchId,
            $userId,
            $actionType,
            $actionDetails,
            $ipAddress,
            $userAgent
        );

        if (!$actionStmt->execute()) {
            // Don't throw error, just log it
            error_log("Failed to log action: " . $actionStmt->error);
        }
        $actionStmt->close();
    }

    // Commit transaction
    $conn->commit();

    // Clear localStorage data (send instruction to frontend)
    $response = [
        'success' => true,
        'message' => 'Batch submitted successfully!',
        'batch_id' => $batchId,
        'batch_name' => $batchName,
        'total_items' => $totalItems,
        'unique_items' => $uniqueItems,
        'clear_storage' => true,
        'processed_items' => count($input['items'])
    ];

    echo json_encode($response);
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollback();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error submitting batch: ' . $e->getMessage()
    ]);
    
    // Log error
    error_log("Batch submission error: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

/**
 * Get new status based on batch action
 */
function getStatusFromAction($action)
{
    $statusMap = [
        'check_in' => 'available',
        'check_out' => 'in_use',
        'maintenance' => 'maintenance',
        'available' => 'available'
    ];

    return $statusMap[$action] ?? 'available';
}

/**
 * Update equipment status in main items table
 */
function updateEquipmentStatus($conn, $itemId, $newStatus, $newLocation, $userId)
{
    try {
        // Check if items table exists
        $checkStmt = $conn->prepare("SHOW TABLES LIKE 'items'");
        
        if ($checkStmt->execute()) {
            $result = $checkStmt->get_result();
            if ($result->num_rows > 0) {
                // Update items table if it exists
                $updateStmt = $conn->prepare("
                    UPDATE items 
                    SET status = ?, 
                        stock_location = ?,
                        updated_at = NOW(),
                        updated_by = ?
                    WHERE id = ? OR item_id = ?
                ");

                if ($updateStmt) {
                    $updateStmt->bind_param(
                        "ssiii",
                        $newStatus,
                        $newLocation,
                        $userId,
                        $itemId,
                        $itemId
                    );

                    $updateStmt->execute();
                    $updateStmt->close();
                }
            }
        }
        $checkStmt->close();
    } catch (Exception $e) {
        // Silently fail - not critical
        error_log("updateEquipmentStatus error: " . $e->getMessage());
    }
}

/**
 * Check and create necessary tables if they don't exist
 */
function checkAndCreateTables($conn)
{
    $tables = [
        "CREATE TABLE IF NOT EXISTS batch_scans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            batch_id VARCHAR(50) UNIQUE NOT NULL,
            batch_name VARCHAR(255) NOT NULL,
            total_items INT DEFAULT 0,
            unique_items INT DEFAULT 0,
            submitted_by INT,
            action_applied VARCHAR(50),
            location_applied VARCHAR(255),
            notes TEXT,
            status VARCHAR(20) DEFAULT 'completed',
            submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_batch_id (batch_id),
            INDEX idx_submitted_at (submitted_at),
            INDEX idx_submitted_by (submitted_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        
        "CREATE TABLE IF NOT EXISTS batch_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            batch_id VARCHAR(50) NOT NULL,
            item_id INT NOT NULL,
            item_name VARCHAR(255) NOT NULL,
            serial_number VARCHAR(100),
            category VARCHAR(100),
            original_status VARCHAR(20),
            new_status VARCHAR(20),
            original_location VARCHAR(255),
            new_location VARCHAR(255),
            quantity INT DEFAULT 1,
            scanned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            notes TEXT,
            INDEX idx_batch_id (batch_id),
            INDEX idx_item_id (item_id),
            INDEX idx_serial (serial_number),
            FOREIGN KEY (batch_id) REFERENCES batch_scans(batch_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        
        "CREATE TABLE IF NOT EXISTS batch_statistics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            batch_id VARCHAR(50) UNIQUE NOT NULL,
            total_items INT DEFAULT 0,
            available_items INT DEFAULT 0,
            in_use_items INT DEFAULT 0,
            maintenance_items INT DEFAULT 0,
            categories_count INT DEFAULT 0,
            INDEX idx_batch_id (batch_id),
            FOREIGN KEY (batch_id) REFERENCES batch_scans(batch_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    ];

    foreach ($tables as $sql) {
        try {
            $conn->query($sql);
        } catch (Exception $e) {
            error_log("Table creation error: " . $e->getMessage());
        }
    }
}