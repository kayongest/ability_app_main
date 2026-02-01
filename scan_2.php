<?php
// scan.php - WITH SPLIT SCREEN (Scan Left, Batch Items Right) + MANUAL ENTRY
require_once 'bootstrap.php';

// Check authentication
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$pageTitle = "Scan & Batch Items - aBility";
$showBreadcrumb = true;
$breadcrumbItems = [
    'Dashboard' => 'dashboard.php',
    'Scan & Batch Items' => ''
];

require_once 'views/partials/header.php';

// Check if library exists
$libraryPath = 'assets/js/html5-qrcode.min.js';
if (!file_exists($libraryPath)) {
    echo '<div class="alert alert-danger">';
    echo '<h5><i class="fas fa-exclamation-triangle me-2"></i>Scanner Library Missing</h5>';
    echo '<p>The QR scanner library is not installed. Please download it:</p>';
    echo '<p><a href="https://github.com/mebjas/html5-qrcode/releases/download/v2.3.8/html5-qrcode.min.js" class="btn btn-primary" download="html5-qrcode.min.js">';
    echo '<i class="fas fa-download me-2"></i>Download Scanner Library</a></p>';
    echo '<p>Then save it to: <code>assets/js/html5-qrcode.min.js</code></p>';
    echo '</div>';
    require_once 'views/partials/footer.php';
    exit();
}
?>

<!-- Load the library from local assets -->
<script src="assets/js/html5-qrcode.min.js"></script>

<style>
    /* Main Layout */
    .split-container {
        display: flex;
        height: calc(100vh - 180px);
        gap: 20px;
        margin-top: 20px;
    }

    .left-panel {
        flex: 1;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .right-panel {
        flex: 1;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .panel-header {
        background: linear-gradient(135deg, #4361ee, #3a0ca3);
        color: white;
        padding: 15px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .panel-header h5 {
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .panel-body {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
    }

    .panel-footer {
        padding: 15px 20px;
        background: #f8f9fa;
        border-top: 1px solid #dee2e6;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Scanner Styles */
    #scanner-container {
        position: relative;
        width: 100%;
        max-width: 500px;
        margin: 0 auto;
        height: 350px;
    }

    #qr-reader {
        width: 100% !important;
        height: 100% !important;
        border: 2px solid #007bff;
        border-radius: 10px;
        overflow: hidden;
        background: #000;
    }

    .scan-overlay {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 250px;
        height: 250px;
        border: 3px solid rgba(0, 123, 255, 0.7);
        box-shadow: 0 0 0 1000px rgba(0, 0, 0, 0.5);
        pointer-events: none;
        z-index: 10;
        border-radius: 15px;
    }

    .scan-line {
        position: absolute;
        width: 100%;
        height: 3px;
        background: linear-gradient(to right, transparent, #28a745, transparent);
        animation: scan 2s ease-in-out infinite;
    }

    @keyframes scan {
        0% {
            top: 0;
            opacity: 0.5;
        }

        50% {
            top: 100%;
            opacity: 1;
        }

        100% {
            top: 0;
            opacity: 0.5;
        }
    }

    .scanner-corner {
        position: absolute;
        width: 30px;
        height: 30px;
        border-color: #28a745;
        border-width: 3px;
    }

    .corner-tl {
        top: -3px;
        left: -3px;
        border-top-style: solid;
        border-left-style: solid;
        border-top-left-radius: 10px;
    }

    .corner-tr {
        top: -3px;
        right: -3px;
        border-top-style: solid;
        border-right-style: solid;
        border-top-right-radius: 10px;
    }

    .corner-bl {
        bottom: -3px;
        left: -3px;
        border-bottom-style: solid;
        border-left-style: solid;
        border-bottom-left-radius: 10px;
    }

    .corner-br {
        bottom: -3px;
        right: -3px;
        border-bottom-style: solid;
        border-right-style: solid;
        border-bottom-right-radius: 10px;
    }

    /* Batch Items Styles */
    .batch-stats {
        display: flex;
        justify-content: space-around;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .stat-item {
        text-align: center;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: bold;
        color: #4361ee;
        line-height: 1;
    }

    .stat-label {
        font-size: 0.9rem;
        color: #6c757d;
        margin-top: 5px;
    }

    .items-list {
        max-height: 400px;
        overflow-y: auto;
    }

    .item-card {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
        transition: all 0.3s ease;
        position: relative;
    }

    .item-card:hover {
        border-color: #4361ee;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .item-name {
        font-weight: 600;
        font-size: 1.1rem;
        color: #333;
        flex: 1;
        margin-right: 10px;
    }

    .item-actions {
        display: flex;
        gap: 5px;
    }

    .btn-action {
        width: 30px;
        height: 30px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .item-details {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        font-size: 0.9rem;
    }

    .detail-item {
        display: flex;
        flex-direction: column;
    }

    .detail-label {
        font-size: 0.8rem;
        color: #6c757d;
        margin-bottom: 2px;
    }

    .detail-value {
        font-weight: 500;
        color: #333;
    }

    .badge-status {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
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

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
    }

    .empty-icon {
        font-size: 48px;
        color: #dee2e6;
        margin-bottom: 15px;
    }

    /* Scanner Controls */
    .scanner-controls {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 15px;
        flex-wrap: wrap;
    }

    .scanner-controls .btn {
        min-width: 120px;
    }

    /* Camera Selection */
    .camera-select-container {
        max-width: 400px;
        margin: 0 auto 15px;
    }

    /* Manual Entry Styles */
    .manual-entry-container {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-top: 20px;
    }

    .manual-entry-container h6 {
        color: #4361ee;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        font-weight: 500;
        margin-bottom: 5px;
        display: block;
        color: #495057;
    }

    .input-group-with-button {
        display: flex;
        gap: 10px;
    }

    .input-group-with-button input {
        flex: 1;
    }

    /* Quick Search Results */
    .search-results {
        position: absolute;
        z-index: 1000;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        max-height: 300px;
        overflow-y: auto;
        width: 100%;
        margin-top: 5px;
        display: none;
    }

    .search-result-item {
        padding: 10px 15px;
        border-bottom: 1px solid #f1f3f4;
        cursor: pointer;
        transition: background 0.2s;
    }

    .search-result-item:hover {
        background: #f8f9fa;
    }

    .search-result-item:last-child {
        border-bottom: none;
    }

    .search-result-item .item-name {
        font-weight: 600;
        font-size: 0.9rem;
        margin-bottom: 3px;
    }

    .search-result-item .item-details {
        font-size: 0.8rem;
        color: #6c757d;
    }

    /* Tabs for Scanner/Manual Entry */
    .nav-tabs-custom {
        border-bottom: 2px solid #dee2e6;
        margin-bottom: 20px;
    }

    .nav-tabs-custom .nav-link {
        border: none;
        color: #6c757d;
        padding: 10px 20px;
        font-weight: 500;
        border-radius: 8px 8px 0 0;
        margin-bottom: -2px;
    }

    .nav-tabs-custom .nav-link:hover {
        color: #4361ee;
        background-color: #f8f9fa;
    }

    .nav-tabs-custom .nav-link.active {
        color: #4361ee;
        background-color: white;
        border-bottom: 3px solid #4361ee;
    }

    /* Responsive */
    @media (max-width: 992px) {
        .split-container {
            flex-direction: column;
            height: auto;
        }

        .left-panel,
        .right-panel {
            height: 500px;
        }

        #scanner-container {
            height: 300px;
        }
    }

    /* Animation for new item */
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(20px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .new-item {
        animation: slideIn 0.3s ease;
    }

    /* Success/Failure Colors */
    .text-success {
        color: #28a745 !important;
    }

    .text-danger {
        color: #dc3545 !important;
    }

    .text-warning {
        color: #ffc107 !important;
    }
</style>

<!-- Batch Submission Modal -->
<!-- Batch Submission Modal -->
<div class="modal fade" id="batchSubmitModal" tabindex="-1" aria-labelledby="batchSubmitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="batchSubmitModalLabel">
                    <i class="fas fa-paper-plane me-2"></i>Submit Batch Items - Review & Approval
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Top Info Section -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-info-circle fa-2x me-3"></i>
                                <div>
                                    <h6 class="mb-1">Ready to Submit</h6>
                                    <p class="mb-0">You're about to submit <span id="batchItemCount" class="fw-bold">0</span> items. Review all details before proceeding.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-warning">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                                <div>
                                    <h6 class="mb-1">Important</h6>
                                    <p class="mb-0">Ensure all information is accurate before submission.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Two Column Layout -->
                <div class="row">
                    <!-- Left Column: Job Details -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Job & Location Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="stockLocation" class="form-label">
                                            <i class="fas fa-warehouse me-1"></i> Stock Location *
                                        </label>
                                        <input type="text" class="form-control" id="stockLocation" value="KCC" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="eventName" class="form-label">
                                            <i class="fas fa-calendar-alt me-1"></i> Event Name *
                                        </label>
                                        <input type="text" class="form-control" id="eventName" value="Hibiscus" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="jobSheet" class="form-label">
                                            <i class="fas fa-file-alt me-1"></i> Job Sheet *
                                        </label>
                                        <input type="text" class="form-control" id="jobSheet" value="JS-00254" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="projectManager" class="form-label">
                                            <i class="fas fa-user-tie me-1"></i> Project Manager *
                                        </label>
                                        <input type="text" class="form-control" id="projectManager" value="Hirwa Aubin" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="vehicleNumber" class="form-label">
                                            <i class="fas fa-truck me-1"></i> Vehicle Number *
                                        </label>
                                        <input type="text" class="form-control" id="vehicleNumber" value="RAH 847" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="driverName" class="form-label">
                                            <i class="fas fa-user me-1"></i> Driver Name *
                                        </label>
                                        <input type="text" class="form-control" id="driverName" value="Valentin" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-user-check me-2"></i>Approval Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="requestedBy" class="form-label">
                                            <i class="fas fa-user-edit me-1"></i> Requested By *
                                        </label>
                                        <input type="text" class="form-control" id="requestedBy" value="Kayonga Raul" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="approvedBy" class="form-label">
                                            <i class="fas fa-user-shield me-1"></i> Approved By *
                                        </label>
                                        <input type="text" class="form-control" id="approvedBy" value="Mudacumura Irene" required>
                                    </div>
                                    <div class="col-md-12">
                                        <label for="approvalNotes" class="form-label">
                                            <i class="fas fa-sticky-note me-1"></i> Approval Notes
                                        </label>
                                        <textarea class="form-control" id="approvalNotes" rows="2" placeholder="Additional approval notes..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Items Summary -->
                    <div class="col-md-6">
                        <!-- Items Preview Card -->
                        <div class="card mb-4">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="fas fa-boxes me-2"></i>Items Summary</h6>
                                <div>
                                    <span class="badge bg-primary" id="summaryTotal">0</span> total items
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Quick Stats -->
                                <div class="row text-center mb-3">
                                    <div class="col-4">
                                        <div class="bg-success bg-opacity-10 p-2 rounded">
                                            <div class="h4 mb-1" id="summaryAvailable">0</div>
                                            <small class="text-muted">Available</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="bg-warning bg-opacity-10 p-2 rounded">
                                            <div class="h4 mb-1" id="summaryInUse">0</div>
                                            <small class="text-muted">In Use</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="bg-danger bg-opacity-10 p-2 rounded">
                                            <div class="h4 mb-1" id="summaryMaintenance">0</div>
                                            <small class="text-muted">Maintenance</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Items Table -->
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Item</th>
                                                <th>Serial</th>
                                                <th>Qty</th>
                                                <th>Destination</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="itemsPreviewTable">
                                            <!-- Items will be dynamically added here -->
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="3" class="text-end"><strong>Total Items:</strong></td>
                                                <td colspan="2"><strong id="totalItemsCount">0</strong></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Batch Actions Card -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-cogs me-2"></i>Batch Actions</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="batchAction" class="form-label">Apply Action to All Items</label>
                                        <select class="form-select" id="batchAction">
                                            <option value="">No Action (Keep Current)</option>
                                            <option value="check_in">Check In All</option>
                                            <option value="check_out">Check Out All</option>
                                            <option value="maintenance">Mark All as Maintenance</option>
                                            <option value="available">Mark All as Available</option>
                                        </select>
                                        <small class="form-text text-muted">This will override individual item statuses</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="batchLocation" class="form-label">Set Destination for All Items</label>
                                        <input type="text" class="form-control" id="batchLocation" value="KCC">
                                        <small class="form-text text-muted">Default: KCC (change if needed)</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Notes -->
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-comments me-2"></i>Additional Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="batchNotes" class="form-label">
                                        <i class="fas fa-sticky-note me-1"></i>Batch Notes
                                    </label>
                                    <textarea class="form-control" id="batchNotes" rows="2"
                                        placeholder="Purpose, special instructions, or additional details..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Confirmation Section -->
                <div class="card mt-4 border-primary">
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmBatchSubmit" required>
                            <label class="form-check-label" for="confirmBatchSubmit">
                                <i class="fas fa-shield-alt me-1"></i>
                                <strong>I confirm that:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>All items listed are accurate and verified</li>
                                    <li>Job details and approval information are correct</li>
                                    <li>This submission is authorized for processing</li>
                                </ul>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" onclick="printBatchSummary()">
                    <i class="fas fa-print me-1"></i> Print Preview
                </button>
                <button type="button" class="btn btn-success" id="submitBatchBtn" disabled>
                    <i class="fas fa-paper-plane me-1"></i> Submit Batch
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-qrcode me-2"></i>Scan & Batch Items
        </h1>
        <div class="btn-group">
            <button class="btn btn-primary" onclick="startScanner()">
                <i class="fas fa-play me-1"></i> Start Scanner
            </button>
            <button class="btn btn-warning" onclick="stopScanner()">
                <i class="fas fa-stop me-1"></i> Stop Scanner
            </button>
            <button class="btn btn-success" id="submitBatchModalBtn" disabled>
                <i class="fas fa-paper-plane me-1"></i> Submit Batch (<span id="batchCount">0</span>)
            </button>
        </div>
    </div>

    <!-- Split Screen Layout -->
    <div class="split-container">
        <!-- Left Panel: Scanner & Manual Entry -->
        <div class="left-panel">
            <div class="panel-header">
                <h5><i class="fas fa-qrcode"></i> Scan & Add Items</h5>
                <small class="text-light opacity-75" id="scannerStatus">Ready</small>
            </div>

            <div class="panel-body">
                <!-- Tabs for Scanner/Manual Entry -->
                <ul class="nav nav-tabs-custom" id="scanTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="scan-tab" data-bs-toggle="tab" data-bs-target="#scan-pane" type="button" role="tab" aria-controls="scan-pane" aria-selected="true">
                            <i class="fas fa-camera me-1"></i> QR Scanner
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual-pane" type="button" role="tab" aria-controls="manual-pane" aria-selected="false">
                            <i class="fas fa-keyboard me-1"></i> Manual Entry
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="scanTabsContent">
                    <!-- Scanner Tab -->
                    <div class="tab-pane fade show active" id="scan-pane" role="tabpanel" aria-labelledby="scan-tab" tabindex="0">
                        <!-- Camera Selection -->
                        <div class="camera-select-container mb-3">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-video"></i></span>
                                <select id="cameraSelect" class="form-select" disabled>
                                    <option value="">Loading cameras...</option>
                                </select>
                                <button class="btn btn-outline-secondary" type="button" onclick="refreshCameras()" title="Refresh camera list">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Scanner Container -->
                        <div id="scanner-container">
                            <div id="qr-reader"></div>
                            <div class="scan-overlay">
                                <div class="scan-line"></div>
                                <div class="scanner-corner corner-tl"></div>
                                <div class="scanner-corner corner-tr"></div>
                                <div class="scanner-corner corner-bl"></div>
                                <div class="scanner-corner corner-br"></div>
                            </div>
                        </div>

                        <!-- Scanner Controls -->
                        <div class="scanner-controls">
                            <button id="startBtn" class="btn btn-success">
                                <i class="fas fa-play me-1"></i> Start Scanner
                            </button>
                            <button id="stopBtn" class="btn btn-danger" disabled>
                                <i class="fas fa-stop me-1"></i> Stop Scanner
                            </button>
                            <button id="flipBtn" class="btn btn-info" onclick="flipCamera()" style="display: none;">
                                <i class="fas fa-sync-alt me-1"></i> Flip Camera
                            </button>
                        </div>

                        <!-- Quick Test Buttons -->
                        <div class="mt-4 border-top pt-3">
                            <h6><i class="fas fa-vial me-2"></i>Test QR Codes:</h6>
                            <div class="d-flex justify-content-center gap-2 flex-wrap">
                                <button class="btn btn-outline-primary btn-sm" onclick="testScanJSON()">
                                    <i class="fas fa-qrcode me-1"></i> Test JSON
                                </button>
                                <button class="btn btn-outline-success btn-sm" onclick="testScanNumber()">
                                    <i class="fas fa-hashtag me-1"></i> Test Number
                                </button>
                                <button class="btn btn-outline-warning btn-sm" onclick="addRandomTestItem()">
                                    <i class="fas fa-plus me-1"></i> Add Test Item
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Manual Entry Tab -->
                    <div class="tab-pane fade" id="manual-pane" role="tabpanel" aria-labelledby="manual-tab" tabindex="0">
                        <div class="manual-entry-container">
                            <h6><i class="fas fa-search me-1"></i> Find Item by Serial or ID</h6>

                            <div class="form-group">
                                <label for="manualSearch">Search Item</label>
                                <div class="input-group-with-button">
                                    <input type="text"
                                        class="form-control"
                                        id="manualSearch"
                                        placeholder="Enter serial number, item ID, or name..."
                                        onkeyup="searchItems(this.value)">
                                    <button class="btn btn-primary" onclick="performManualSearch()">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                </div>
                                <div id="searchResults" class="search-results"></div>
                                <div class="form-text">Start typing to search items</div>
                            </div>

                            <h6 class="mt-4"><i class="fas fa-plus-circle me-1"></i> Quick Add Items</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="itemId">Item ID</label>
                                        <input type="text" class="form-control" id="itemId" placeholder="Enter item ID">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="quantity">Quantity</label>
                                        <input type="number" class="form-control" id="quantity" value="1" min="1">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="itemName">Item Name (Optional)</label>
                                <input type="text" class="form-control" id="itemName" placeholder="Enter item name">
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select class="form-select" id="status">
                                            <option value="available">Available</option>
                                            <option value="in_use">In Use</option>
                                            <option value="maintenance">Maintenance</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="location">Location</label>
                                        <input type="text" class="form-control" id="location" placeholder="e.g., Warehouse A">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="category">Category</label>
                                <input type="text" class="form-control" id="category" placeholder="e.g., Electronics, Tools">
                            </div>

                            <div class="d-flex gap-2 mt-4">
                                <button class="btn btn-success flex-fill" onclick="addManualItem()">
                                    <i class="fas fa-plus me-1"></i> Add to Batch
                                </button>
                                <button class="btn btn-outline-secondary" onclick="clearManualForm()">
                                    <i class="fas fa-times me-1"></i> Clear
                                </button>
                            </div>
                        </div>

                        <!-- Recent Items -->
                        <div class="mt-4">
                            <h6><i class="fas fa-history me-2"></i>Recent Items</h6>
                            <div id="recentItems" class="d-flex flex-wrap gap-2">
                                <!-- Recent items will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel-footer">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Use scanner or manual entry to add items
                </small>
                <div class="text-end">
                    <span class="badge bg-info" id="scanCount">0 scans</span>
                </div>
            </div>
        </div>

        <!-- Right Panel: Batch Items -->
        <div class="right-panel">
            <div class="panel-header">
                <h5><i class="fas fa-boxes"></i> Batch Items <small class="opacity-75">(Scanned Items)</small></h5>
                <button class="btn btn-sm btn-light" onclick="clearBatch()" id="clearBatchBtn" disabled>
                    <i class="fas fa-trash me-1"></i> Clear All
                </button>
            </div>

            <div class="panel-body">
                <!-- Batch Stats -->
                <div class="batch-stats">
                    <div class="stat-item">
                        <div class="stat-value" id="totalItems">0</div>
                        <div class="stat-label">Total Items</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value text-success" id="availableItems">0</div>
                        <div class="stat-label">Available</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value text-warning" id="inUseItems">0</div>
                        <div class="stat-label">In Use</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value text-danger" id="maintenanceItems">0</div>
                        <div class="stat-label">Maintenance</div>
                    </div>
                </div>

                <!-- Items List -->
                <div class="items-list" id="batchItemsList">
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <h5>No items in batch</h5>
                        <p class="text-muted">Scan QR codes or use manual entry to add items</p>
                        <button class="btn btn-outline-primary mt-2" onclick="addRandomTestItem()">
                            <i class="fas fa-plus me-1"></i> Add Test Item
                        </button>
                    </div>
                </div>
            </div>

            <div class="panel-footer">
                <div class="text-muted small">
                    <i class="fas fa-lightbulb me-1"></i>
                    Scan multiple items before submitting as a batch
                </div>
                <div class="text-end">
                    <button class="btn btn-sm btn-primary" onclick="openBatchModal()" id="openBatchModalBtn" disabled>
                        <i class="fas fa-external-link-alt me-1"></i> Review & Submit
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Global variables
    let html5QrCode = null;
    let isScanning = false;
    let currentCameraId = null;
    let availableCameras = [];
    let currentCameraIndex = 0;
    let lastScanTimestamp = 0;
    const SCAN_COOLDOWN = 1500;
    let scanAttempts = 0;

    // Batch management
    let batchItems = [];
    let scanCount = 0;
    const DEBUG = true;

    // Recent items for manual entry
    let recentItems = [];

    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🔍 Scan & Batch page loading...');

        // Initialize scanner
        if (typeof Html5Qrcode === 'undefined') {
            showNotification('error', 'QR Scanner library not loaded');
            return;
        }

        try {
            html5QrCode = new Html5Qrcode("qr-reader");
            console.log('✅ Scanner instance created');
        } catch (error) {
            console.error('❌ Error creating scanner:', error);
            showNotification('error', 'Error initializing scanner');
            return;
        }

        // Setup button events
        document.getElementById('startBtn').addEventListener('click', startScanner);
        document.getElementById('stopBtn').addEventListener('click', stopScanner);
        document.getElementById('cameraSelect').addEventListener('change', onCameraSelect);
        document.getElementById('submitBatchModalBtn').addEventListener('click', openBatchModal);
        document.getElementById('submitBatchBtn').addEventListener('click', submitBatch);
        document.getElementById('confirmBatchSubmit').addEventListener('change', function() {
            document.getElementById('submitBatchBtn').disabled = !this.checked;
        });
        document.getElementById('manualSearch').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performManualSearch();
            }
        });

        // Load cameras
        loadCameras();

        // Initialize batch from localStorage
        loadBatchFromStorage();

        // Load recent items
        loadRecentItems();

        console.log('✅ Page initialization complete');
    });

    // ==================== MANUAL ENTRY FUNCTIONS ====================

    async function searchItems(searchTerm) {
        if (searchTerm.length < 2) {
            hideSearchResults();
            return;
        }

        try {
            const response = await fetch(`api/items/search.php?q=${encodeURIComponent(searchTerm)}`);
            if (response.ok) {
                const data = await response.json();
                displaySearchResults(data.items || []);
            }
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    function displaySearchResults(items) {
        const resultsContainer = document.getElementById('searchResults');

        if (!items || items.length === 0) {
            resultsContainer.innerHTML = '<div class="search-result-item text-muted p-3">No items found</div>';
            resultsContainer.style.display = 'block';
            return;
        }

        let html = '';
        items.forEach(item => {
            html += `
                <div class="search-result-item" onclick="selectSearchItem(${JSON.stringify(item).replace(/"/g, '&quot;')})">
                    <div class="item-name">${item.name || item.item_name || 'Unknown Item'}</div>
                    <div class="item-details">
                        ID: ${item.id || item.item_id} | 
                        Serial: ${item.serial_number || item.serial || 'N/A'} | 
                        Category: ${item.category || item.category_name || 'N/A'}
                    </div>
                </div>
            `;
        });

        resultsContainer.innerHTML = html;
        resultsContainer.style.display = 'block';
    }

    function hideSearchResults() {
        document.getElementById('searchResults').style.display = 'none';
    }

    function selectSearchItem(item) {
        // Fill the form with selected item
        document.getElementById('itemId').value = item.id || item.item_id || '';
        document.getElementById('itemName').value = item.name || item.item_name || '';
        document.getElementById('category').value = item.category || item.category_name || '';
        document.getElementById('status').value = item.status || 'available';
        document.getElementById('location').value = item.stock_location || item.location || '';
        document.getElementById('quantity').value = 1;

        hideSearchResults();
        document.getElementById('manualSearch').value = '';

        // Focus on quantity for quick adjustment
        document.getElementById('quantity').focus();
    }

    async function performManualSearch() {
        const searchTerm = document.getElementById('manualSearch').value.trim();
        if (!searchTerm) {
            showNotification('warning', 'Please enter a search term');
            return;
        }

        try {
            showNotification('info', 'Searching items...');

            const response = await fetch(`api/items/search.php?q=${encodeURIComponent(searchTerm)}`);
            if (!response.ok) throw new Error('Search failed');

            const data = await response.json();

            if (data.success && data.items && data.items.length > 0) {
                displaySearchResults(data.items);
            } else {
                showNotification('info', 'No items found');
                hideSearchResults();
            }
        } catch (error) {
            console.error('Search error:', error);
            showNotification('error', 'Error searching items');
        }
    }

    function addManualItem() {
        const itemId = document.getElementById('itemId').value.trim();
        const itemName = document.getElementById('itemName').value.trim();
        const quantity = parseInt(document.getElementById('quantity').value) || 1;
        const status = document.getElementById('status').value;
        const location = document.getElementById('location').value.trim();
        const category = document.getElementById('category').value.trim();

        if (!itemId && !itemName) {
            showNotification('warning', 'Please enter either Item ID or Item Name');
            return;
        }

        // Create item object
        const newItem = {
            id: itemId || Date.now(), // Generate temp ID if none
            item_id: itemId || Date.now(),
            name: itemName || `Manual Item ${Date.now()}`,
            item_name: itemName || `Manual Item ${Date.now()}`,
            serial_number: itemId,
            serial: itemId,
            category: category || 'Manual Entry',
            category_name: category || 'Manual Entry',
            status: status,
            stock_location: location,
            location: location,
            quantity: quantity,
            added_at: new Date().toISOString(),
            manual_entry: true
        };

        // Add to batch
        addToBatch(newItem);

        // Add to recent items
        addToRecentItems(newItem);

        // Clear form
        clearManualForm();

        showNotification('success', 'Item added to batch');
    }

    function clearManualForm() {
        document.getElementById('itemId').value = '';
        document.getElementById('itemName').value = '';
        document.getElementById('quantity').value = 1;
        document.getElementById('status').value = 'available';
        document.getElementById('location').value = '';
        document.getElementById('category').value = '';
        document.getElementById('manualSearch').value = '';
        hideSearchResults();
    }

    function addToRecentItems(item) {
        // Add to beginning of array
        recentItems.unshift(item);

        // Keep only last 10 items
        if (recentItems.length > 10) {
            recentItems = recentItems.slice(0, 10);
        }

        // Update recent items display
        updateRecentItemsDisplay();

        // Save to localStorage
        saveRecentItems();
    }

    function loadRecentItems() {
        try {
            const saved = localStorage.getItem('recent_items');
            if (saved) {
                recentItems = JSON.parse(saved);
                updateRecentItemsDisplay();
            }
        } catch (e) {
            console.error('Error loading recent items:', e);
        }
    }

    function saveRecentItems() {
        try {
            localStorage.setItem('recent_items', JSON.stringify(recentItems));
        } catch (e) {
            console.error('Error saving recent items:', e);
        }
    }

    function updateRecentItemsDisplay() {
        const container = document.getElementById('recentItems');

        if (recentItems.length === 0) {
            container.innerHTML = '<div class="text-muted small">No recent items</div>';
            return;
        }

        let html = '';
        recentItems.forEach((item, index) => {
            const name = item.name || item.item_name || 'Unknown Item';
            const truncatedName = name.length > 20 ? name.substring(0, 20) + '...' : name;

            html += `
                <button class="btn btn-sm btn-outline-primary" 
                        onclick="addRecentItemToBatch(${index})"
                        title="${name}">
                    ${truncatedName}
                </button>
            `;
        });

        container.innerHTML = html;
    }

    function addRecentItemToBatch(index) {
        if (index >= 0 && index < recentItems.length) {
            const item = {
                ...recentItems[index]
            };
            item.added_at = new Date().toISOString();
            addToBatch(item);
            showNotification('success', 'Item added from recent items');
        }
    }

    // ==================== SCANNER FUNCTIONS ====================

    async function loadCameras() {
        console.log('📷 Loading cameras...');
        updateScannerStatus('Detecting cameras...');

        const cameraSelect = document.getElementById('cameraSelect');
        cameraSelect.disabled = true;
        cameraSelect.innerHTML = '<option value="">Loading cameras...</option>';

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            console.error('❌ Camera API not supported');
            updateScannerStatus('Camera not supported');
            return;
        }

        try {
            // Get camera permission
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'environment'
                }
            });
            stream.getTracks().forEach(track => track.stop());

            // Enumerate devices
            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoDevices = devices.filter(device => device.kind === 'videoinput');

            console.log(`✅ Found ${videoDevices.length} camera(s)`);
            availableCameras = videoDevices;

            if (videoDevices.length > 0) {
                updateCameraSelect(videoDevices);
                updateScannerStatus('Select camera and start scanning');

                // Show flip button if more than 1 camera
                const flipBtn = document.getElementById('flipBtn');
                if (flipBtn && videoDevices.length > 1) {
                    flipBtn.style.display = 'inline-block';
                }
            } else {
                updateScannerStatus('No cameras found');
            }
        } catch (error) {
            console.error('❌ Error loading cameras:', error);
            updateScannerStatus('Camera access denied');
        }
    }

    function updateCameraSelect(cameras) {
        const select = document.getElementById('cameraSelect');
        select.innerHTML = '';

        if (cameras.length === 0) {
            select.innerHTML = '<option value="">No cameras found</option>';
            select.disabled = true;
            return;
        }

        cameras.forEach((camera, index) => {
            const option = document.createElement('option');
            option.value = camera.deviceId;
            option.textContent = camera.label || `Camera ${index + 1}`;
            option.dataset.index = index;
            select.appendChild(option);
        });

        select.value = cameras[0].deviceId;
        select.disabled = false;
        currentCameraIndex = 0;
        currentCameraId = cameras[0].deviceId;

        document.getElementById('startBtn').disabled = false;
    }

    function onCameraSelect() {
        const select = document.getElementById('cameraSelect');
        const cameraId = select.value;
        const selectedOption = select.options[select.selectedIndex];
        const cameraIndex = parseInt(selectedOption.dataset.index);

        if (cameraId && availableCameras[cameraIndex]) {
            const camera = availableCameras[cameraIndex];
            console.log(`📷 Selected camera: ${camera.label || 'Camera ' + (cameraIndex + 1)}`);

            currentCameraId = cameraId;
            currentCameraIndex = cameraIndex;

            if (isScanning) {
                restartScanner(cameraId);
            }
        }
    }

    async function startScanner() {
        const cameraSelect = document.getElementById('cameraSelect');
        const cameraId = cameraSelect.value;

        if (!cameraId) {
            showNotification('error', 'Please select a camera first');
            return;
        }

        await startScannerWithCamera(cameraId);
    }

    async function startScannerWithCamera(cameraId) {
        console.log(`🚀 Starting scanner with camera: ${cameraId}`);
        scanAttempts = 0;

        const startBtn = document.getElementById('startBtn');
        const stopBtn = document.getElementById('stopBtn');
        startBtn.disabled = true;
        stopBtn.disabled = false;

        updateScannerStatus('Starting camera...');

        try {
            const config = {
                fps: 10,
                qrbox: {
                    width: 250,
                    height: 250
                },
                aspectRatio: 1.0,
                disableFlip: false,
                formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
                focusMode: "continuous"
            };

            const qrCodeSuccessCallback = (decodedText, decodedResult) => {
                scanAttempts++;
                console.log(`✅ QR Code detected (attempt ${scanAttempts}):`, decodedText.substring(0, 50));
                onScanSuccess(decodedText, decodedResult);
            };

            const qrCodeErrorCallback = (errorMessage) => {
                if (errorMessage && !errorMessage.includes('NotFoundException')) {
                    console.log('Scan error:', errorMessage);
                }
            };

            await html5QrCode.start(cameraId, config, qrCodeSuccessCallback, qrCodeErrorCallback);

            isScanning = true;
            updateScannerStatus('Scanning... Point camera at QR code');

            const qrReader = document.getElementById('qr-reader');
            if (qrReader) qrReader.classList.add('scanner-active');

            console.log('🎉 Scanner started successfully');

        } catch (error) {
            console.error('❌ Scanner startup error:', error);

            let userMessage = 'Failed to start scanner. ';
            if (error.name === 'NotAllowedError') {
                userMessage = 'Camera permission denied. Please allow camera access.';
            } else if (error.name === 'NotFoundError') {
                userMessage = 'Selected camera not found. Try another camera.';
            } else {
                userMessage += error.message;
            }

            updateScannerStatus('Startup failed');
            startBtn.disabled = false;
            stopBtn.disabled = true;

            showNotification('error', userMessage);

            try {
                if (html5QrCode.getState() && html5QrCode.getState() !== Html5QrcodeScannerState.STOPPED) {
                    await html5QrCode.stop();
                }
            } catch (e) {
                console.log('Error cleaning up scanner:', e);
            }
        }
    }

    async function stopScanner() {
        console.log('🛑 Stopping scanner...');

        if (html5QrCode && isScanning) {
            try {
                await html5QrCode.stop();
                isScanning = false;

                const startBtn = document.getElementById('startBtn');
                const stopBtn = document.getElementById('stopBtn');
                startBtn.disabled = false;
                stopBtn.disabled = true;

                updateScannerStatus('Ready to scan');

                const qrReader = document.getElementById('qr-reader');
                if (qrReader) qrReader.classList.remove('scanner-active');

                console.log('✅ Scanner stopped successfully');

            } catch (error) {
                console.error('❌ Error stopping scanner:', error);
                showNotification('error', 'Error stopping scanner');
            }
        }
    }

    async function restartScanner(cameraId) {
        if (html5QrCode && isScanning) {
            await stopScanner();
            setTimeout(() => {
                startScannerWithCamera(cameraId);
            }, 500);
        }
    }

    function flipCamera() {
        if (availableCameras.length > 1) {
            currentCameraIndex = (currentCameraIndex + 1) % availableCameras.length;
            const newCamera = availableCameras[currentCameraIndex];
            const cameraSelect = document.getElementById('cameraSelect');
            if (cameraSelect) {
                cameraSelect.value = newCamera.deviceId;
                onCameraSelect();
                console.log('🔄 Flipped to camera:', newCamera.label || `Camera ${currentCameraIndex + 1}`);
            }
        }
    }

    function refreshCameras() {
        console.log('🔄 Refreshing camera list...');
        loadCameras();
    }

    function updateScannerStatus(text) {
        document.getElementById('scannerStatus').textContent = text;
    }

    function onScanSuccess(decodedText, decodedResult) {
        const now = Date.now();

        // Prevent multiple scans in quick succession
        if (now - lastScanTimestamp < SCAN_COOLDOWN) {
            console.log('⏳ Scan ignored (too soon after last scan)');
            return;
        }

        lastScanTimestamp = now;
        console.log('🔍 Processing QR code:', decodedText.substring(0, 100));

        updateScannerStatus('Scan successful!');

        // Visual feedback
        const qrReader = document.getElementById('qr-reader');
        if (qrReader) {
            qrReader.classList.remove('scanner-active');
            qrReader.classList.add('scan-success');
            setTimeout(() => {
                qrReader.classList.remove('scan-success');
                if (isScanning) {
                    qrReader.classList.add('scanner-active');
                }
            }, 500);
        }

        // Process the scan
        processScan(decodedText);

        // Update scan count
        scanCount++;
        document.getElementById('scanCount').textContent = scanCount + ' scans';

        // Pause scanner briefly
        if (html5QrCode && isScanning) {
            html5QrCode.pause();

            setTimeout(() => {
                if (html5QrCode && isScanning) {
                    html5QrCode.resume();
                    updateScannerStatus('Scanning...');
                }
            }, SCAN_COOLDOWN);
        }
    }

    // ==================== BATCH MANAGEMENT FUNCTIONS ====================

    function processScan(scanData) {
        console.log('🔧 Processing scan data:', scanData);

        let itemId = null;
        scanData = scanData.trim();

        // Extract item ID from various formats
        if (scanData.startsWith('ID:')) {
            itemId = scanData.substring(3);
        } else if (scanData.startsWith('ABIL:')) {
            itemId = scanData.substring(5);
        } else if (scanData.startsWith('ITEM-')) {
            itemId = scanData.substring(5);
        } else if (/^\d+$/.test(scanData)) {
            itemId = scanData;
        } else {
            try {
                const parsedData = JSON.parse(scanData);
                itemId = parsedData.i || parsedData.id || parsedData.item_id;
            } catch (e) {
                console.log('Not JSON format');
            }
        }

        if (itemId) {
            console.log('📋 Found item ID:', itemId);
            fetchAndAddToBatch(itemId);
        } else {
            console.log('🔍 No ID found, showing raw data');
            showNotification('warning', 'No valid item ID found in scan');
        }
    }

    async function fetchAndAddToBatch(itemId) {
        try {
            console.log('🌐 Fetching item details for ID:', itemId);
            showNotification('info', 'Fetching item details...');

            // Try multiple API endpoints
            const apiUrls = [
                `api/items/get.php?id=${itemId}`,
                `api/items/view.php?id=${itemId}`,
                `api/item.php?id=${itemId}`
            ];

            let response = null;
            let data = null;

            for (const apiUrl of apiUrls) {
                try {
                    console.log(`Trying API: ${apiUrl}`);
                    response = await fetch(apiUrl);

                    if (response.ok) {
                        const responseText = await response.text();
                        try {
                            data = JSON.parse(responseText);
                            console.log(`✅ Parsed JSON from ${apiUrl}:`, data);
                            break;
                        } catch (jsonError) {
                            console.error(`❌ Failed to parse JSON from ${apiUrl}`);
                            continue;
                        }
                    }
                } catch (e) {
                    console.log(`Network error for ${apiUrl}:`, e.message);
                }
            }

            if (!data) {
                throw new Error('Could not fetch item details');
            }

            // Extract item data
            let itemData = null;
            if (data.success && data.item) {
                itemData = data.item;
            } else if (data.item) {
                itemData = data.item;
            } else if (data.name || data.item_name) {
                itemData = data;
            }

            if (itemData) {
                // Add to batch
                addToBatch(itemData);
            } else {
                console.log('❌ Unexpected API response structure:', data);
                showNotification('error', 'Unexpected response format from server');
            }

        } catch (error) {
            console.error('❌ Fetch error:', error);
            showNotification('error', 'Error fetching item details: ' + error.message);
        }
    }

    function addToBatch(item) {
        // Check if item already exists in batch
        const existingIndex = batchItems.findIndex(i => i.id === item.id ||
            i.item_id === item.id ||
            i.serial_number === item.serial_number);

        if (existingIndex !== -1) {
            // Update existing item (increment quantity if applicable)
            if (batchItems[existingIndex].quantity) {
                batchItems[existingIndex].quantity += 1;
            }
            showNotification('warning', `Item "${item.name || item.item_name}" already in batch (quantity updated)`);
        } else {
            // Add new item with timestamp
            item.added_at = new Date().toISOString();
            item.batch_id = generateBatchId();
            if (!item.quantity) item.quantity = 1;

            batchItems.unshift(item); // Add to beginning
            showNotification('success', `Added "${item.name || item.item_name}" to batch`);
        }

        // Update UI
        updateBatchUI();
        saveBatchToStorage();

        // Play success sound
        playSuccessSound();
    }

    function removeFromBatch(itemId) {
        const index = batchItems.findIndex(i => i.id === itemId || i.item_id === itemId);
        if (index !== -1) {
            const itemName = batchItems[index].name || batchItems[index].item_name;
            batchItems.splice(index, 1);
            showNotification('info', `Removed "${itemName}" from batch`);
            updateBatchUI();
            saveBatchToStorage();
        }
    }

    function updateItemStatus(itemId, newStatus) {
        const index = batchItems.findIndex(i => i.id === itemId || i.item_id === itemId);
        if (index !== -1) {
            batchItems[index].status = newStatus;
            updateBatchUI();
            saveBatchToStorage();
            showNotification('success', `Status updated to "${newStatus}"`);
        }
    }

    function updateBatchUI() {
        const totalItems = batchItems.reduce((sum, item) => sum + (item.quantity || 1), 0);
        const availableItems = batchItems.filter(i => i.status === 'available').length;
        const inUseItems = batchItems.filter(i => i.status === 'in_use').length;
        const maintenanceItems = batchItems.filter(i => i.status === 'maintenance').length;

        // Update stats
        document.getElementById('totalItems').textContent = totalItems;
        document.getElementById('availableItems').textContent = availableItems;
        document.getElementById('inUseItems').textContent = inUseItems;
        document.getElementById('maintenanceItems').textContent = maintenanceItems;

        // Update batch count
        const batchCount = batchItems.length;
        document.getElementById('batchCount').textContent = batchCount;

        // Enable/disable buttons
        const submitBtn = document.getElementById('submitBatchModalBtn');
        const clearBtn = document.getElementById('clearBatchBtn');
        const openModalBtn = document.getElementById('openBatchModalBtn');

        if (batchCount > 0) {
            submitBtn.disabled = false;
            clearBtn.disabled = false;
            openModalBtn.disabled = false;
        } else {
            submitBtn.disabled = true;
            clearBtn.disabled = true;
            openModalBtn.disabled = true;
        }

        // Update items list
        const itemsList = document.getElementById('batchItemsList');

        if (batchCount === 0) {
            itemsList.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <h5>No items in batch</h5>
                    <p class="text-muted">Scan QR codes or use manual entry to add items</p>
                    <button class="btn btn-outline-primary mt-2" onclick="addRandomTestItem()">
                        <i class="fas fa-plus me-1"></i> Add Test Item
                    </button>
                </div>`;
        } else {
            let html = '';
            batchItems.forEach((item, index) => {
                const itemName = item.name || item.item_name || 'Unknown Item';
                const serialNumber = item.serial_number || item.serial || 'N/A';
                const category = item.category || item.category_name || 'N/A';
                const status = item.status || 'available';
                const location = item.stock_location || item.location || 'N/A';
                const quantity = item.quantity || 1;
                const isManual = item.manual_entry ? '<span class="badge bg-secondary ms-2">Manual</span>' : '';

                html += `
                <div class="item-card new-item" data-item-id="${item.id || item.item_id}">
                    <div class="item-header">
                        <div class="item-name">${itemName} ${isManual}</div>
                        <div class="item-actions">
                            ${quantity > 1 ? `<span class="badge bg-info me-2">x${quantity}</span>` : ''}
                            <button class="btn btn-action btn-sm btn-success" onclick="updateItemStatus('${item.id || item.item_id}', 'available')" 
                                    title="Mark as Available" ${status === 'available' ? 'disabled' : ''}>
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-action btn-sm btn-warning" onclick="updateItemStatus('${item.id || item.item_id}', 'in_use')" 
                                    title="Mark as In Use" ${status === 'in_use' ? 'disabled' : ''}>
                                <i class="fas fa-wrench"></i>
                            </button>
                            <button class="btn btn-action btn-sm btn-danger" onclick="removeFromBatch('${item.id || item.item_id}')" title="Remove from batch">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="item-details">
                        <div class="detail-item">
                            <span class="detail-label">Serial</span>
                            <span class="detail-value"><code>${serialNumber}</code></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Status</span>
                            <span class="detail-value">
                                <span class="badge-status status-${status}">${status}</span>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Category</span>
                            <span class="detail-value">${category}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Location</span>
                            <span class="detail-value">${location}</span>
                        </div>
                    </div>
                    <div class="mt-2 text-end">
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            ${formatTime(item.added_at)}
                        </small>
                    </div>
                </div>`;
            });
            itemsList.innerHTML = html;

            // Remove new-item class after animation
            setTimeout(() => {
                document.querySelectorAll('.new-item').forEach(el => {
                    el.classList.remove('new-item');
                });
            }, 300);
        }
    }

    function clearBatch() {
        if (batchItems.length === 0) return;

        if (confirm(`Are you sure you want to clear all ${batchItems.length} items from the batch?`)) {
            batchItems = [];
            updateBatchUI();
            saveBatchToStorage();
            showNotification('info', 'Batch cleared');
        }
    }

    function openBatchModal() {
        if (batchItems.length === 0) {
            showNotification('warning', 'No items in batch');
            return;
        }

        // Update basic stats
        updateBatchModalStats();

        // Update items preview table
        updateItemsPreviewTable();

        // Reset confirmation checkbox
        document.getElementById('confirmBatchSubmit').checked = false;
        document.getElementById('submitBatchBtn').disabled = true;

        // Set default destination from batch location if available
        const batchLocationInput = document.getElementById('batchLocation');
        if (batchLocationInput && !batchLocationInput.value) {
            batchLocationInput.value = 'KCC'; // Default destination
        }

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('batchSubmitModal'));
        modal.show();
    }

    function updateBatchModalStats() {
        const totalItems = batchItems.reduce((sum, item) => sum + (item.quantity || 1), 0);
        const availableItems = batchItems.filter(i => i.status === 'available').length;
        const inUseItems = batchItems.filter(i => i.status === 'in_use').length;
        const maintenanceItems = batchItems.filter(i => i.status === 'maintenance').length;
        const categories = [...new Set(batchItems.map(i => i.category || i.category_name || 'Uncategorized').filter(Boolean))];
        const uniqueItems = batchItems.length;

        // Update all stats
        document.getElementById('batchItemCount').textContent = batchItems.length;
        document.getElementById('summaryTotal').textContent = totalItems;
        document.getElementById('summaryAvailable').textContent = availableItems;
        document.getElementById('summaryInUse').textContent = inUseItems;
        document.getElementById('summaryMaintenance').textContent = maintenanceItems;
        document.getElementById('totalItemsCount').textContent = totalItems;
    }

    function updateItemsPreviewTable() {
        const tableBody = document.getElementById('itemsPreviewTable');

        if (batchItems.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">No items in batch</td></tr>';
            return;
        }

        let html = '';
        batchItems.slice(0, 10).forEach((item, index) => {
            const itemName = item.name || item.item_name || 'Unknown Item';
            const serialNumber = item.serial_number || item.serial || 'N/A';
            const quantity = item.quantity || 1;
            const status = item.status || 'available';
            const category = item.category || item.category_name || 'General';

            // Get status badge color
            let statusBadge = '';
            switch (status) {
                case 'available':
                    statusBadge = '<span class="badge bg-success">Available</span>';
                    break;
                case 'in_use':
                    statusBadge = '<span class="badge bg-warning">In Use</span>';
                    break;
                case 'maintenance':
                    statusBadge = '<span class="badge bg-danger">Maintenance</span>';
                    break;
                default:
                    statusBadge = '<span class="badge bg-secondary">' + status + '</span>';
            }

            html += `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-info me-2">${index + 1}</span>
                        <div>
                            <strong>${itemName}</strong>
                            <small class="d-block text-muted">${category}</small>
                        </div>
                    </div>
                </td>
                <td><code>${serialNumber}</code></td>
                <td><span class="badge bg-primary">${quantity}</span></td>
                <td>KCC</td>
                <td>${statusBadge}</td>
            </tr>
        `;
        });

        // Add "more items" row if there are more than 10
        if (batchItems.length > 10) {
            html += `
            <tr class="table-info">
                <td colspan="5" class="text-center">
                    <i class="fas fa-ellipsis-h me-2"></i>
                    ... and ${batchItems.length - 10} more items
                </td>
            </tr>
        `;
        }

        tableBody.innerHTML = html;
    }

    async function submitBatch() {
        if (batchItems.length === 0) {
            showNotification('warning', 'No items to submit');
            return;
        }

        // Get all form values
        const batchAction = document.getElementById('batchAction').value;
        const batchLocation = document.getElementById('batchLocation').value;
        const batchNotes = document.getElementById('batchNotes').value;
        const stockLocation = document.getElementById('stockLocation').value;
        const eventName = document.getElementById('eventName').value;
        const jobSheet = document.getElementById('jobSheet').value;
        const projectManager = document.getElementById('projectManager').value;
        const vehicleNumber = document.getElementById('vehicleNumber').value;
        const driverName = document.getElementById('driverName').value;
        const requestedBy = document.getElementById('requestedBy').value;
        const approvedBy = document.getElementById('approvedBy').value;
        const approvalNotes = document.getElementById('approvalNotes').value;

        // Validate required fields
        const requiredFields = [{
                field: stockLocation,
                name: 'Stock Location'
            },
            {
                field: eventName,
                name: 'Event Name'
            },
            {
                field: jobSheet,
                name: 'Job Sheet'
            },
            {
                field: projectManager,
                name: 'Project Manager'
            },
            {
                field: vehicleNumber,
                name: 'Vehicle Number'
            },
            {
                field: driverName,
                name: 'Driver Name'
            },
            {
                field: requestedBy,
                name: 'Requested By'
            },
            {
                field: approvedBy,
                name: 'Approved By'
            }
        ];

        for (const req of requiredFields) {
            if (!req.field || req.field.trim() === '') {
                showNotification('error', `${req.name} is required`);
                return;
            }
        }

        // Prepare batch data for API
        const batchData = {
            items: batchItems.map(item => ({
                id: item.id || item.item_id || 0,
                item_id: item.id || item.item_id || 0,
                name: item.name || item.item_name || 'Unknown Item',
                item_name: item.name || item.item_name || 'Unknown Item',
                serial_number: item.serial_number || item.serial || null,
                serial: item.serial_number || item.serial || null,
                category: item.category || item.category_name || null,
                category_name: item.category || item.category_name || null,
                status: item.status || 'available',
                original_status: item.status || 'available',
                stock_location: item.stock_location || item.location || null,
                location: item.stock_location || item.location || null,
                quantity: item.quantity || 1,
                added_at: item.added_at || new Date().toISOString(),
                notes: item.notes || null,
                manual_entry: item.manual_entry || false
            })),
            action: batchAction,
            location: batchLocation || stockLocation, // Use batch location or default to stock location
            notes: batchNotes,
            job_details: {
                stock_location: stockLocation,
                event_name: eventName,
                job_sheet: jobSheet,
                project_manager: projectManager,
                vehicle_number: vehicleNumber,
                driver_name: driverName,
                requested_by: requestedBy,
                approved_by: approvedBy,
                approval_notes: approvalNotes
            },
            submitted_at: new Date().toISOString(),
            submitted_by: 'current_user' // Will be set by session on backend
        };

        console.log('📦 Submitting batch to API:', batchData);

        try {
            // Show loading
            const submitBtn = document.getElementById('submitBatchBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Submitting...';
            submitBtn.disabled = true;

            // Send to API
            const response = await fetch('api/batch/submit.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(batchData)
            });

            const responseText = await response.text();
            let data;

            try {
                data = JSON.parse(responseText);
            } catch (e) {
                console.error('❌ Failed to parse JSON:', e);
                throw new Error('Invalid server response: ' + responseText.substring(0, 200));
            }

            if (!data.success) {
                throw new Error(data.message || 'Batch submission failed');
            }

            // Success
            showNotification('success', 'Batch submitted successfully!');

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('batchSubmitModal'));
            modal.hide();

            // Clear local storage if API indicates success
            if (data.clear_storage) {
                clearLocalStorage();
            }

            // Clear batch UI
            batchItems = [];
            updateBatchUI();

            // Reset scan count
            scanCount = 0;
            document.getElementById('scanCount').textContent = '0 scans';

            // Reset button
            submitBtn.innerHTML = originalText;

            console.log('✅ Batch submitted successfully:', data);

            // Show success message with batch ID
            setTimeout(() => {
                if (data.batch_id) {
                    showNotification('info', `Batch ID: ${data.batch_id}. You can view it in the batch history.`);
                }
            }, 1000);

        } catch (error) {
            console.error('❌ Batch submission error:', error);
            showNotification('error', 'Error submitting batch: ' + error.message);

            // Reset button
            const submitBtn = document.getElementById('submitBatchBtn');
            submitBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Submit Batch';
            submitBtn.disabled = !document.getElementById('confirmBatchSubmit').checked;
        }
    }

    function printBatchSummary() {
        // Create a printable summary
        const printContent = `
        <html>
            <head>
                <title>Batch Summary - ${document.getElementById('jobSheet').value}</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 20px; }
                    .section { margin-bottom: 25px; }
                    .section-title { background: #f0f0f0; padding: 10px; font-weight: bold; margin-bottom: 10px; }
                    .row { display: flex; margin-bottom: 8px; }
                    .col { flex: 1; }
                    .col-label { font-weight: bold; min-width: 150px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f5f5f5; }
                    .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #000; }
                    .signature { margin-top: 40px; }
                    @media print {
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>Equipment Batch Transfer Summary</h1>
                    <h3>Job Sheet: ${document.getElementById('jobSheet').value}</h3>
                    <p>Generated: ${new Date().toLocaleString()}</p>
                </div>
                
                <div class="section">
                    <div class="section-title">Job Details</div>
                    <div class="row">
                        <div class="col col-label">Stock Location:</div>
                        <div class="col">${document.getElementById('stockLocation').value}</div>
                    </div>
                    <div class="row">
                        <div class="col col-label">Event Name:</div>
                        <div class="col">${document.getElementById('eventName').value}</div>
                    </div>
                    <div class="row">
                        <div class="col col-label">Job Sheet:</div>
                        <div class="col">${document.getElementById('jobSheet').value}</div>
                    </div>
                    <div class="row">
                        <div class="col col-label">Project Manager:</div>
                        <div class="col">${document.getElementById('projectManager').value}</div>
                    </div>
                </div>
                
                <div class="section">
                    <div class="section-title">Transport Details</div>
                    <div class="row">
                        <div class="col col-label">Vehicle Number:</div>
                        <div class="col">${document.getElementById('vehicleNumber').value}</div>
                    </div>
                    <div class="row">
                        <div class="col col-label">Driver Name:</div>
                        <div class="col">${document.getElementById('driverName').value}</div>
                    </div>
                </div>
                
                <div class="section">
                    <div class="section-title">Approval Details</div>
                    <div class="row">
                        <div class="col col-label">Requested By:</div>
                        <div class="col">${document.getElementById('requestedBy').value}</div>
                    </div>
                    <div class="row">
                        <div class="col col-label">Approved By:</div>
                        <div class="col">${document.getElementById('approvedBy').value}</div>
                    </div>
                </div>
                
                <div class="section">
                    <div class="section-title">Items Summary (${batchItems.length} items)</div>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Item</th>
                                <th>Serial Number</th>
                                <th>Qty</th>
                                <th>Destination</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${batchItems.map((item, index) => `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${item.name || item.item_name || 'Unknown Item'}</td>
                                    <td><code>${item.serial_number || item.serial || 'N/A'}</code></td>
                                    <td>${item.quantity || 1}</td>
                                    <td>KCC</td>
                                    <td>${item.status || 'available'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
                
                <div class="footer">
                    <div class="signature">
                        <div style="float: left; width: 45%;">
                            <p>_______________________</p>
                            <p><strong>Driver Signature</strong></p>
                            <p>Date: _________________</p>
                        </div>
                        <div style="float: right; width: 45%;">
                            <p>_______________________</p>
                            <p><strong>Site Supervisor Signature</strong></p>
                            <p>Date: _________________</p>
                        </div>
                        <div style="clear: both;"></div>
                    </div>
                </div>
                
                <div class="no-print" style="margin-top: 20px; text-align: center;">
                    <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer;">
                        Print Document
                    </button>
                    <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; cursor: pointer; margin-left: 10px;">
                        Close
                    </button>
                </div>
            </body>
        </html>
    `;

        const printWindow = window.open('', '_blank');
        printWindow.document.write(printContent);
        printWindow.document.close();
    }

    // Update the existing submit button event listener
    document.addEventListener('DOMContentLoaded', function() {
        // ... existing code ...

        // Update the confirmation checkbox listener
        document.getElementById('confirmBatchSubmit').addEventListener('change', function() {
            // Also check if all required fields are filled
            const requiredFields = [
                'stockLocation', 'eventName', 'jobSheet', 'projectManager',
                'vehicleNumber', 'driverName', 'requestedBy', 'approvedBy'
            ];

            let allFieldsValid = true;
            requiredFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field && (!field.value || field.value.trim() === '')) {
                    allFieldsValid = false;
                }
            });

            document.getElementById('submitBatchBtn').disabled = !(this.checked && allFieldsValid);
        });

        // Add real-time validation for required fields
        const requiredFields = [
            'stockLocation', 'eventName', 'jobSheet', 'projectManager',
            'vehicleNumber', 'driverName', 'requestedBy', 'approvedBy'
        ];

        requiredFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('input', function() {
                    const confirmCheckbox = document.getElementById('confirmBatchSubmit');
                    if (confirmCheckbox.checked) {
                        let allFieldsValid = true;
                        requiredFields.forEach(fieldId => {
                            const field = document.getElementById(fieldId);
                            if (field && (!field.value || field.value.trim() === '')) {
                                allFieldsValid = false;
                            }
                        });
                        document.getElementById('submitBatchBtn').disabled = !allFieldsValid;
                    }
                });
            }
        });
    });

    async function submitBatch() {
        if (batchItems.length === 0) {
            showNotification('warning', 'No items to submit');
            return;
        }

        const batchAction = document.getElementById('batchAction').value;
        const batchLocation = document.getElementById('batchLocation').value;
        const batchNotes = document.getElementById('batchNotes').value;

        // Prepare batch data for API
        const batchData = {
            items: batchItems.map(item => ({
                id: item.id || item.item_id || 0,
                item_id: item.id || item.item_id || 0,
                name: item.name || item.item_name || 'Unknown Item',
                item_name: item.name || item.item_name || 'Unknown Item',
                serial_number: item.serial_number || item.serial || null,
                serial: item.serial_number || item.serial || null,
                category: item.category || item.category_name || null,
                category_name: item.category || item.category_name || null,
                status: item.status || 'available',
                original_status: item.status || 'available',
                stock_location: item.stock_location || item.location || null,
                location: item.stock_location || item.location || null,
                quantity: item.quantity || 1,
                added_at: item.added_at || new Date().toISOString(),
                notes: item.notes || null,
                manual_entry: item.manual_entry || false
            })),
            action: batchAction,
            location: batchLocation,
            notes: batchNotes,
            submitted_at: new Date().toISOString(),
            submitted_by: 'current_user' // Will be set by session on backend
        };

        console.log('📦 Submitting batch to API:', batchData);

        try {
            // Show loading
            const submitBtn = document.getElementById('submitBatchBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Submitting...';
            submitBtn.disabled = true;

            // Send to API
            const response = await fetch('api/batch/submit.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(batchData)
            });

            const responseText = await response.text();
            let data;

            try {
                data = JSON.parse(responseText);
            } catch (e) {
                console.error('❌ Failed to parse JSON:', e);
                throw new Error('Invalid server response: ' + responseText.substring(0, 200));
            }

            if (!data.success) {
                throw new Error(data.message || 'Batch submission failed');
            }

            // Success
            showNotification('success', data.message || 'Batch submitted successfully!');

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('batchSubmitModal'));
            modal.hide();

            // Clear local storage if API indicates success
            if (data.clear_storage) {
                clearLocalStorage();
            }

            // Clear batch UI
            batchItems = [];
            updateBatchUI();

            // Reset scan count
            scanCount = 0;
            document.getElementById('scanCount').textContent = '0 scans';

            // Reset button
            submitBtn.innerHTML = originalText;

            console.log('✅ Batch submitted successfully:', data);

            // Optionally redirect to batch view or show success page
            setTimeout(() => {
                if (data.batch_id) {
                    showNotification('info', `Batch ID: ${data.batch_id}. You can view it in the batch history.`);
                }
            }, 1000);

        } catch (error) {
            console.error('❌ Batch submission error:', error);
            showNotification('error', 'Error submitting batch: ' + error.message);

            // Reset button
            const submitBtn = document.getElementById('submitBatchBtn');
            submitBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Submit Batch';
            submitBtn.disabled = !document.getElementById('confirmBatchSubmit').checked;
        }
    }

    function clearLocalStorage() {
        try {
            localStorage.removeItem('batch_items');
            localStorage.removeItem('batch_scan_count');
            localStorage.removeItem('recent_items');
            console.log('🗑️ Local storage cleared');
        } catch (e) {
            console.error('Error clearing local storage:', e);
        }
    }

    function createSuccessModal(data) {
        // Create modal HTML
        const modalHtml = `
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="successModalLabel">
                        <i class="fas fa-check-circle me-2"></i>Batch Submitted Successfully!
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-success">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle fa-2x me-3"></i>
                            <div>
                                <h5 class="mb-1">Batch Processed</h5>
                                <p class="mb-0">Your batch has been successfully saved to the database.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Batch Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Batch ID:</strong><br>
                                    <code class="fs-6">${data.batch_id}</code></p>
                                    <p><strong>Submitted At:</strong><br>
                                    ${new Date().toLocaleString()}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Total Items:</strong><br>
                                    <span class="badge bg-primary fs-6">${data.total_items}</span></p>
                                    <p><strong>Unique Items:</strong><br>
                                    <span class="badge bg-secondary fs-6">${data.unique_items}</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-list-check me-2"></i>Processed Items</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Serial</th>
                                            <th>Status</th>
                                            <th>Location</th>
                                            <th>Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody id="processedItemsTable">
                                        ${data.processed_list ? data.processed_list.map(item => `
                                            <tr>
                                                <td>${item.name}</td>
                                                <td><code>${item.serial || 'N/A'}</code></td>
                                                <td><span class="badge bg-${getStatusColor(item.status)}">${item.status}</span></td>
                                                <td>${item.location || 'N/A'}</td>
                                                <td>${item.quantity || 1}</td>
                                            </tr>
                                        `).join('') : '<tr><td colspan="5">No items processed</td></tr>'}
                                    </tbody>
                                </table>
                            </div>
                            
                            ${data.errors && data.errors.length > 0 ? `
                            <div class="alert alert-warning mt-3">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>Items with Errors</h6>
                                <ul class="mb-0">
                                    ${data.errors.map(error => `<li>${error}</li>`).join('')}
                                </ul>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Close
                    </button>
                    <button type="button" class="btn btn-primary" onclick="printBatchReceipt('${data.batch_id}')">
                        <i class="fas fa-print me-1"></i> Print Receipt
                    </button>
                    <a href="batch_report.php?id=${data.batch_id}" class="btn btn-success" target="_blank">
                        <i class="fas fa-file-alt me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div>
    </div>
    `;

        // Add modal to page
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('successModal'));
        modal.show();

        // Clean up modal on close
        document.getElementById('successModal').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
    }

    function clearBatchCompletely() {
        batchItems = [];
        scanCount = 0;
        updateBatchUI();

        // Clear localStorage
        localStorage.removeItem('batch_items');
        localStorage.removeItem('batch_scan_count');
        localStorage.removeItem('recent_items');

        // Update UI elements
        document.getElementById('scanCount').textContent = '0 scans';

        console.log('🧹 Batch completely cleared');
    }

    function showNotification(type, message, isHtml = false) {
        // Remove existing notifications
        const existing = document.querySelectorAll('.alert-notification');
        existing.forEach(el => el.remove());

        // Create notification
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show`;
        notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 500px;';

        if (isHtml) {
            notification.innerHTML = message;
        } else {
            notification.innerHTML = `
            ${type === 'success' ? '<i class="fas fa-check-circle me-2"></i>' : 
              type === 'error' ? '<i class="fas fa-exclamation-triangle me-2"></i>' : 
              '<i class="fas fa-info-circle me-2"></i>'}
            ${message}
        `;
        }

        notification.innerHTML += `
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;

        document.body.appendChild(notification);

        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 8000);
    }

    // ==================== UTILITY FUNCTIONS ====================

    function generateBatchId() {
        return 'BATCH-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    }

    function formatTime(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function playSuccessSound() {
        try {
            const audioContext = new(window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.value = 1000;
            gainNode.gain.value = 0.1;

            oscillator.start();
            setTimeout(() => oscillator.stop(), 200);
        } catch (e) {
            // Ignore sound errors
        }
    }

    function saveBatchToStorage() {
        try {
            localStorage.setItem('batch_items', JSON.stringify(batchItems));
            localStorage.setItem('batch_scan_count', scanCount.toString());
        } catch (e) {
            console.error('❌ Error saving to localStorage:', e);
        }
    }

    function loadBatchFromStorage() {
        try {
            const savedBatch = localStorage.getItem('batch_items');
            const savedScanCount = localStorage.getItem('batch_scan_count');

            if (savedBatch) {
                batchItems = JSON.parse(savedBatch);
            }
            if (savedScanCount) {
                scanCount = parseInt(savedScanCount);
                document.getElementById('scanCount').textContent = scanCount + ' scans';
            }

            updateBatchUI();
        } catch (e) {
            console.error('❌ Error loading from localStorage:', e);
        }
    }

    // ==================== TEST FUNCTIONS ====================

    function testScanJSON() {
        const testData = {
            id: Math.floor(Math.random() * 1000) + 1,
            name: "Test Equipment " + (Math.floor(Math.random() * 10) + 1),
            serial_number: "TEST-" + Math.random().toString(36).substr(2, 6).toUpperCase(),
            category: ["Electronics", "Tools", "Furniture", "Vehicles"][Math.floor(Math.random() * 4)],
            status: ["available", "in_use", "maintenance"][Math.floor(Math.random() * 3)],
            stock_location: ["Warehouse A", "Site Office", "Storage Room"][Math.floor(Math.random() * 3)],
            quantity: Math.floor(Math.random() * 3) + 1
        };
        processScan(JSON.stringify(testData));
    }

    function testScanNumber() {
        const randomId = Math.floor(Math.random() * 1000) + 1;
        processScan(randomId.toString());
    }

    function addRandomTestItem() {
        const testItems = [{
                id: 101,
                name: "Laptop Dell XPS 15",
                serial_number: "DLXPS15-001",
                category: "Electronics",
                status: "available",
                stock_location: "IT Department",
                quantity: 1
            },
            {
                id: 102,
                name: "Power Drill",
                serial_number: "PD-2024-001",
                category: "Tools",
                status: "in_use",
                stock_location: "Construction Site",
                quantity: 2
            },
            {
                id: 103,
                name: "Office Chair",
                serial_number: "OC-ERG-001",
                category: "Furniture",
                status: "available",
                stock_location: "Warehouse B",
                quantity: 1
            },
            {
                id: 104,
                name: "Projector",
                serial_number: "PROJ-4K-001",
                category: "Electronics",
                status: "maintenance",
                stock_location: "Repair Room",
                quantity: 1
            },
            {
                id: 105,
                name: "Safety Helmet",
                serial_number: "SH-2024-001",
                category: "Safety Equipment",
                status: "available",
                stock_location: "Storage Room",
                quantity: 5
            }
        ];

        const randomItem = testItems[Math.floor(Math.random() * testItems.length)];
        addToBatch(randomItem);
    }

    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        if (html5QrCode && isScanning) {
            html5QrCode.stop().catch(e => console.log('Stop error on unload:', e));
        }
        saveBatchToStorage();
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.code === 'Space' && !e.target.matches('input, textarea, select')) {
            e.preventDefault();
            if (isScanning) {
                stopScanner();
            } else {
                startScanner();
            }
        }

        if (e.code === 'Escape') {
            clearBatch();
        }

        // Ctrl+Enter to submit batch
        if (e.ctrlKey && e.code === 'Enter' && batchItems.length > 0) {
            e.preventDefault();
            openBatchModal();
        }

        // Tab switching shortcuts
        if (e.ctrlKey && e.code === 'Digit1') {
            e.preventDefault();
            document.getElementById('scan-tab').click();
        }

        if (e.ctrlKey && e.code === 'Digit2') {
            e.preventDefault();
            document.getElementById('manual-tab').click();
        }
    });

    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#manualSearch') && !e.target.closest('#searchResults')) {
            hideSearchResults();
        }
    });
</script>

<?php
require_once 'views/partials/footer.php';
?>