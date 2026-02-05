<?php
header('Content-Type: application/json');
require_once '../config/database.php';
session_start();

// Check if user is logged in as stock controller
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'stock_controller') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$technicianId = $data['technician_id'] ?? '';
$password = $data['password'] ?? '';

if (empty($technicianId) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Method 1: If you have hashed passwords (recommended)
    $stmt = $pdo->prepare("SELECT id, username, full_name, department, email, position, password_hash FROM technicians WHERE (id = ? OR username = ?) AND active = 1");
    $stmt->execute([$technicianId, $technicianId]);
    $technician = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($technician && password_verify($password, $technician['password_hash'])) {
        // Remove password hash from response
        unset($technician['password_hash']);

        echo json_encode([
            'success' => true,
            'message' => 'Password verified',
            'technician' => $technician
        ]);
    } else {
        // Method 2: If you have plain text passwords (for testing)
        $stmt = $pdo->prepare("SELECT id, username, full_name, department, email, position FROM technicians WHERE (id = ? OR username = ?) AND password = ? AND active = 1");
        $stmt->execute([$technicianId, $technicianId, $password]);
        $technician = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($technician) {
            echo json_encode([
                'success' => true,
                'message' => 'Password verified (plain text)',
                'technician' => $technician
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid technician ID or password']);
        }
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
