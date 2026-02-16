<?php
// api/get_qr_stats.php
session_start();
require_once '../includes/database_fix.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

try {
    $db = new DatabaseFix();
    $conn = $db->getConnection();
    
    // Get total items count
    $totalQuery = "SELECT COUNT(*) as total FROM items WHERE status NOT IN ('disposed', 'lost')";
    $totalResult = $conn->query($totalQuery);
    $total = $totalResult->fetch_assoc()['total'] ?? 0;
    
    // Get items without QR codes
    $missingQuery = "SELECT COUNT(*) as missing FROM items WHERE (qr_code IS NULL OR qr_code = '' OR qr_code = 'pending') AND status NOT IN ('disposed', 'lost')";
    $missingResult = $conn->query($missingQuery);
    $missing = $missingResult->fetch_assoc()['missing'] ?? 0;
    
    // Get items with QR codes
    $withQRQuery = "SELECT COUNT(*) as with_qr FROM items WHERE qr_code IS NOT NULL AND qr_code != '' AND qr_code != 'pending' AND status NOT IN ('disposed', 'lost')";
    $withQRResult = $conn->query($withQRQuery);
    $withQR = $withQRResult->fetch_assoc()['with_qr'] ?? 0;
    
    $db->close();
    
    echo json_encode([
        'success' => true,
        'total' => (int)$total,
        'missing' => (int)$missing,
        'with_qr' => (int)$withQR
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>