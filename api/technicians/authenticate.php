<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$technicianId = $data['technician_id'] ?? '';
$password = $data['password'] ?? '';

if (empty($technicianId) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Technician ID and password are required'
    ]);
    exit;
}

try {
    $conn = getConnection();

    // Check if it's a username or ID
    if (is_numeric($technicianId)) {
        $sql = "SELECT id, username, full_name, password, department 
                FROM technicians 
                WHERE id = ? AND is_active = 1";
    } else {
        $sql = "SELECT id, username, full_name, password, department 
                FROM technicians 
                WHERE username = ? AND is_active = 1";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $technicianId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Technician not found or inactive'
        ]);
        exit;
    }

    $technician = $result->fetch_assoc();

    // Verify password
    if (password_verify($password, $technician['password'])) {
        // Remove password from response
        unset($technician['password']);

        echo json_encode([
            'success' => true,
            'message' => 'Authentication successful',
            'technician' => $technician
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid password'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error authenticating: ' . $e->getMessage()
    ]);
}
