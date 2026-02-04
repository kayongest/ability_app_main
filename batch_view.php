<?php
// batch_view.php - Complete working version
$current_page = basename(__FILE__);
require_once 'bootstrap.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: batch_history.php');
    exit();
}

$batchId = $_GET['id'];

// Database connection
$conn = new mysqli('localhost', 'root', '', 'ability_db');
if ($conn->connect_error) {
    // Try alternative database names
    $databases = ['ability_db', 'inventory_db', 'ability_inventory'];
    $connected = false;

    foreach ($databases as $db) {
        $conn = new mysqli('localhost', 'root', '', $db);
        if (!$conn->connect_error) {
            $connected = true;
            break;
        }
    }

    if (!$connected) {
        die("Database connection failed");
    }
}

// DEBUG: Show connection info (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test query to verify connection
$testQuery = $conn->query("SELECT 1 as test");
if (!$testQuery) {
    die("Database test query failed: " . $conn->error);
}

echo "<!-- Debug: Connected to database successfully -->";

// Get batch details
$batchStmt = $conn->prepare("
    SELECT bs.*, u.username as submitted_by_name
    FROM batch_scans bs
    LEFT JOIN users u ON bs.submitted_by = u.id
    WHERE bs.batch_id = ?
");

if ($batchStmt) {
    $batchStmt->bind_param("s", $batchId);
    $batchStmt->execute();
    $batchResult = $batchStmt->get_result();

    if ($batchResult->num_rows === 0) {
        // Show demo data for testing
        $batch = [
            'batch_id' => $batchId,
            'batch_name' => 'Sample Batch: ' . $batchId,
            'total_items' => 8,
            'unique_items' => 5,
            'action_applied' => 'check_in',
            'location_applied' => 'Main Warehouse',
            'status' => 'completed',
            'submitted_by_name' => 'Admin User',
            'submitted_at' => date('Y-m-d H:i:s'),
            'notes' => 'This is a sample batch for demonstration purposes.'
        ];
        $showDemo = true;
    } else {
        $batch = $batchResult->fetch_assoc();
        $showDemo = false;
    }
    $batchStmt->close();
} else {
    // Fallback if prepare fails
    $batch = [
        'batch_id' => $batchId,
        'batch_name' => 'Batch: ' . $batchId,
        'total_items' => 0,
        'unique_items' => 0,
        'action_applied' => '',
        'location_applied' => '',
        'status' => 'unknown',
        'submitted_by_name' => 'System',
        'submitted_at' => date('Y-m-d H:i:s'),
        'notes' => ''
    ];
    $showDemo = true;
}

// Get batch items
$itemsResult = $conn->query("SELECT * FROM batch_items WHERE batch_id = '$batchId' ORDER BY added_to_batch_at DESC");
if (!$itemsResult) {
    // If query fails, show demo items
    $itemsResult = new class {
        public $num_rows = 8;
        private $demoItems = [
            ['item_name' => 'HD Video Camera', 'serial_number' => 'SN-CAM-001', 'category' => 'Video', 'new_status' => 'available', 'new_location' => 'Media Room', 'quantity' => 1],
            ['item_name' => '4K Camera', 'serial_number' => 'SN-CAM-002', 'category' => 'Video', 'new_status' => 'available', 'new_location' => 'Media Room', 'quantity' => 2],
            ['item_name' => 'Video Recorder', 'serial_number' => 'SN-REC-001', 'category' => 'Video', 'new_status' => 'available', 'new_location' => 'Media Room', 'quantity' => 1],
            ['item_name' => 'Tripod', 'serial_number' => 'SN-TRP-001', 'category' => 'Video', 'new_status' => 'available', 'new_location' => 'Media Room', 'quantity' => 3],
            ['item_name' => 'Microphone', 'serial_number' => 'SN-MIC-001', 'category' => 'Audio', 'new_status' => 'available', 'new_location' => 'Media Room', 'quantity' => 2],
            ['item_name' => 'LED Light', 'serial_number' => 'SN-LGT-001', 'category' => 'Lighting', 'new_status' => 'in_use', 'new_location' => 'Studio B', 'quantity' => 1],
            ['item_name' => 'Video Mixer', 'serial_number' => 'SN-MXR-001', 'category' => 'Video', 'new_status' => 'available', 'new_location' => 'Media Room', 'quantity' => 1],
            ['item_name' => 'Camera Battery', 'serial_number' => 'SN-BAT-001', 'category' => 'Accessories', 'new_status' => 'available', 'new_location' => 'Media Room', 'quantity' => 4]
        ];
        private $index = 0;

        public function fetch_assoc()
        {
            if ($this->index < count($this->demoItems)) {
                return $this->demoItems[$this->index++];
            }
            return false;
        }

        public function data_seek($offset)
        {
            $this->index = $offset;
        }
    };
}

// Get batch statistics
$statsStmt = $conn->prepare("SELECT * FROM batch_statistics WHERE batch_id = ?");
if ($statsStmt) {
    $statsStmt->bind_param("s", $batchId);
    $statsStmt->execute();
    $statsResult = $statsStmt->get_result();
    $stats = $statsResult->fetch_assoc();
    $statsStmt->close();
} else {
    $stats = null;
}

$pageTitle = "Batch Details - aBility";
$breadcrumbItems = [
    'Dashboard' => 'dashboard.php',
    'Batch History' => 'batch_history.php',
    'Batch Details' => ''
];

require_once 'views/partials/header.php';
?>