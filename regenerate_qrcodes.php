<?php
// regenerate_qrcodes.php
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Get all items
$result = $conn->query("SELECT id, item_name, serial_number FROM items ORDER BY id");

echo "<h2>Regenerating QR Codes</h2>";

while ($row = $result->fetch_assoc()) {
    $item_id = $row['id'];
    $item_name = $row['item_name'];
    
    // Generate new simple QR code
    $qrData = $item_id; // Just the ID
    
    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?";
    $qrUrl .= "size=400x400&data=" . urlencode($qrData);
    $qrUrl .= "&margin=20&format=png&ecc=H";
    
    $qrcodeDir = __DIR__ . '/qrcodes';
    $qrFilename = 'qr_' . $item_id . '.png';
    $qrPath = $qrcodeDir . DIRECTORY_SEPARATOR . $qrFilename;
    $qrRelativePath = 'qrcodes/' . $qrFilename;
    
    $qrImage = @file_get_contents($qrUrl);
    
    if ($qrImage && file_put_contents($qrPath, $qrImage)) {
        // Update database
        $updateStmt = $conn->prepare("UPDATE items SET qr_code = ? WHERE id = ?");
        $updateStmt->bind_param('si', $qrRelativePath, $item_id);
        $updateStmt->execute();
        $updateStmt->close();
        
        echo "<p>✅ Regenerated QR for Item #{$item_id}: {$item_name}</p>";
    } else {
        echo "<p>❌ Failed to regenerate QR for Item #{$item_id}</p>";
    }
}

echo "<h3>Done! All QR codes updated to new simple format.</h3>";
$conn->close();
?>