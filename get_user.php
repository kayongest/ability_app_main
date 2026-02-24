<?php
// get_user.php - AJAX endpoint to get user data
require_once 'bootstrap.php';

if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once 'includes/db_connect.php';
$conn = getConnection();

$user_id = $_GET['id'] ?? 0;

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID required']);
    exit();
}

$stmt = $conn->prepare("SELECT id, username, email, full_name, phone, department, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    echo json_encode($user);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
}

$stmt->close();