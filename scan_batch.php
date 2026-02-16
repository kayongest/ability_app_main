<?php
// scan_batch.php - For scanning multiple items at once
require_once 'bootstrap.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$pageTitle = "Batch Scan - aBility";
$showBreadcrumb = true;
$breadcrumbItems = [
    'Dashboard' => 'dashboard.php',
    'Batch Scan' => ''
];

require_once 'views/partials/header.php';
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://unpkg.com/html5-qrcode"></script>

<div class="container-fluid">
    <h1 class="h3 mb-4">Batch Scan</h1>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div id="reader" style="width: 100%; min-height: 400px;"></div>
                    <button id="startBatch" class="btn btn-primary mt-3">Start Scanning</button>
                    <button id="stopBatch" class="btn btn-secondary mt-3" disabled>Stop</button>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5>Scanned Items (<span id="count">0</span>)</h5>
                </div>
                <div class="card-body">
                    <div id="batchList"></div>
                    <div class="mt-3">
                        <select class="form-select" id="batchAction">
                            <option value="check_in">Check In All</option>
                            <option value="check_out">Check Out All</option>
                            <option value="inventory">Inventory Check</option>
                        </select>
                        <input type="text" class="form-control mt-2" id="batchLocation" placeholder="Location">
                        <button id="processBatch" class="btn btn-success w-100 mt-2">Process Batch</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Batch scanning implementation
let batchItems = [];

// Initialize batch scanning
$(document).ready(function() {
    // ... similar to single scan but accumulates items ...
});
</script>

<?php require_once 'views/partials/footer.php'; ?>