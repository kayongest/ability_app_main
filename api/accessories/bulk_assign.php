<?php
// api/accessories/bulk_assign.php
session_start();

// Include required files
require_once __DIR__ . '/../../includes/database_fix.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new DatabaseFix();
        $conn = $db->getConnection();

        // Check if transactions are supported
        $conn->begin_transaction();

        // Get data
        $accessories = $_POST['accessories'] ?? [];
        $items = $_POST['items'] ?? [];
        $mode = $_POST['mode'] ?? 'add';

        // Validate data
        if (empty($accessories) || !is_array($accessories)) {
            throw new Exception('No accessories selected');
        }

        if (empty($items) || !is_array($items)) {
            throw new Exception('No equipment items selected');
        }

        // Clean and validate accessory IDs
        $accessory_ids = [];
        foreach ($accessories as $acc_id) {
            $acc_id = intval($acc_id);
            if ($acc_id > 0) {
                $accessory_ids[] = $acc_id;
            }
        }

        if (empty($accessory_ids)) {
            throw new Exception('Invalid accessory selection');
        }

        // Clean and validate item IDs
        $item_ids = [];
        foreach ($items as $item_id) {
            $item_id = intval($item_id);
            if ($item_id > 0) {
                $item_ids[] = $item_id;
            }
        }

        if (empty($item_ids)) {
            throw new Exception('Invalid item selection');
        }

        $assigned_count = 0;
        $skipped_count = 0;

        // Process each item
        foreach ($item_ids as $item_id) {
            if ($mode === 'replace') {
                // Remove existing accessories for this item
                $deleteStmt = $conn->prepare("
                    DELETE FROM item_accessories WHERE item_id = ?
                ");
                $deleteStmt->bind_param("i", $item_id);
                $deleteStmt->execute();
                $deleteStmt->close();
            }

            // Assign new accessories
            foreach ($accessory_ids as $acc_id) {
                // Check if already assigned
                $checkStmt = $conn->prepare("
                    SELECT id FROM item_accessories 
                    WHERE item_id = ? AND accessory_id = ?
                ");
                $checkStmt->bind_param("ii", $item_id, $acc_id);
                $checkStmt->execute();
                $checkStmt->store_result();

                if ($checkStmt->num_rows === 0) {
                    // Assign accessory
                    $assignStmt = $conn->prepare("
                        INSERT INTO item_accessories (item_id, accessory_id, created_at)
                        VALUES (?, ?, NOW())
                    ");
                    $assignStmt->bind_param("ii", $item_id, $acc_id);

                    if ($assignStmt->execute()) {
                        $assigned_count++;
                    } else {
                        error_log("Failed to assign accessory $acc_id to item $item_id: " . $assignStmt->error);
                    }
                    $assignStmt->close();
                } else {
                    $skipped_count++;
                }
                $checkStmt->close();
            }
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => "Successfully assigned accessories to " . count($item_ids) . " items. " .
                "$assigned_count new assignments made, $skipped_count already existed.",
            'assigned_count' => $assigned_count,
            'skipped_count' => $skipped_count
        ]);

        $db->close();
    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($conn) && method_exists($conn, 'rollback')) {
            $conn->rollback();
        }

        error_log("Bulk assign error: " . $e->getMessage());

        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
