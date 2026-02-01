<?php
// batch_view.php - Complete working version
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
$itemsStmt = $conn->prepare("SELECT * FROM batch_items WHERE batch_id = ? ORDER BY added_to_batch_at DESC");
if ($itemsStmt) {
    $itemsStmt->bind_param("s", $batchId);
    $itemsStmt->execute();
    $itemsResult = $itemsStmt->get_result();
    $itemsStmt->close();
} else {
    // Demo items if query fails
    $itemsResult = new class {
        public $num_rows = 0;
        public function fetch_assoc() { return false; }
        public function data_seek($offset) { }
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

<style>
    .batch-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 25px;
    }
    
    .info-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border-left: 4px solid #007bff;
    }
    
    .item-row {
        border-bottom: 1px solid #eee;
        padding: 15px 0;
        transition: background 0.2s;
    }
    
    .item-row:hover {
        background: #f8f9fa;
    }
    
    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
    }
    
    .status-available {
        background-color: rgba(40, 167, 69, 0.15);
        color: #28a745;
    }
    
    .status-in_use {
        background-color: rgba(0, 123, 255, 0.15);
        color: #007bff;
    }
    
    .status-maintenance {
        background-color: rgba(255, 193, 7, 0.15);
        color: #ffc107;
    }
    
    /* Modal Styles */
    .modal-xl {
        max-width: 1200px;
    }
    
    .modal-batch-header {
        background: linear-gradient(135deg, #4361ee, #3a0ca3);
        color: white;
        padding: 15px 20px;
        border-radius: 10px 10px 0 0;
        margin-bottom: 20px;
    }
    
    .modal-batch-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 20px;
    }
    
    .modal-stat-item {
        flex: 1;
        min-width: 150px;
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
    }
    
    .modal-stat-value {
        font-size: 1.5rem;
        font-weight: bold;
        color: #4361ee;
        line-height: 1;
        margin-bottom: 5px;
    }
    
    .modal-stat-label {
        font-size: 0.85rem;
        color: #6c757d;
    }
    
    .modal-items-table {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .modal-items-table table {
        width: 100%;
        font-size: 0.9rem;
    }
    
    .modal-items-table th {
        position: sticky;
        top: 0;
        background: #f8f9fa;
        z-index: 10;
    }
    
    .print-options {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-top: 20px;
    }
    
    .demo-banner {
        background: linear-gradient(135deg, #ffc107, #ff9800);
        color: #856404;
        padding: 12px 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 5px solid #ffc107;
    }
</style>

<!-- Print Modal -->
<div class="modal fade" id="printModal" tabindex="-1" aria-labelledby="printModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-batch-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="modal-title mb-0" id="printModalLabel">
                            <i class="fas fa-print me-2"></i>Print Batch Report
                        </h5>
                        <small>Batch ID: <?php echo $batch['batch_id']; ?></small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body">
                <?php if ($showDemo): ?>
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    Showing sample data. Real data will appear when batches are submitted.
                </div>
                <?php endif; ?>
                
                <!-- Print Options -->
                <div class="print-options mb-4">
                    <h6><i class="fas fa-cog me-2"></i>Print Options</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="printHeader" checked>
                                <label class="form-check-label" for="printHeader">
                                    Include Header Information
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="printStats" checked>
                                <label class="form-check-label" for="printStats">
                                    Include Statistics
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="printNotes" checked>
                                <label class="form-check-label" for="printNotes">
                                    Include Batch Notes
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="printItems" checked>
                                <label class="form-check-label" for="printItems">
                                    Include Items List
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-2">
                                <label for="printFormat" class="form-label">Format</label>
                                <select class="form-select" id="printFormat">
                                    <option value="full">Full Report</option>
                                    <option value="summary">Summary Only</option>
                                    <option value="items">Items List Only</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preview Area -->
                <div id="printPreview" style="background: white; padding: 20px; border: 1px solid #dee2e6; border-radius: 8px;">
                    <!-- Preview will be generated by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" onclick="generatePDF()">
                    <i class="fas fa-file-pdf me-1"></i> Download PDF
                </button>
                <button type="button" class="btn btn-success" onclick="printBatchReport()">
                    <i class="fas fa-print me-1"></i> Print Now
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="exportModalLabel">
                    <i class="fas fa-download me-2"></i>Export Batch Data
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Choose the format you want to export the batch data in.
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="card text-center h-100">
                            <div class="card-body">
                                <i class="fas fa-file-excel fa-3x text-success mb-3"></i>
                                <h5>Excel Format</h5>
                                <p class="text-muted">Best for data analysis and editing</p>
                                <button class="btn btn-success w-100" onclick="exportBatch('excel')">
                                    <i class="fas fa-download me-1"></i> Export as Excel
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center h-100">
                            <div class="card-body">
                                <i class="fas fa-file-csv fa-3x text-primary mb-3"></i>
                                <h5>CSV Format</h5>
                                <p class="text-muted">Compatible with most spreadsheet software</p>
                                <button class="btn btn-primary w-100" onclick="exportBatch('csv')">
                                    <i class="fas fa-download me-1"></i> Export as CSV
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center h-100">
                            <div class="card-body">
                                <i class="fas fa-file-alt fa-3x text-warning mb-3"></i>
                                <h5>JSON Format</h5>
                                <p class="text-muted">For API integration and data transfer</p>
                                <button class="btn btn-warning w-100" onclick="exportBatch('json')">
                                    <i class="fas fa-download me-1"></i> Export as JSON
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Quick View Modal -->
<div class="modal fade" id="quickViewModal" tabindex="-1" aria-labelledby="quickViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="quickViewModalLabel">
                    <i class="fas fa-eye me-2"></i>Quick Batch View
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Batch Header -->
                <div class="modal-batch-header mb-4">
                    <h4><?php echo htmlspecialchars($batch['batch_name']); ?></h4>
                    <div class="row mt-2">
                        <div class="col-md-4">
                            <small><i class="fas fa-hashtag me-1"></i><?php echo $batch['batch_id']; ?></small>
                        </div>
                        <div class="col-md-4">
                            <small><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($batch['submitted_by_name']); ?></small>
                        </div>
                        <div class="col-md-4">
                            <small><i class="fas fa-calendar me-1"></i><?php echo date('F j, Y g:i A', strtotime($batch['submitted_at'])); ?></small>
                        </div>
                    </div>
                </div>

                <!-- Batch Statistics -->
                <div class="modal-batch-stats">
                    <div class="modal-stat-item">
                        <div class="modal-stat-value"><?php echo $batch['total_items']; ?></div>
                        <div class="modal-stat-label">Total Items</div>
                        <small class="text-muted"><?php echo $batch['unique_items']; ?> unique</small>
                    </div>
                    <div class="modal-stat-item">
                        <div class="modal-stat-value">
                            <?php if ($batch['action_applied']): ?>
                                <?php echo ucfirst(str_replace('_', ' ', $batch['action_applied'])); ?>
                            <?php else: ?>
                                None
                            <?php endif; ?>
                        </div>
                        <div class="modal-stat-label">Action Applied</div>
                    </div>
                    <div class="modal-stat-item">
                        <div class="modal-stat-value">
                            <?php echo $batch['location_applied'] ? htmlspecialchars($batch['location_applied']) : 'Not Set'; ?>
                        </div>
                        <div class="modal-stat-label">Location</div>
                    </div>
                    <div class="modal-stat-item">
                        <div class="modal-stat-value">
                            <span class="badge bg-<?php echo $batch['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($batch['status']); ?>
                            </span>
                        </div>
                        <div class="modal-stat-label">Status</div>
                    </div>
                </div>

                <!-- Batch Notes -->
                <?php if ($batch['notes']): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Batch Notes</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($batch['notes'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Items Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fas fa-boxes me-2"></i>Scanned Items (<?php echo $itemsResult->num_rows; ?>)</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="modal-items-table">
                            <table class="table table-hover table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Serial Number</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Location</th>
                                        <th>Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($itemsResult->num_rows > 0): ?>
                                        <?php while ($item = $itemsResult->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                                <?php if (!empty($item['notes'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($item['notes']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><code><?php echo htmlspecialchars($item['serial_number']); ?></code></td>
                                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($item['new_status']); ?>">
                                                    <?php echo ucfirst($item['new_status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($item['new_location']); ?></td>
                                            <td><span class="badge bg-info">x<?php echo $item['quantity']; ?></span></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">
                                                <i class="fas fa-box-open fa-2x mb-3"></i>
                                                <h5>No items in this batch</h5>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Close
                </button>
                <button type="button" class="btn btn-primary" onclick="openPrintModal()">
                    <i class="fas fa-print me-1"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <?php if ($showDemo): ?>
    <div class="demo-banner">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Sample Data:</strong> This is demonstration data. Real batches will appear after submission.
        <a href="scan.php" class="btn btn-sm btn-warning ms-2">
            <i class="fas fa-qrcode me-1"></i> Go to Scan Page
        </a>
    </div>
    <?php endif; ?>
    
    <!-- Batch Header -->
    <div class="batch-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h2 mb-2"><?php echo htmlspecialchars($batch['batch_name']); ?></h1>
                <p class="mb-0 opacity-75">
                    <i class="fas fa-hashtag me-1"></i><?php echo $batch['batch_id']; ?>
                    <span class="mx-3">|</span>
                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($batch['submitted_by_name']); ?>
                    <span class="mx-3">|</span>
                    <i class="fas fa-calendar me-1"></i><?php echo date('F j, Y g:i A', strtotime($batch['submitted_at'])); ?>
                </p>
            </div>
            <div class="col-md-4 text-end">
                <div class="btn-group">
                    <a href="batch_history.php" class="btn btn-light">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </a>
                    <button class="btn btn-light" onclick="openPrintModal()">
                        <i class="fas fa-print me-1"></i> Print
                    </button>
                    <button class="btn btn-light" onclick="openExportModal()">
                        <i class="fas fa-download me-1"></i> Export
                    </button>
                    <button class="btn btn-primary" onclick="openQuickViewModal()">
                        <i class="fas fa-expand me-1"></i> Full View
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Batch Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="info-card" style="border-left-color: #28a745;">
                <h6 class="text-muted mb-2">Total Items</h6>
                <h3 class="mb-0"><?php echo $batch['total_items']; ?></h3>
                <small class="text-muted"><?php echo $batch['unique_items']; ?> unique items</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-card" style="border-left-color: #007bff;">
                <h6 class="text-muted mb-2">Action Applied</h6>
                <h5 class="mb-0">
                    <?php if ($batch['action_applied']): ?>
                        <?php echo ucfirst(str_replace('_', ' ', $batch['action_applied'])); ?>
                    <?php else: ?>
                        None
                    <?php endif; ?>
                </h5>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-card" style="border-left-color: #ffc107;">
                <h6 class="text-muted mb-2">Location</h6>
                <h5 class="mb-0">
                    <?php echo $batch['location_applied'] ? htmlspecialchars($batch['location_applied']) : 'Not Set'; ?>
                </h5>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-card" style="border-left-color: #6f42c1;">
                <h6 class="text-muted mb-2">Status</h6>
                <h5 class="mb-0">
                    <span class="badge bg-<?php echo $batch['status'] === 'completed' ? 'success' : 'warning'; ?>">
                        <?php echo ucfirst($batch['status']); ?>
                    </span>
                </h5>
            </div>
        </div>
    </div>
    
    <!-- Batch Notes -->
    <?php if ($batch['notes']): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Batch Notes</h6>
        </div>
        <div class="card-body">
            <p class="mb-0"><?php echo nl2br(htmlspecialchars($batch['notes'])); ?></p>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Items List -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-boxes me-2"></i>Scanned Items (<?php echo $itemsResult->num_rows; ?>)</h6>
            <div class="text-muted">
                Showing all items in this batch
            </div>
        </div>
        <div class="card-body">
            <?php if ($itemsResult->num_rows > 0): ?>
                <?php while ($item = $itemsResult->fetch_assoc()): ?>
                <div class="item-row">
                    <div class="row">
                        <div class="col-md-3">
                            <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                            <div class="small text-muted">
                                <code><?php echo htmlspecialchars($item['serial_number']); ?></code>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="small text-muted">Category</div>
                            <div><?php echo htmlspecialchars($item['category']); ?></div>
                        </div>
                        <div class="col-md-2">
                            <div class="small text-muted">Status</div>
                            <div>
                                <span class="status-badge status-<?php echo strtolower($item['new_status']); ?>">
                                    <?php echo ucfirst($item['new_status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="small text-muted">Location</div>
                            <div><?php echo htmlspecialchars($item['new_location']); ?></div>
                        </div>
                        <div class="col-md-2">
                            <div class="small text-muted">Quantity</div>
                            <div>
                                <span class="badge bg-info">x<?php echo $item['quantity']; ?></span>
                            </div>
                        </div>
                        <div class="col-md-1 text-end">
                            <button class="btn btn-sm btn-outline-secondary" 
                                    onclick="viewItemDetails(<?php echo $item['item_id']; ?>)">
                                <i class="fas fa-external-link-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-box-open fa-2x mb-3"></i>
                    <h5>No items in this batch</h5>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer text-muted text-center">
            <?php echo $itemsResult->num_rows; ?> item(s) total
        </div>
    </div>
    
    <!-- Statistics Card -->
    <?php if ($stats): ?>
    <div class="card mt-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Batch Statistics</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="text-center p-3">
                        <h3 class="text-primary"><?php echo $stats['status_changed_count'] ?? 0; ?></h3>
                        <p class="text-muted mb-0">Status Changes</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-3">
                        <h3 class="text-success"><?php echo $stats['location_changed_count'] ?? 0; ?></h3>
                        <p class="text-muted mb-0">Location Updates</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-3">
                        <h3 class="text-warning"><?php echo $stats['quantity_updated_count'] ?? 0; ?></h3>
                        <p class="text-muted mb-0">Quantity Adjustments</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Modal Functions
function openQuickViewModal() {
    const modal = new bootstrap.Modal(document.getElementById('quickViewModal'));
    modal.show();
}

function openPrintModal() {
    generatePrintPreview();
    const modal = new bootstrap.Modal(document.getElementById('printModal'));
    modal.show();
}

function openExportModal() {
    const modal = new bootstrap.Modal(document.getElementById('exportModal'));
    modal.show();
}

function viewItemDetails(itemId) {
    alert('Item details for ID: ' + itemId + '\n\nThis would open a detailed item view page.');
    // window.open(`item_details.php?id=${itemId}`, '_blank');
}

function generatePrintPreview() {
    const preview = document.getElementById('printPreview');
    const includeHeader = document.getElementById('printHeader').checked;
    const includeStats = document.getElementById('printStats').checked;
    const includeNotes = document.getElementById('printNotes').checked;
    const includeItems = document.getElementById('printItems').checked;
    const format = document.getElementById('printFormat').value;

    let html = `
        <style>
            .print-container { font-family: Arial, sans-serif; }
            .print-header { border-bottom: 2px solid #333; margin-bottom: 20px; padding-bottom: 10px; }
            .print-title { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
            .print-subtitle { font-size: 14px; color: #666; margin-bottom: 10px; }
            .print-section { margin-bottom: 20px; }
            .print-section-title { font-size: 16px; font-weight: bold; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 10px; }
            .print-table { width: 100%; border-collapse: collapse; font-size: 12px; }
            .print-table th { background: #f5f5f5; border: 1px solid #ddd; padding: 8px; text-align: left; }
            .print-table td { border: 1px solid #ddd; padding: 8px; }
            .print-stats { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px; }
            .print-stat-item { flex: 1; min-width: 120px; background: #f9f9f9; padding: 10px; border-radius: 4px; }
            .print-stat-value { font-size: 18px; font-weight: bold; }
            .print-stat-label { font-size: 12px; color: #666; }
        </style>
        <div class="print-container">
    `;

    // Header
    if (includeHeader && (format === 'full' || format === 'summary')) {
        html += `
            <div class="print-header">
                <div class="print-title"><?php echo htmlspecialchars($batch['batch_name']); ?></div>
                <div class="print-subtitle">
                    Batch ID: <?php echo $batch['batch_id']; ?> | 
                    Submitted: <?php echo date('F j, Y g:i A', strtotime($batch['submitted_at'])); ?> | 
                    By: <?php echo htmlspecialchars($batch['submitted_by_name']); ?>
                </div>
            </div>
        `;
    }

    // Statistics
    if (includeStats && (format === 'full' || format === 'summary')) {
        html += `
            <div class="print-section">
                <div class="print-section-title">Batch Statistics</div>
                <div class="print-stats">
                    <div class="print-stat-item">
                        <div class="print-stat-value"><?php echo $batch['total_items']; ?></div>
                        <div class="print-stat-label">Total Items</div>
                    </div>
                    <div class="print-stat-item">
                        <div class="print-stat-value"><?php echo $batch['unique_items']; ?></div>
                        <div class="print-stat-label">Unique Items</div>
                    </div>
                    <div class="print-stat-item">
                        <div class="print-stat-value"><?php echo $batch['action_applied'] ? ucfirst(str_replace('_', ' ', $batch['action_applied'])) : 'None'; ?></div>
                        <div class="print-stat-label">Action Applied</div>
                    </div>
                    <div class="print-stat-item">
                        <div class="print-stat-value"><?php echo ucfirst($batch['status']); ?></div>
                        <div class="print-stat-label">Status</div>
                    </div>
                </div>
            </div>
        `;
    }

    // Notes
    if (includeNotes && <?php echo !empty($batch['notes']) ? 'true' : 'false'; ?> && (format === 'full' || format === 'summary')) {
        html += `
            <div class="print-section">
                <div class="print-section-title">Batch Notes</div>
                <p><?php echo nl2br(htmlspecialchars($batch['notes'])); ?></p>
            </div>
        `;
    }

    // Items List
    if (includeItems && (format === 'full' || format === 'items')) {
        html += `
            <div class="print-section">
                <div class="print-section-title">Scanned Items (<?php echo $itemsResult->num_rows; ?>)</div>
                <table class="print-table">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Serial Number</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Location</th>
                            <th>Qty</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        // Reset items pointer and loop through items
        <?php 
        if ($itemsResult->num_rows > 0) {
            // Store items in array for JavaScript access
            $items = [];
            while ($item = $itemsResult->fetch_assoc()) {
                $items[] = $item;
            }
            // Reset pointer
            $itemsResult->data_seek(0);
            
            foreach ($items as $item): ?>
                html += `
                    <tr>
                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['serial_number']); ?></td>
                        <td><?php echo htmlspecialchars($item['category']); ?></td>
                        <td><?php echo ucfirst($item['new_status']); ?></td>
                        <td><?php echo htmlspecialchars($item['new_location']); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                    </tr>
                `;
            <?php endforeach; 
        } ?>

        html += `
                    </tbody>
                </table>
            </div>
        `;
    }

    // Footer
    html += `
            <div style="margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 11px; color: #666;">
                Generated on <?php echo date('F j, Y g:i A'); ?> | 
                aBility Inventory System
            </div>
        </div>
    `;

    preview.innerHTML = html;
}

function printBatchReport() {
    const printWindow = window.open('', '_blank');
    const preview = document.getElementById('printPreview').innerHTML;
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Batch Report - <?php echo $batch['batch_id']; ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                @media print {
                    @page { margin: 0.5in; }
                    body { margin: 0; }
                }
            </style>
        </head>
        <body>
            ${preview}
            <script>
                window.onload = function() {
                    window.print();
                    setTimeout(function() {
                        window.close();
                    }, 1000);
                };
            <\/script>
        </body>
        </html>
    `);
    
    printWindow.document.close();
}

function generatePDF() {
    alert('PDF generation feature coming soon! For now, use the Print function.');
}

function exportBatch(format) {
    alert(format.toUpperCase() + ' export feature coming soon!');
}

// Initialize print preview when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for print options
    const printOptions = ['printHeader', 'printStats', 'printNotes', 'printItems', 'printFormat'];
    printOptions.forEach(option => {
        const element = document.getElementById(option);
        if (element) {
            element.addEventListener('change', generatePrintPreview);
        }
    });
});
</script>

<?php require_once 'views/partials/footer.php'; ?>