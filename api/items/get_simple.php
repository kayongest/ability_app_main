<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get item ID
$itemId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($itemId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'ability_db');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Query to get item by ID
$sql = "SELECT id, item_name, serial_number, category, status, stock_location, quantity 
        FROM items WHERE id = ? OR item_id = ? LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $itemId, $itemId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'item' => [
            'id' => (int)$row['id'],
            'item_id' => (int)$row['id'],
            'name' => $row['item_name'] ?? 'Unknown Item',
            'item_name' => $row['item_name'] ?? 'Unknown Item',
            'serial_number' => $row['serial_number'] ?? '',
            'serial' => $row['serial_number'] ?? '',
            'category' => $row['category'] ?? 'Equipment',
            'category_name' => $row['category'] ?? 'Equipment',
            'status' => $row['status'] ?? 'available',
            'stock_location' => $row['stock_location'] ?? 'Ndera Stock',
            'location' => $row['stock_location'] ?? 'Ndera Stock',
            'quantity' => (int)($row['quantity'] ?? 1)
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Item not found with ID: ' . $itemId
    ]);
}

$conn->close();
