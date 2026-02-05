<?php
// ajax/get_batch_details.php - UPDATED to use batch_scans table
session_start();
header('Content-Type: application/json');

// Debug info
$debug_info = [
    'batch_id' => $_GET['batch_id'] ?? 'not provided',
    'timestamp' => date('Y-m-d H:i:s')
];

try {
    // Include database config
    require_once dirname(__DIR__) . '/config/database.php';

    // Get connection
    $conn = getConnection();

    // Get batch ID
    $batch_id = $conn->real_escape_string($_GET['batch_id'] ?? '');

    if (empty($batch_id)) {
        echo json_encode(['error' => 'Batch ID required', 'debug' => $debug_info]);
        exit;
    }

    // Check if batch_scans table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'batch_scans'");
    if ($table_check->num_rows === 0) {
        throw new Exception("The 'batch_scans' table does not exist in the database.");
    }

    // Get batch details from batch_scans table
    $sql = "
        SELECT 
            bs.*,
            u1.full_name as submitted_by_fullname,
            u1.username as submitted_by_username,
            u2.full_name as requested_by_fullname,
            u2.username as requested_by_username,
            u2.department as technician_department,
            u2.email as technician_email,
            u3.full_name as approved_by_fullname,
            u3.username as approved_by_username
        FROM batch_scans bs
        LEFT JOIN users u1 ON bs.submitted_by = u1.id
        LEFT JOIN users u2 ON bs.requested_by = u2.id
        LEFT JOIN users u3 ON bs.approved_by = u3.id
        WHERE bs.batch_id = '$batch_id' OR bs.id = '$batch_id'
        LIMIT 1
    ";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Query failed: " . $conn->error . " | SQL: " . $sql);
    }

    if ($result->num_rows === 0) {
        echo json_encode([
            'error' => 'Batch not found with ID: ' . htmlspecialchars($batch_id),
            'debug' => $debug_info,
            'sql' => $sql
        ]);
        exit;
    }

    $batch = $result->fetch_assoc();

    // Get batch items from batch_items table
    $items = [];
    $table_check = $conn->query("SHOW TABLES LIKE 'batch_items'");

    if ($table_check->num_rows > 0) {
        // Use the batch_id string (e.g., 'BATCH-20260205153301-7fe34a80')
        $batch_id_str = $batch['batch_id'];
        $sql_items = "
            SELECT bi.*, i.name as item_name, i.serial_number, i.category
            FROM batch_items bi
            LEFT JOIN items i ON bi.item_id = i.id
            WHERE bi.batch_id = '$batch_id_str'
            ORDER BY bi.id
        ";

        $result_items = $conn->query($sql_items);

        if ($result_items) {
            while ($row = $result_items->fetch_assoc()) {
                $items[] = $row;
            }
        }
    }

    // Close connection
    $conn->close();

    // Prepare response - map batch_scans columns to expected response format
    $response = [
        'id' => $batch['id'],
        'batch_id' => $batch['batch_id'],
        'batch_name' => $batch['batch_name'] ?? 'Batch ' . $batch['batch_id'],
        'submitted_by' => $batch['submitted_by'],
        'submitted_by_fullname' => $batch['submitted_by_fullname'] ?? 'Unknown',
        'submitted_by_username' => $batch['submitted_by_username'] ?? 'unknown',
        'requested_by' => $batch['requested_by'],
        'requested_by_fullname' => $batch['requested_by_fullname'] ?? 'N/A',
        'requested_by_username' => $batch['requested_by_username'] ?? 'unknown',
        'technician_department' => $batch['technician_department'] ?? 'Not specified',
        'technician_email' => $batch['technician_email'] ?? '',
        'approved_by' => $batch['approved_by'],
        'approved_by_fullname' => $batch['approved_by_fullname'] ?? 'Pending',
        'approved_by_username' => $batch['approved_by_username'] ?? '',
        'submitted_at' => $batch['submitted_at'],
        'approved_at' => $batch['approved_at'],
        'approval_status' => $batch['approval_status'] ?? 'pending',
        'job_sheet_number' => $batch['job_sheet_number'] ?? '',
        'location_applied' => $batch['location_applied'] ?? '',
        'project_manager' => $batch['project_manager'] ?? '',
        'vehicle_number' => $batch['vehicle_number'] ?? '',
        'driver_name' => $batch['driver_name'] ?? '',
        'destination' => $batch['destination'] ?? '',
        'notes' => $batch['notes'] ?? '',
        'total_items' => $batch['total_items'] ?? count($items),
        'unique_items' => $batch['unique_items'] ?? 0,
        'status' => $batch['status'] ?? 'completed',
        'action_applied' => $batch['action_applied'] ?? '',
        'items' => $items,
        'technician' => [
            'id' => $batch['requested_by'],
            'full_name' => $batch['requested_by_fullname'] ?? 'N/A',
            'username' => $batch['requested_by_username'] ?? 'unknown',
            'department' => $batch['technician_department'] ?? 'Not specified',
            'email' => $batch['technician_email'] ?? ''
        ],
        'debug' => array_merge($debug_info, [
            'table_used' => 'batch_scans',
            'batch_found' => 'yes',
            'items_count' => count($items)
        ])
    ];

    echo json_encode($response);
} catch (Exception $e) {
    // Return detailed error for debugging
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage(),
        'debug' => $debug_info,
        'suggestion' => 'Please check the batch_scans table exists and has data.'
    ]);
}
