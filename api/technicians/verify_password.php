<?php
// api/technicians/verify_password.php
session_start();
header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['technician_id']) || !isset($data['password'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Technician ID and password required'
    ]);
    exit;
}

require_once '../../config/database.php';

$conn = getConnection();

try {
    $technician_id = $conn->real_escape_string($data['technician_id']);
    $password = $data['password'];

    // Get user from users table
    $sql = "SELECT id, username, full_name, password, email, department, role, is_active 
            FROM users 
            WHERE (id = '$technician_id' OR username = '$technician_id') 
              AND is_active = 1";

    $result = $conn->query($sql);

    if (!$result || $result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Technician not found or inactive'
        ]);
        exit;
    }

    $user = $result->fetch_assoc();

    // Verify password
    if (password_verify($password, $user['password'])) {
        // Password is correct
        echo json_encode([
            'success' => true,
            'message' => 'Authentication successful',
            'technician' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'] ?? $user['username'],
                'email' => $user['email'] ?? '',
                'department' => $user['department'] ?? 'Not specified',
                'role' => $user['role'] ?? 'technician',
                'is_active' => $user['is_active']
            ]
        ]);
    } else {
        // Password incorrect
        echo json_encode([
            'success' => false,
            'message' => 'Invalid password'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} finally {
    $conn->close();
}
