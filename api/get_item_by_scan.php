<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

$identifier = $_GET['id'] ?? '';

if (empty($identifier)) {
    echo json_encode(['success' => false, 'message' => 'No identifier provided']);
    exit();
}

try {
    // Try to find by ID first
    $stmt = $pdo->prepare("
        SELECT * FROM items 
        WHERE id = ? OR serial_number = ?
        LIMIT 1
    ");

    $stmt->execute([$identifier, $identifier]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        echo json_encode([
            'success' => true,
            'data' => $item
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Equipment not found'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
