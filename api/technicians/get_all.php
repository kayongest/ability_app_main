<?php
header('Content-Type: application/json');
require_once '../config/database.php';
session_start();

// Check if user is logged in as stock controller
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'stock_controller') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Get all active technicians (without passwords for security)
    $stmt = $pdo->prepare("SELECT id, username, full_name, department, email, position FROM technicians WHERE active = 1 ORDER BY full_name");
    $stmt->execute();
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'technicians' => $technicians,
        'stock_controller' => [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'] ?? $_SESSION['username']
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
