<?php
// At the VERY TOP of scan_2.php
session_start();

// DEBUG SESSION
error_log("=== SCAN_2.PHP SESSION DEBUG ===");
error_log("Session ID: " . session_id());
error_log("User ID: " . ($_SESSION['user_id'] ?? 'NOT SET'));
error_log("Username: " . ($_SESSION['username'] ?? 'NOT SET'));
error_log("Session save path: " . session_save_path());
error_log("Session cookie params: " . json_encode(session_get_cookie_params()));


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Make sure user data is available
$_SESSION['username'] = $_SESSION['username'] ?? 'Stock Controller';
$_SESSION['full_name'] = $_SESSION['full_name'] ?? $_SESSION['username'];

// Debug session - ADD MORE DETAILED DEBUGGING
error_log("=== SCAN_2.PHP STARTED ===");
error_log("Session ID: " . session_id());
error_log("Session status: " . session_status());

// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    error_log("Session started manually in scan_2.php");
} else {
    error_log("Session already started");
}

// Then include bootstrap - THIS WILL DEFINE BASE_URL
require_once 'bootstrap.php';

// Debug after bootstrap
error_log("=== AFTER BOOTSTRAP INCLUSION ===");
error_log("BASE_URL is: " . (defined('BASE_URL') ? BASE_URL : 'NOT DEFINED'));
error_log("Available session variables: " . json_encode($_SESSION));

// Check authentication
$isLoggedIn = isLoggedIn();
error_log("Checking isLoggedIn(): " . ($isLoggedIn ? 'TRUE' : 'FALSE'));

if (!$isLoggedIn) {
    error_log("User not logged in, redirecting to login.php");

    // Store where we came from for redirect back
    $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
    error_log("Stored redirect_to: " . $_SERVER['REQUEST_URI']);

    // Add flash message
    $_SESSION['flash_messages']['warning'] = 'Please log in to access the scanner.';

    // Redirect to login
    header('Location: login.php');
    exit();
}

error_log("User is logged in, continuing...");

require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$pageTitle = "Scan & Batch Items - aBility";
$showBreadcrumb = true;
$breadcrumbItems = [
    'Dashboard' => 'dashboard.php',
    'Scan & Batch Items' => ''
];

require_once 'views/partials/header.php';

// Display flash messages if any
if (isset($_SESSION['flash_messages'])) {
    foreach ($_SESSION['flash_messages'] as $type => $message) {
        echo '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($message);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
    // Clear flash messages after displaying
    unset($_SESSION['flash_messages']);
}

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

// Display current user info for debugging
echo '<!-- DEBUG: User logged in as: ' . htmlspecialchars($_SESSION['username'] ?? 'Unknown') . ' -->';
echo '<!-- DEBUG: User ID: ' . htmlspecialchars($_SESSION['user_id'] ?? 'Not set') . ' -->';
echo '<!-- DEBUG: User Role: ' . htmlspecialchars($_SESSION['user_role'] ?? 'Not set') . ' -->';
?>

<!-- Load the library from local assets -->
<script src="assets/js/html5-qrcode.min.js"></script>

<style>
    /* Success Modal Styles */
    .success-icon {
        animation: successScale 0.5s ease-out;
    }

    @keyframes successScale {
        0% {
            transform: scale(0.5);
            opacity: 0;
        }

        70% {
            transform: scale(1.1);
            opacity: 1;
        }

        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    #redirectCountdown {
        font-size: 1.2em;
        animation: pulse 1s infinite;
    }

    @keyframes pulse {
        0% {
            opacity: 1;
        }

        50% {
            opacity: 0.6;
        }

        100% {
            opacity: 1;
        }
    }

    /* Center the modal */
    .modal-dialog-centered {
        display: flex;
        align-items: center;
        min-height: calc(100% - 1rem);
    }

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
    }

    #qr-reader {
        width: 100% !important;
        min-height: 400px;
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

    /* Mobile Torch Button */
    #torchBtn.active {
        background-color: #ffc107 !important;
        border-color: #ffc107 !important;
        box-shadow: 0 0 10px rgba(255, 193, 7, 0.5);
    }

    /* Mobile Fullscreen */
    :fullscreen #scanner-container {
        height: 150vh !important;
    }

    /* Mobile Safe Areas */
    @supports (padding: max(0px)) {
        .panel-footer {
            padding-left: max(15px, env(safe-area-inset-left));
            padding-right: max(15px, env(safe-area-inset-right));
            padding-bottom: max(15px, env(safe-area-inset-bottom));
        }
    }

    /* Mobile Landscape */
    @media (orientation: landscape) and (max-height: 500px) {
        .split-container {
            flex-direction: row !important;
        }

        .left-panel,
        .right-panel {
            height: 90vh !important;
        }

        #scanner-container {
            height: 200px !important;
        }
    }

    /* Prevent zoom on input focus for mobile */
    @media (max-width: 768px) {

        input,
        select,
        textarea {
            font-size: 16px !important;
        }
    }

    /* Mobile tap highlights */
    .btn,
    .item-card {
        -webkit-tap-highlight-color: rgba(0, 0, 0, 0.1);
    }

    /* Mobile camera fixes */
    @media (max-width: 768px) {
        #cameraSelect {
            font-size: 16px !important;
            /* Prevents zoom on focus */
            height: 44px;
            /* Minimum touch target size */
        }

        #qr-reader video {
            object-fit: cover !important;
            transform: scale(1.1);
            /* Helps fill the viewport */
        }

        /* Better camera testing display */
        #cameraTestResults video {
            width: 80px !important;
            height: 60px !important;
        }
    }

    /* Safari-specific fixes */
    @supports (-webkit-touch-callout: none) {
        #qr-reader {
            -webkit-transform: translateZ(0);
            transform: translateZ(0);
        }
    }

    /* Fix for iOS Safari fullscreen */
    :-webkit-full-screen #scanner-container {
        background: #000 !important;
    }

    :-webkit-full-screen #qr-reader {
        height: 100vh !important;
        width: 100vw !important;
    }
</style>

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
                <!-- Authentication Section (Always Visible First) -->
                <div id="authenticationSection" class="card mb-4 border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="fas fa-user-lock me-2"></i>Technician Authentication Required</h6>
                    </div>
                    <div class="card-body">
                        <form id="technicianAuthForm" onsubmit="authenticateTechnicianAsStockController(); return false;">
                            <h6 class="mb-3">
                                <i class="fas fa-user-shield me-1"></i>Technician Authentication
                                <small class="text-muted ms-2">(Verified by Stock Controller)</small>
                            </h6>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Select Technician</label>
                                    <select id="technicianSelect" class="form-select" required>
                                        <option value="">-- Select Technician --</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a technician.</div>
                                    <div id="authTechnicianInfo" class="mt-2 d-none"></div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Technician Password</label>
                                    <div class="input-group has-validation">
                                        <input type="password" id="technicianPassword" class="form-control"
                                            placeholder="Enter technician's password"
                                            autocomplete="current-password"
                                            required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <div class="invalid-feedback">Please enter the technician's password.</div>
                                    </div>
                                    <div id="authStatus" class="mt-2"></div>
                                </div>
                            </div>

                            <div class="text-center mt-3">
                                <button id="authenticateBtn" type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-check me-1"></i>Verify & Continue
                                </button>
                                <button type="button" class="btn btn-secondary ms-2" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Main Batch Form (Hidden until authentication) -->
                <div id="batchFormSection" class="d-none">
                    <!-- Authentication Success -->
                    <div id="authSuccessSection">
                        <!-- Top Info Section -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="alert alert-success">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-check-circle fa-2x me-3"></i>
                                        <div>
                                            <h6 class="mb-1"><i class="fas fa-user-check me-1"></i> Technician Authenticated</h6>
                                            <p class="mb-0"><strong id="authenticatedTechnicianName"></strong> successfully authenticated</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user-tie fa-2x me-3"></i>
                                        <div>
                                            <h6 class="mb-1"><i class="fas fa-user-shield me-1"></i> Stock Controller</h6>
                                            <p class="mb-0">Logged in as: <strong id="submittedByDisplay"><?php echo htmlspecialchars($_SESSION['username'] ?? 'System'); ?></strong></p>
                                            <small>Full Name: <span id="submittedByFullName"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Stock Controller'); ?></span></small>
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
                                        <form id="jobDetailsForm">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label for="stockLocation" class="form-label">
                                                        <i class="fas fa-warehouse me-1"></i> Stock Location *
                                                    </label>
                                                    <select id="stockLocation" class="form-select" required>
                                                        <option value="">-- Select Location --</option>
                                                        <option value="KCC">KCC</option>
                                                        <option value="Warehouse A">Warehouse A</option>
                                                        <option value="Site Office">Site Office</option>
                                                        <option value="Storage Room">Storage Room</option>
                                                    </select>
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
                                        </form>
                                    </div>
                                </div>

                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-user-check me-2"></i>Approval Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <form id="approvalForm">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label for="requestedBy" class="form-label">
                                                        <i class="fas fa-user-edit me-1"></i> Requested By *
                                                    </label>
                                                    <input type="text" class="form-control bg-light" id="requestedBy"
                                                        placeholder="Select and verify technician first" readonly>
                                                    <small class="text-muted">Auto-filled after technician verification</small>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="submittedBy" class="form-label">
                                                        <i class="fas fa-user-shield me-1"></i> Submitted By *
                                                    </label>
                                                    <input type="text" class="form-control bg-light" id="submittedBy"
                                                        value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Stock Controller'); ?>" readonly>
                                                    <small class="text-muted">Stock Controller</small>
                                                </div>
                                                <div class="col-md-12">
                                                    <label for="approvalNotes" class="form-label">
                                                        <i class="fas fa-sticky-note me-1"></i> Approval Notes
                                                    </label>
                                                    <textarea class="form-control" id="approvalNotes" rows="2"
                                                        placeholder="Notes from stock controller approval..."></textarea>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Items Summary -->
                            <div class="col-md-6">
                                <!-- Items Preview Card -->
                                <div class="card mb-4">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i class="fas fa-boxes me-2"></i>Items Summary
                                            <span class="badge bg-warning ms-2" id="batchItemCount">0</span>
                                        </h6>
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
                                        <form id="batchActionsForm">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="batchAction" class="form-label">Apply Action to All Items</label>
                                                    <select class="form-select" id="batchAction">
                                                        <option value="">No Action (Keep Current)</option>
                                                        <option value="release">Release for Use</option>
                                                        <option value="transfer">Transfer to Location</option>
                                                        <option value="maintenance">Send for Maintenance</option>
                                                        <option value="return">Return to Stock</option>
                                                        <option value="check_in">Check In All</option>
                                                        <option value="check_out">Check Out All</option>
                                                        <option value="available">Mark All as Available</option>
                                                    </select>
                                                    <small class="form-text text-muted">This will override individual item statuses</small>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="batchLocation" class="form-label">Set Destination for All Items</label>
                                                    <input type="text" class="form-control" id="batchLocation" value="KCC" placeholder="Where items are going">
                                                    <small class="form-text text-muted">Default: KCC (change if needed)</small>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Additional Notes -->
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-comments me-2"></i>Additional Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <form id="notesForm">
                                            <div class="mb-3">
                                                <label for="batchNotes" class="form-label">
                                                    <i class="fas fa-sticky-note me-1"></i>Batch Notes
                                                </label>
                                                <textarea class="form-control" id="batchNotes" rows="2"
                                                    placeholder="Purpose, special instructions, or additional details..."></textarea>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Confirmation Section -->
                        <div class="card mt-4 border-primary">
                            <div class="card-body">
                                <form id="confirmationForm">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="confirmBatchSubmit" required>
                                        <label class="form-check-label" for="confirmBatchSubmit">
                                            <i class="fas fa-shield-alt me-1"></i>
                                            <strong>I confirm that:</strong>
                                            <ul class="mb-0 mt-2">
                                                <li>Technician <strong id="confirmationTechnicianName"></strong> has been authenticated</li>
                                                <li>All items listed are accurate and verified</li>
                                                <li>Job details and approval information are correct</li>
                                                <li>This submission is authorized for processing</li>
                                                <li>Submitted by: <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Stock Controller'); ?></strong> (Stock Controller)</li>
                                            </ul>
                                        </label>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-info" onclick="printBatchSummary()" id="printPreviewBtn" disabled>
                    <i class="fas fa-print me-1"></i> Print Preview
                </button>
                <button type="button" class="btn btn-success" id="submitBatchBtn" disabled>
                    <i class="fas fa-paper-plane me-1"></i> Submit Batch
                </button>
            </div>
        </div>
    </div>
</div>


<!-- Success Modal -->
<div class="modal fade" id="successModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-4">
                    <div class="success-icon d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10"
                        style="width: 80px; height: 80px; margin: 0 auto;">
                        <i class="fas fa-check fa-3x text-success"></i>
                    </div>
                </div>
                <h4 class="mb-3 text-success">Batch Submitted Successfully!</h4>
                <p class="mb-4 text-muted">
                    Your batch has been successfully submitted and is now being processed.
                    <br>
                    <span id="redirectCountdown" class="fw-bold text-primary">5</span> seconds remaining...
                </p>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary" onclick="redirectToBatchHistory()">
                        <i class="fas fa-eye me-2"></i>View Batch History
                    </button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Close
                    </button>
                </div>
            </div>
            <div class="modal-footer border-0 text-center">
                <small class="text-muted">Batch ID: <span id="successBatchId">N/A</span></small>
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
                    <button class="btn btn-sm btn-primary" id="openBatchModalBtn" disabled>
                        <i class="fas fa-external-link-alt me-1"></i> Review & Submit
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // ==================== OVERRIDE EXTERNAL FUNCTIONS ====================
    // REMOVED window.processScan = null; - THIS WAS THE BUG!
    window.onScanSuccess = null;
    // window.addToBatch = null;

    // ==================== GLOBAL VARIABLES ====================
    let html5QrCode = null;
    let isScanning = false;
    let currentCameraId = null;
    let availableCameras = [];
    let lastScanTimestamp = 0;
    const SCAN_COOLDOWN = 1000;
    let batchItems = [];
    let scanCount = 0;
    let recentItems = [];
    let isTechnicianAuthenticated = false;
    let authenticatedTechnician = null;
    let modalOpenInProgress = false;
    let isSubmitting = false;

    // ==================== BATCH MANAGEMENT FUNCTIONS ====================
    async function processScan(scanData) {
        console.log('🔧 Processing scan data:', scanData);
        const itemId = extractItemId(scanData.trim());
        if (itemId) {
            console.log('📋 Found item ID:', itemId);
            updateScannerStatus('Fetching item data...');
            const itemData = await fetchAndAddToBatch(itemId);
            if (itemData) {
                console.log('✅ Item added to batch:', itemData);
                provideScanVisualFeedback();
                incrementScanCount();
                pauseScannerTemporarily();
            }
        } else {
            console.log('🔍 No ID found, showing raw data');
            showNotification('warning', 'No valid item ID found in scan');
        }
    }

    async function fetchAndAddToBatch(itemId) {
        try {
            console.log('🌐 Fetching item details for ID:', itemId);
            showNotification('info', 'Looking up item...');
            const itemData = await fetchItemData(itemId);
            if (itemData) {
                addToBatch(itemData);
                return itemData;
            } else {
                console.log('❌ Could not fetch item data');
                showNotification('error', 'Could not find item with ID: ' + itemId);
                return null;
            }
        } catch (error) {
            console.error('❌ Fetch error:', error);
            showNotification('error', 'Error fetching item details: ' + error.message);
            return null;
        }
    }

    async function fetchItemData(itemId) {
        console.log('🌐 Fetching item details for ID:', itemId);
        const apiUrl = `api/items/get_simple.php?id=${itemId}`;
        try {
            console.log(`Trying API: ${apiUrl}`);
            const response = await fetch(apiUrl);
            if (response.ok) {
                const data = await response.json();
                console.log('✅ API Response:', data);
                if (data.success && data.item) {
                    console.log('✅ Found item:', data.item);
                    showNotification('success', `Found: ${data.item.name}`);
                    return data.item;
                } else {
                    console.warn('⚠️ API returned success=false:', data.message);
                    showNotification('warning', data.message || 'Item not found');
                }
            } else {
                console.warn(`⚠️ API returned status: ${response.status}`);
                showNotification('warning', `Server error: ${response.status}`);
            }
        } catch (error) {
            console.error('❌ Fetch error:', error);
            showNotification('error', 'Network error: ' + error.message);
        }
        console.warn('⚠️ Could not fetch item, using fallback');
        const fallbackItem = {
            id: parseInt(itemId),
            item_id: parseInt(itemId),
            name: `Item ${itemId}`,
            item_name: `Item ${itemId}`,
            serial_number: `SN-${itemId}`,
            serial: `SN-${itemId}`,
            category: 'Equipment',
            category_name: 'Equipment',
            status: 'available',
            stock_location: 'Ndera Stock',
            location: 'Ndera Stock',
            quantity: 1
        };
        showNotification('info', `Using fallback data for item ${itemId}`);
        return fallbackItem;
    }

    function addToBatch(item) {
        console.log('➕ Adding to batch:', item);
        const existingIndex = findItemInBatch(item);
        if (existingIndex !== -1) {
            updateExistingItem(existingIndex, item);
        } else {
            addNewItemToBatch(item);
        }
        updateBatchUI();
        saveBatchToStorage();
        playSuccessSound();
    }

    // Add this debug function right after addToBatch
    function debugAddToBatch() {
        console.log('🔍 DEBUG: addToBatch function exists:', typeof addToBatch);
        console.log('🔍 DEBUG: window.addToBatch exists:', typeof window.addToBatch);
        console.log('🔍 DEBUG: batchItems array length:', batchItems.length);
    }
    window.debugAddToBatch = debugAddToBatch;

    function findItemInBatch(item) {
        return batchItems.findIndex(i =>
            i.id === item.id ||
            i.item_id === item.id ||
            i.serial_number === item.serial_number
        );
    }

    function updateExistingItem(index, item) {
        if (batchItems[index].quantity) {
            batchItems[index].quantity += 1;
        }
        showNotification('warning', `Item "${item.name || item.item_name}" already in batch (quantity updated)`);
    }

    function addNewItemToBatch(item) {
        const newItem = {
            ...item,
            added_at: new Date().toISOString(),
            batch_id: generateBatchId(),
            quantity: item.quantity || 1,
            status: item.status || 'available',
            name: item.name || item.item_name || `Item ${Date.now()}`,
            item_name: item.item_name || item.name || `Item ${Date.now()}`,
            serial_number: item.serial_number || item.serial || '',
            category: item.category || item.category_name || 'Equipment',
            stock_location: item.stock_location || item.location || 'Ndera Stock'
        };
        batchItems.unshift(newItem);
        console.log('✅ New item added to batch:', newItem);
        showNotification('success', `Added "${newItem.name}" to batch`);
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
        const batchCount = batchItems.length;
        updateElementText('totalItems', totalItems);
        updateElementText('availableItems', availableItems);
        updateElementText('inUseItems', inUseItems);
        updateElementText('maintenanceItems', maintenanceItems);
        updateElementText('batchCount', batchCount);
        updateBatchButtons(batchCount);
        updateBatchItemsList();
    }

    function updateBatchButtons(batchCount) {
        const hasItems = batchCount > 0;
        const submitBtn = document.getElementById('submitBatchModalBtn');
        if (submitBtn) {
            submitBtn.disabled = !hasItems;
            const badgeSpan = submitBtn.querySelector('span');
            if (badgeSpan) badgeSpan.textContent = batchCount;
        }
        const clearBtn = document.getElementById('clearBatchBtn');
        if (clearBtn) clearBtn.disabled = !hasItems;
        const openModalBtn = document.getElementById('openBatchModalBtn');
        if (openModalBtn) openModalBtn.disabled = !hasItems;
    }

    function updateBatchItemsList() {
        const itemsList = document.getElementById('batchItemsList');
        if (!itemsList) return;
        if (batchItems.length === 0) {
            itemsList.innerHTML = getEmptyBatchHTML();
        } else {
            itemsList.innerHTML = batchItems.map(item => createItemCardHTML(item)).join('');
        }
    }

    function getEmptyBatchHTML() {
        return `
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
    }

    function createItemCardHTML(item) {
        const itemName = item.name || item.item_name || 'Unknown Item';
        const serialNumber = item.serial_number || item.serial || 'N/A';
        const category = item.category || item.category_name || 'N/A';
        const status = item.status || 'available';
        const location = item.stock_location || item.location || 'N/A';
        const quantity = item.quantity || 1;
        return `
        <div class="item-card new-item" data-item-id="${item.id || item.item_id}">
            <div class="item-header">
                <div class="item-name">${escapeHtml(itemName)}</div>
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
                    <span class="detail-value"><code>${escapeHtml(serialNumber)}</code></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Status</span>
                    <span class="detail-value">
                        <span class="badge-status status-${status}">${status}</span>
                    </span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Category</span>
                    <span class="detail-value">${escapeHtml(category)}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Location</span>
                    <span class="detail-value">${escapeHtml(location)}</span>
                </div>
            </div>
        </div>`;
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

    // ==================== QR PARSING FUNCTIONS ====================
    function parseQRCodeContent(qrContent) {
        console.log('📱 Raw QR content:', qrContent);
        let itemId = 0,
            itemName = '',
            serialNumber = '';
        try {
            if (qrContent.includes('|')) {
                const parts = qrContent.split('|');
                parts.forEach(part => {
                    if (part.startsWith('ID:')) itemId = parseInt(part.substring(3));
                    else if (part.startsWith('SN:')) serialNumber = part.substring(3);
                    else if (part.startsWith('N:')) itemName = part.substring(2);
                });
            } else if (qrContent.startsWith('{')) {
                const data = JSON.parse(qrContent);
                itemId = data.id || data.item_id || 0;
                itemName = data.name || data.item_name || '';
                serialNumber = data.serial || data.serial_number || '';
            } else if (!isNaN(qrContent)) {
                itemId = parseInt(qrContent);
            }
            console.log('✅ Parsed QR data:', {
                itemId,
                itemName,
                serialNumber
            });
            return {
                id: itemId,
                name: itemName,
                serial_number: serialNumber
            };
        } catch (error) {
            console.error('❌ Error parsing QR:', error);
            return {
                id: 0,
                name: qrContent,
                serial_number: ''
            };
        }
    }

    function extractItemId(scanData) {
        console.log('🔍 Extracting ID from:', scanData);
        if (scanData.includes('ID:')) {
            const match = scanData.match(/ID:(\d+)/);
            if (match && match[1]) return match[1];
        }
        if (scanData.startsWith('ID:')) return scanData.substring(3);
        if (scanData.startsWith('ABIL:')) return scanData.substring(5);
        if (scanData.startsWith('ITEM-')) return scanData.substring(5);
        if (/^\d+$/.test(scanData)) return scanData;
        try {
            const parsedData = JSON.parse(scanData);
            return parsedData.i || parsedData.id || parsedData.item_id;
        } catch (e) {
            return null;
        }
    }

    // ==================== SCANNER FUNCTIONS ====================
    async function loadCameras() {
        console.log('📷 Loading cameras...');
        updateScannerStatus('Loading cameras...');
        const cameraSelect = document.getElementById('cameraSelect');
        cameraSelect.disabled = true;
        cameraSelect.innerHTML = '<option value="">Loading cameras...</option>';
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            console.error('❌ Camera API not supported');
            updateScannerStatus('Camera not supported');
            showNotification('info', 'Your browser doesn\'t support camera access. You can still use manual entry.');
            document.getElementById('startBtn').disabled = true;
            return;
        }
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoDevices = devices.filter(device => device.kind === 'videoinput');
            if (videoDevices.length > 0 && videoDevices[0].deviceId) {
                console.log(`✅ Found ${videoDevices.length} camera(s)`);
                availableCameras = videoDevices;
                updateCameraSelect(videoDevices);
                updateScannerStatus('Camera ready');
            } else {
                console.log('🔄 Requesting camera permission...');
                updateCameraSelect([]);
                cameraSelect.innerHTML = '<option value="">Click "Refresh Cameras" or "Start Scanner" to activate</option>';
                updateScannerStatus('Click "Refresh Cameras" to enable');
            }
        } catch (error) {
            console.error('❌ Camera enumeration error:', error);
            updateScannerStatus('Camera access needed');
            cameraSelect.innerHTML = '<option value="">Click "Refresh Cameras" to enable</option>';
        }
    }

    function refreshCameras() {
        console.log('🔄 Refreshing camera list...');
        updateScannerStatus('Refreshing cameras...');
        const cameraSelect = document.getElementById('cameraSelect');
        cameraSelect.innerHTML = '<option value="">Loading cameras...</option>';
        cameraSelect.disabled = true;
        if (html5QrCode && isScanning) {
            stopScanner().then(() => {
                loadCameras();
            });
        } else {
            loadCameras();
        }
    }

    async function handleStartScanner() {
        const cameraSelect = document.getElementById('cameraSelect');
        let cameraId = cameraSelect.value;
        if (!cameraId || cameraId === '' || cameraSelect.options[0].text.includes('Start Scanner')) {
            await actuallyLoadCameras();
            cameraId = cameraSelect.value;
            if (!cameraId) {
                showNotification('warning', 'Please select a camera from the list');
                return;
            }
        }
        await startScannerWithCamera(cameraId);
    }

    async function actuallyLoadCameras() {
        console.log('📷 Actually loading cameras now...');
        updateScannerStatus('Accessing camera...');
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: {
                        ideal: 640
                    },
                    height: {
                        ideal: 480
                    }
                }
            });
            console.log('✅ Camera permission granted');
            stream.getTracks().forEach(track => track.stop());
            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoDevices = devices.filter(device => device.kind === 'videoinput');
            console.log(`✅ Found ${videoDevices.length} camera(s)`);
            availableCameras = videoDevices;
            if (videoDevices.length > 0) {
                updateCameraSelect(videoDevices);
                updateScannerStatus('Camera ready');
            } else {
                updateCameraSelect([]);
                updateScannerStatus('No cameras found');
                showNotification('warning', 'No cameras detected. Check your camera connection.');
            }
        } catch (error) {
            console.error('❌ Camera access error:', error);
            handleCameraError(error);
        }
    }

    function handleCameraError(error) {
        let errorMessage;
        if (error.name === 'NotAllowedError') {
            errorMessage = 'Camera permission denied. Please allow camera access in browser settings.';
            showNotification('warning', errorMessage);
        } else if (error.name === 'NotFoundError') {
            errorMessage = 'No camera found. Please connect a camera.';
            showNotification('info', errorMessage);
        } else {
            errorMessage = 'Camera access failed: ' + error.message;
            showNotification('error', errorMessage);
        }
        updateScannerStatus('Camera access failed');
        updateCameraSelect([]);
    }

    function updateCameraSelect(cameras) {
        const select = document.getElementById('cameraSelect');
        const startBtn = document.getElementById('startBtn');
        select.innerHTML = '';
        if (!cameras || cameras.length === 0) {
            select.innerHTML = '<option value="">No cameras available</option>';
            select.disabled = true;
            if (startBtn) startBtn.disabled = true;
            return;
        }
        cameras.forEach((camera, index) => {
            const option = document.createElement('option');
            option.value = camera.deviceId;
            option.textContent = camera.label || `Camera ${index + 1}`;
            select.appendChild(option);
        });
        select.value = cameras[0].deviceId;
        select.disabled = false;
        currentCameraId = cameras[0].deviceId;
        if (startBtn) startBtn.disabled = false;
        updateScannerStatus('Select camera and start scanning');
    }

    function onCameraSelect() {
        const select = document.getElementById('cameraSelect');
        const cameraId = select.value;
        if (cameraId) {
            currentCameraId = cameraId;
            console.log(`📷 Selected camera: ${select.options[select.selectedIndex].text}`);
            if (isScanning) {
                restartScanner(cameraId);
            }
        }
    }

    async function startScannerWithCamera(cameraId) {
        console.log(`🚀 Starting scanner with camera: ${cameraId}`);
        const startBtn = document.getElementById('startBtn');
        const stopBtn = document.getElementById('stopBtn');
        if (startBtn) startBtn.disabled = true;
        if (stopBtn) stopBtn.disabled = false;
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
                formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE]
            };
            const qrCodeSuccessCallback = (decodedText) => {
                console.log('✅ QR Code detected:', decodedText.substring(0, 50));
                // Call processScan directly - now it's globally available
                processScan(decodedText);
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
            handleScannerStartError(error);
        }
    }

    function handleScannerStartError(error) {
        let userMessage = 'Failed to start scanner. ';
        if (error.name === 'NotAllowedError') {
            userMessage = 'Camera permission denied. Please allow camera access.';
        } else if (error.name === 'NotFoundError') {
            userMessage = 'Selected camera not found. Try another camera.';
        } else {
            userMessage += error.message;
        }
        updateScannerStatus('Startup failed');
        const startBtn = document.getElementById('startBtn');
        const stopBtn = document.getElementById('stopBtn');
        if (startBtn) startBtn.disabled = false;
        if (stopBtn) stopBtn.disabled = true;
        showNotification('error', userMessage);
    }

    async function stopScanner() {
        console.log('🛑 Stopping scanner...');
        if (html5QrCode && isScanning) {
            try {
                await html5QrCode.stop();
                isScanning = false;
                const startBtn = document.getElementById('startBtn');
                const stopBtn = document.getElementById('stopBtn');
                if (startBtn) startBtn.disabled = false;
                if (stopBtn) stopBtn.disabled = true;
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

    function updateScannerStatus(text) {
        const statusElement = document.getElementById('scannerStatus');
        if (statusElement) statusElement.textContent = text;
    }

    function provideScanVisualFeedback() {
        const qrReader = document.getElementById('qr-reader');
        if (qrReader) {
            qrReader.classList.remove('scanner-active');
            qrReader.classList.add('scan-success');
            setTimeout(() => {
                qrReader.classList.remove('scan-success');
                if (isScanning) qrReader.classList.add('scanner-active');
            }, 500);
        }
    }

    function incrementScanCount() {
        scanCount++;
        const scanCountElement = document.getElementById('scanCount');
        if (scanCountElement) scanCountElement.textContent = scanCount + ' scans';
    }

    function pauseScannerTemporarily() {
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
        if (!resultsContainer) return;
        if (!items || items.length === 0) {
            resultsContainer.innerHTML = '<div class="search-result-item text-muted p-3">No items found</div>';
            resultsContainer.style.display = 'block';
            return;
        }
        let html = items.map(item => createSearchResultHTML(item)).join('');
        resultsContainer.innerHTML = html;
        resultsContainer.style.display = 'block';
    }

    function createSearchResultHTML(item) {
        const name = item.name || item.item_name || 'Unknown Item';
        const id = item.id || item.item_id;
        const serial = item.serial_number || item.serial || 'N/A';
        const category = item.category || item.category_name || 'N/A';
        return `
            <div class="search-result-item" onclick="selectSearchItem(${JSON.stringify(item).replace(/"/g, '&quot;')})">
                <div class="item-name">${escapeHtml(name)}</div>
                <div class="item-details">
                    ID: ${escapeHtml(id)} | 
                    Serial: ${escapeHtml(serial)} | 
                    Category: ${escapeHtml(category)}
                </div>
            </div>
        `;
    }

    function hideSearchResults() {
        const resultsContainer = document.getElementById('searchResults');
        if (resultsContainer) resultsContainer.style.display = 'none';
    }

    function selectSearchItem(item) {
        document.getElementById('itemId').value = item.id || item.item_id || '';
        document.getElementById('itemName').value = item.name || item.item_name || '';
        document.getElementById('category').value = item.category || item.category_name || '';
        document.getElementById('status').value = item.status || 'available';
        document.getElementById('location').value = item.stock_location || item.location || '';
        document.getElementById('quantity').value = 1;
        hideSearchResults();
        document.getElementById('manualSearch').value = '';
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
        const newItem = createManualItem(itemId, itemName, quantity, status, location, category);
        addToBatch(newItem);
        addToRecentItems(newItem);
        clearManualForm();
        showNotification('success', 'Item added to batch');
    }

    function createManualItem(itemId, itemName, quantity, status, location, category) {
        return {
            id: itemId || Date.now(),
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
    }

    function clearManualForm() {
        const fields = ['itemId', 'itemName', 'quantity', 'status', 'location', 'category', 'manualSearch'];
        fields.forEach(fieldId => {
            const element = document.getElementById(fieldId);
            if (element) element.value = '';
        });
        document.getElementById('quantity').value = 1;
        hideSearchResults();
    }

    function addToRecentItems(item) {
        recentItems.unshift(item);
        if (recentItems.length > 10) recentItems = recentItems.slice(0, 10);
        updateRecentItemsDisplay();
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
        if (!container) return;
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
                        title="${escapeHtml(name)}">
                    ${escapeHtml(truncatedName)}
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

    // ==================== TECHNICIAN AUTHENTICATION FUNCTIONS ====================
    function initializeTechnicianAuthentication() {
        console.log('🔐 Initializing technician authentication...');
        showAuthenticationSection();
        isTechnicianAuthenticated = false;
        authenticatedTechnician = null;
        setStockControllerName();
        loadRealTechniciansFromDatabase();
        setupAuthEventListeners();
        resetFormState();
    }

    function showAuthenticationSection() {
        const authSection = document.getElementById('authenticationSection');
        const batchFormSection = document.getElementById('batchFormSection');
        if (authSection) authSection.classList.remove('d-none');
        if (batchFormSection) batchFormSection.classList.add('d-none');
    }

    function showBatchFormSection() {
        const authSection = document.getElementById('authenticationSection');
        const batchFormSection = document.getElementById('batchFormSection');
        if (authSection) authSection.classList.add('d-none');
        if (batchFormSection) batchFormSection.classList.remove('d-none');
        const printPreviewBtn = document.getElementById('printPreviewBtn');
        const confirmCheckbox = document.getElementById('confirmBatchSubmit');
        const submitBtn = document.getElementById('submitBatchBtn');
        if (printPreviewBtn) printPreviewBtn.disabled = false;
        if (confirmCheckbox) {
            confirmCheckbox.disabled = false;
            if (!confirmCheckbox.checked) {
                confirmCheckbox.checked = true;
                confirmCheckbox.dispatchEvent(new Event('change'));
            }
        }
        if (submitBtn) submitBtn.disabled = false;
    }

    async function loadRealTechniciansFromDatabase() {
        console.log('👨‍🔧 Loading technicians...');
        const technicianSelect = document.getElementById('technicianSelect');
        if (!technicianSelect) {
            console.error('❌ technicianSelect element not found');
            return;
        }
        technicianSelect.innerHTML = '<option value="">Loading technicians...</option>';
        technicianSelect.disabled = true;
        try {
            const response = await fetch('api/get_technicians.php');
            const data = await response.json();
            console.log('API response:', data);
            if (data.success && data.technicians && data.technicians.length > 0) {
                console.log(`✅ Found ${data.technicians.length} technicians from API`);
                populateTechnicianDropdown(data.technicians);
                showNotification('success', `Loaded ${data.technicians.length} technicians`);
            } else {
                console.log('⚠️ No technicians from API:', data.message);
                showEmptyTechnicianState(data.message || 'No technicians found');
                showNotification('info', data.message || 'No technicians available');
            }
        } catch (error) {
            console.error('❌ Error loading technicians:', error);
            showEmptyTechnicianState('Error loading technicians');
            showNotification('error', 'Failed to load technicians');
        }
    }

    function showEmptyTechnicianState() {
        const select = document.getElementById('technicianSelect');
        if (!select) return;
        select.innerHTML = `
            <option value="">-- Select Technician --</option>
            <option value="" disabled>No technicians available in database</option>
            <option value="demo_1" data-username="demo_tech" data-fullname="Demo Technician" data-department="Demo" data-email="demo@example.com">Demo Technician - Demo Department</option>
        `;
        select.disabled = false;
    }

    function populateTechnicianDropdown(technicians) {
        const select = document.getElementById('technicianSelect');
        if (!select) return;
        select.innerHTML = '';
        const defaultOption = document.createElement('option');
        defaultOption.value = "";
        defaultOption.textContent = "-- Select Technician --";
        defaultOption.disabled = true;
        defaultOption.selected = true;
        select.appendChild(defaultOption);
        if (!technicians || technicians.length === 0) {
            const noDataOption = document.createElement('option');
            noDataOption.value = "";
            noDataOption.textContent = "No technicians available";
            noDataOption.disabled = true;
            select.appendChild(noDataOption);
            select.disabled = false;
            return;
        }
        technicians.sort((a, b) => {
            const nameA = (a.full_name || a.username || '').toUpperCase();
            const nameB = (b.full_name || b.username || '').toUpperCase();
            return nameA.localeCompare(nameB);
        });
        technicians.forEach(tech => {
            const option = document.createElement('option');
            option.value = tech.id || tech.user_id || '';
            let displayText = tech.full_name || tech.username || 'Unknown Technician';
            if (tech.department && tech.department !== 'Not specified') {
                displayText += ` - ${tech.department}`;
            }
            if (tech.role && tech.role !== 'technician') {
                displayText += ` (${tech.role})`;
            }
            option.textContent = displayText;
            option.dataset.username = tech.username || '';
            option.dataset.fullname = tech.full_name || tech.username || '';
            option.dataset.department = tech.department || '';
            option.dataset.email = tech.email || '';
            option.dataset.role = tech.role || '';
            select.appendChild(option);
        });
        select.disabled = false;
        console.log(`✅ Populated dropdown with ${technicians.length} technicians`);
    }

    function setupAuthEventListeners() {
        const toggleBtn = document.getElementById('togglePassword');
        const authBtn = document.getElementById('authenticateBtn');
        const technicianSelect = document.getElementById('technicianSelect');
        const passwordInput = document.getElementById('technicianPassword');
        const confirmCheckbox = document.getElementById('confirmBatchSubmit');
        const technicianAuthForm = document.getElementById('technicianAuthForm');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', togglePasswordVisibility);
        }
        if (authBtn) {
            authBtn.addEventListener('click', authenticateTechnicianAsStockController);
            authBtn.innerHTML = '<i class="fas fa-user-check me-1"></i> Verify & Continue';
        }
        if (technicianSelect) {
            technicianSelect.addEventListener('change', handleTechnicianSelectChange);
        }
        if (passwordInput) {
            passwordInput.addEventListener('input', updateAuthButtonState);
            passwordInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    authenticateTechnicianAsStockController();
                }
            });
        }
        if (confirmCheckbox) {
            confirmCheckbox.addEventListener('change', function() {
                const submitBtn = document.getElementById('submitBatchBtn');
                if (submitBtn) {
                    submitBtn.disabled = !(this.checked && isTechnicianAuthenticated);
                }
            });
        }
        if (technicianAuthForm) {
            technicianAuthForm.addEventListener('submit', function(e) {
                e.preventDefault();
                authenticateTechnicianAsStockController();
            });
        }
    }

    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('technicianPassword');
        const icon = document.querySelector('#togglePassword i');
        if (!passwordInput || !icon) return;
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    function updateAuthButtonState() {
        const technicianSelect = document.getElementById('technicianSelect');
        const passwordInput = document.getElementById('technicianPassword');
        const authBtn = document.getElementById('authenticateBtn');
        if (!technicianSelect || !passwordInput || !authBtn) return;
        const technician = technicianSelect.value;
        const password = passwordInput.value;
        authBtn.disabled = !(technician && password.length > 0);
    }

    function handleTechnicianSelectChange() {
        const select = document.getElementById('technicianSelect');
        const selectedOption = select.options[select.selectedIndex];
        const authInfoDiv = document.getElementById('authTechnicianInfo');
        document.getElementById('technicianPassword').value = '';
        document.getElementById('authStatus').innerHTML = '';
        if (selectedOption && selectedOption.value) {
            const fullName = selectedOption.dataset.fullname;
            const department = selectedOption.dataset.department;
            const email = selectedOption.dataset.email;
            if (authInfoDiv) {
                authInfoDiv.innerHTML = `
                <div class="selected-technician-info">
                    <div class="fw-bold">${escapeHtml(fullName)}</div>
                    ${department ? `<div><small class="text-muted">${escapeHtml(department)}</small></div>` : ''}
                    ${email ? `<div><small><i class="fas fa-envelope me-1"></i>${escapeHtml(email)}</small></div>` : ''}
                </div>
            `;
                authInfoDiv.classList.remove('d-none');
            }
        } else {
            if (authInfoDiv) {
                authInfoDiv.innerHTML = '';
                authInfoDiv.classList.add('d-none');
            }
        }
        updateAuthButtonState();
    }

    async function authenticateTechnicianAsStockController() {
        const technicianSelect = document.getElementById('technicianSelect');
        const passwordInput = document.getElementById('technicianPassword');
        const authStatus = document.getElementById('authStatus');
        if (!technicianSelect || !passwordInput || !authStatus) {
            showToast('error', 'Authentication elements not found');
            return;
        }
        const technicianId = technicianSelect.value;
        const password = passwordInput.value;
        if (!technicianId) {
            showToast('error', 'Please select a technician');
            return;
        }
        if (!password) {
            showToast('error', 'Please enter the technician\'s password');
            return;
        }
        const authBtn = document.getElementById('authenticateBtn');
        if (authBtn) {
            authBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Verifying...';
            authBtn.disabled = true;
        }
        try {
            console.log('🔐 Attempting to verify technician...');
            const verifyEndpoints = [
                'api/technicians/verify_password.php',
                'api/verify_password.php',
                '../api/verify_password.php'
            ];
            let verificationSuccessful = false;
            let technicianData = null;
            for (const endpoint of verifyEndpoints) {
                try {
                    console.log(`Trying verification endpoint: ${endpoint}`);
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            technician_id: technicianId,
                            password: password
                        })
                    });
                    if (response.ok) {
                        const result = await response.json();
                        console.log('API verification response:', result);
                        if (result.success && result.technician) {
                            technicianData = result.technician;
                            verificationSuccessful = true;
                            break;
                        }
                    }
                } catch (apiError) {
                    console.log(`Verification endpoint ${endpoint} failed:`, apiError.message);
                }
            }
            if (!verificationSuccessful) {
                if (technicianId === 'demo_1' && password === 'demo123') {
                    technicianData = {
                        id: 'demo_1',
                        username: 'demo_tech',
                        full_name: 'Demo Technician',
                        department: 'Demo Department',
                        email: 'demo@example.com',
                        role: 'technician',
                        verified_at: new Date().toISOString(),
                        demo_mode: true
                    };
                    verificationSuccessful = true;
                }
            }
            if (verificationSuccessful && technicianData) {
                handleSuccessfulAuthentication(technicianData);
            } else {
                handleFailedAuthentication('Invalid password. Please try again.');
            }
        } catch (error) {
            console.error('❌ Authentication error:', error);
            handleFailedAuthentication('Authentication failed. Please try again.');
        } finally {
            if (authBtn) {
                authBtn.innerHTML = '<i class="fas fa-user-check me-1"></i> Verify & Continue';
                authBtn.disabled = false;
            }
        }
    }

    function handleSuccessfulAuthentication(technicianData) {
        console.log('✅ Authentication successful:', technicianData);
        authenticatedTechnician = technicianData;
        isTechnicianAuthenticated = true;
        sessionStorage.setItem('authenticatedTechnician', JSON.stringify(authenticatedTechnician));
        console.log('💾 Technician saved to sessionStorage');
        updateAuthUI(technicianData);
        const authStatus = document.getElementById('authStatus');
        if (authStatus) {
            authStatus.innerHTML = `
            <div class="alert alert-success alert-sm mt-2 mb-0">
                <i class="fas fa-check-circle me-1"></i>
                Technician verified successfully.
            </div>
        `;
        }
        setTimeout(() => {
            showBatchFormSection();
            updateBatchModalData();
            showToast('success', `Verified: ${authenticatedTechnician.full_name} is authenticated`);
        }, 800);
    }

    function updateAuthUI(technicianData) {
        const elementsToUpdate = {
            'authenticatedTechnicianName': technicianData.full_name,
            'confirmationTechnicianName': technicianData.full_name,
            'requestedBy': technicianData.full_name
        };
        Object.entries(elementsToUpdate).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                if (element.tagName === 'INPUT') {
                    element.value = value;
                } else {
                    element.textContent = value;
                }
            }
        });
        const authSuccessSection = document.getElementById('authSuccessSection');
        if (authSuccessSection) {
            authSuccessSection.classList.remove('d-none');
        }
    }

    function handleFailedAuthentication(message) {
        console.log('❌ Authentication failed:', message);
        isTechnicianAuthenticated = false;
        authenticatedTechnician = null;
        sessionStorage.removeItem('authenticatedTechnician');
        const authStatus = document.getElementById('authStatus');
        if (authStatus) {
            authStatus.innerHTML = `
            <div class="alert alert-danger alert-sm mt-2 mb-0">
                <i class="fas fa-times-circle me-1"></i>
                ${escapeHtml(message)}
            </div>
        `;
        }
        const passwordInput = document.getElementById('technicianPassword');
        if (passwordInput) {
            passwordInput.value = '';
            passwordInput.focus();
        }
        const authSuccessSection = document.getElementById('authSuccessSection');
        if (authSuccessSection) {
            authSuccessSection.classList.add('d-none');
        }
        showToast('error', 'Authentication failed');
    }

    function resetFormState() {
        const defaultValues = {
            'stockLocation': 'KCC',
            'eventName': 'Hibiscus',
            'jobSheet': 'JS-00254',
            'projectManager': 'Hirwa Aubin',
            'vehicleNumber': 'RAH 847',
            'driverName': 'Valentin',
            'batchLocation': 'KCC',
            'batchNotes': '',
            'approvalNotes': ''
        };
        Object.keys(defaultValues).forEach(fieldId => {
            const element = document.getElementById(fieldId);
            if (element) {
                if (element.tagName === 'SELECT') {
                    element.value = defaultValues[fieldId];
                } else {
                    element.value = defaultValues[fieldId];
                }
            }
        });
        const confirmCheckbox = document.getElementById('confirmBatchSubmit');
        if (confirmCheckbox) {
            confirmCheckbox.checked = false;
            confirmCheckbox.disabled = true;
        }
        const submitBtn = document.getElementById('submitBatchBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-lock me-1"></i> Awaiting Authentication';
        }
    }

    function resetTechnicianAuthentication() {
        console.log('🔓 Resetting technician authentication UI');
        showAuthenticationSection();
        const passwordInput = document.getElementById('technicianPassword');
        const authStatus = document.getElementById('authStatus');
        if (passwordInput) passwordInput.value = '';
        if (authStatus) authStatus.innerHTML = '';
        const confirmCheckbox = document.getElementById('confirmBatchSubmit');
        if (confirmCheckbox) {
            confirmCheckbox.checked = false;
            confirmCheckbox.disabled = true;
        }
        const submitBtn = document.getElementById('submitBatchBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Submit Batch';
        }
        const authSuccessSection = document.getElementById('authSuccessSection');
        if (authSuccessSection) authSuccessSection.classList.add('d-none');
    }

    function setStockControllerName() {
        const stockControllerName = "<?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Stock Controller'); ?>";
        const stockControllerUsername = "<?php echo htmlspecialchars($_SESSION['username'] ?? 'Stock Controller'); ?>";
        const submittedByField = document.getElementById('submittedBy');
        if (submittedByField) {
            submittedByField.value = stockControllerName;
            submittedByField.setAttribute('readonly', 'readonly');
            submittedByField.classList.add('bg-light');
        }
        const submittedByDisplay = document.getElementById('submittedByDisplay');
        if (submittedByDisplay) {
            submittedByDisplay.textContent = stockControllerName;
        }
        return stockControllerName;
    }

    // ==================== BATCH MODAL FUNCTIONS ====================
    function openBatchModal() {
        console.log('📋 Opening batch modal...');
        const modalElement = document.getElementById('batchSubmitModal');
        if (modalElement && modalElement.classList.contains('show')) {
            console.log('Modal is already open');
            return;
        }
        if (batchItems.length === 0) {
            showNotification('warning', 'No items in batch to submit');
            return;
        }
        updateBatchModalData();
        if (modalElement) {
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.show();
            } else {
                const newModal = new bootstrap.Modal(modalElement, {
                    backdrop: 'static',
                    keyboard: false
                });
                newModal.show();
            }
        } else {
            console.error('❌ Modal element not found');
            showNotification('error', 'Could not open batch modal');
        }
    }

    function updateBatchModalData() {
        console.log('Updating batch modal data...');
        if (window.updateModalDataTimeout) {
            clearTimeout(window.updateModalDataTimeout);
        }
        window.updateModalDataTimeout = setTimeout(() => {
            updateModalCounts();
            updateItemsTable();
        }, 100);
    }

    function updateModalCounts() {
        updateElementText('batchItemCount', batchItems.length);
        updateElementText('summaryTotal', batchItems.length);
        const availableCount = batchItems.filter(i => i.status === 'available').length;
        const inUseCount = batchItems.filter(i => i.status === 'in_use').length;
        const maintenanceCount = batchItems.filter(i => i.status === 'maintenance').length;
        updateElementText('summaryAvailable', availableCount);
        updateElementText('summaryInUse', inUseCount);
        updateElementText('summaryMaintenance', maintenanceCount);
        updateElementText('totalItemsCount', batchItems.length);
    }

    function updateItemsTable() {
        const tableBody = document.getElementById('itemsPreviewTable');
        if (!tableBody) {
            console.warn('itemsPreviewTable element not found');
            return;
        }
        if (batchItems.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        <i class="fas fa-box-open fa-2x mb-2"></i><br>
                        No items in batch
                    </td>
                </tr>`;
            return;
        }
        let html = '';
        batchItems.forEach((item, index) => {
            const status = item.status || 'available';
            html += `
                <tr>
                    <td>${escapeHtml(item.name || item.item_name || 'Unknown Item')}</td>
                    <td><code>${escapeHtml(item.serial_number || item.serial || 'N/A')}</code></td>
                    <td>${item.quantity || 1}</td>
                    <td>${escapeHtml(item.stock_location || item.location || 'KCC')}</td>
                    <td><span class="badge bg-${getStatusColor(status)}">${escapeHtml(status)}</span></td>
                </tr>`;
        });
        tableBody.innerHTML = html;
    }

    function getStatusColor(status) {
        if (!status) return 'secondary';
        switch (status.toLowerCase()) {
            case 'available':
                return 'success';
            case 'in_use':
            case 'in-use':
                return 'warning';
            case 'maintenance':
                return 'danger';
            default:
                return 'secondary';
        }
    }

    // ==================== PRINT FUNCTIONS ====================
    function printBatchSummary() {
        if (!isTechnicianAuthenticated) {
            showToast('error', 'Please authenticate a technician first');
            return;
        }
        console.log('🖨️ Opening print preview...');
        const stockController = "<?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Stock Controller'); ?>";
        const printContent = createPrintContent(stockController);
        const printWindow = window.open('', '_blank');
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.onload = function() {
            printWindow.print();
        };
    }

    function createPrintContent(stockController) {
        return `<!DOCTYPE html>
<html>
<head>
    <title>Batch Summary - ${new Date().toLocaleDateString()}</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h1 { color: #333; border-bottom: 2px solid #4361ee; padding-bottom: 10px; }
        .header-info { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .auth-section { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .info-box { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #4361ee; color: white; padding: 10px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #dee2e6; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .badge-success { background: #28a745; color: white; }
        .badge-warning { background: #ffc107; color: black; }
        .badge-danger { background: #dc3545; color: white; }
        .signature-section { margin-top: 40px; padding-top: 20px; border-top: 2px solid #ccc; }
        .signature-box { display: inline-block; width: 45%; vertical-align: top; }
        @media print {
            button { display: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: right; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #4361ee; color: white; border: none; border-radius: 5px; cursor: pointer;">
            🖨️ Print
        </button>
    </div>
    
    <h1>📦 Batch Items Summary - Equipment Release</h1>
    
    <div class="auth-section">
        <h3 style="color: #856404; margin-top: 0;">
            <i class="fas fa-user-shield"></i> Authentication Details
        </h3>
        <div style="display: flex; justify-content: space-between;">
            <div>
                <strong>Technician Requesting:</strong> ${authenticatedTechnician.full_name}<br>
                <strong>Authentication Time:</strong> ${new Date().toLocaleString()}<br>
                <strong>Authentication Status:</strong> <span style="color: green;">✓ Verified</span>
            </div>
            <div style="text-align: right;">
                <strong>Submitted By (Stock Controller):</strong> ${stockController}<br>
                <strong>Submission Time:</strong> ${new Date().toLocaleString()}<br>
                <strong>Submission Status:</strong> Pending Approval
            </div>
        </div>
    </div>
    
    <div class="header-info">
        <div>
            <strong>Date:</strong> ${new Date().toLocaleDateString()}<br>
            <strong>Time:</strong> ${new Date().toLocaleTimeString()}<br>
            <strong>Total Items:</strong> ${batchItems.length}
        </div>
        <div style="text-align: right;">
            <strong>Job Sheet:</strong> ${document.getElementById('jobSheet')?.value || 'N/A'}<br>
            <strong>Event:</strong> ${document.getElementById('eventName')?.value || 'N/A'}<br>
            <strong>Reference:</strong> BATCH-${Date.now().toString().slice(-6)}
        </div>
    </div>
    
    <div class="info-box">
        <strong>📋 Job Details:</strong><br>
        Stock Location: ${document.getElementById('stockLocation')?.value || 'KCC'}<br>
        Project Manager: ${document.getElementById('projectManager')?.value || 'N/A'}<br>
        Vehicle: ${document.getElementById('vehicleNumber')?.value || 'N/A'}<br>
        Driver: ${document.getElementById('driverName')?.value || 'N/A'}<br>
        Destination: ${document.getElementById('batchLocation')?.value || 'KCC'}
    </div>
    
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Item Name</th>
                <th>Serial Number</th>
                <th>Category</th>
                <th>Status</th>
                <th>Destination</th>
                <th>Qty</th>
            </tr>
        </thead>
        <tbody>
            ${batchItems.map((item, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td>${escapeHtml(item.name || item.item_name || 'Unknown Item')}</td>
                    <td><code>${escapeHtml(item.serial_number || item.serial || 'N/A')}</code></td>
                    <td>${escapeHtml(item.category || item.category_name || 'N/A')}</td>
                    <td><span class="badge badge-${getStatusColor(item.status)}">${escapeHtml(item.status || 'available')}</span></td>
                    <td>${document.getElementById('batchLocation')?.value || 'KCC'}</td>
                    <td>${item.quantity || 1}</td>
                </tr>
            `).join('')}
        </tbody>
    </table>
    
    <div class="signature-section">
        <div class="signature-box">
            <strong>Requested By (Technician):</strong><br><br>
            <div style="border-bottom: 1px solid #000; width: 80%; margin-bottom: 5px;"></div>
            <div style="color: #666; font-size: 0.9em;">
                ${authenticatedTechnician.full_name}<br>
                Technician Signature
            </div>
        </div>
        
        <div class="signature-box" style="float: right;">
            <strong>Approved By (Stock Controller):</strong><br><br>
            <div style="border-bottom: 1px solid #000; width: 80%; margin-bottom: 5px;"></div>
            <div style="color: #666; font-size: 0.9em;">
                ${stockController}<br>
                Stock Controller Signature<br>
                ${document.getElementById('approvalNotes')?.value ? 'Notes: ' + document.getElementById('approvalNotes').value : ''}
            </div>
        </div>
        
        <div style="clear: both; margin-top: 20px; font-size: 0.9em; color: #666;">
            <strong>Important Notes:</strong><br>
            ${document.getElementById('batchNotes')?.value || 'No additional notes provided.'}
        </div>
    </div>
    
    <script>
        function getStatusColor(status) {
            switch(status) {
                case 'available': return 'success';
                case 'in_use': return 'warning';
                case 'maintenance': return 'danger';
                default: return 'secondary';
            }
        }
    <\/script>
</body>
</html>`;
    }

    // ==================== BATCH SUBMISSION FUNCTIONS ====================
    function submitBatchToServer() {
        if (!isTechnicianAuthenticated) {
            showToast('error', 'Please verify technician identity first');
            return;
        }

        const confirmCheckbox = document.getElementById('confirmBatchSubmit');
        if (!confirmCheckbox || !confirmCheckbox.checked) {
            showToast('error', 'Please confirm that you have verified the technician');
            return;
        }

        const submitBtn = document.getElementById('submitBatchBtn');
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Submitting...';
            submitBtn.disabled = true;
        }

        const batchData = collectBatchData();
        console.log('Submitting batch with verified technician:', batchData);

        // IMPORTANT: Add credentials: 'include' to send cookies
        fetch('api/batch/submit.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include', // THIS IS CRITICAL - sends session cookie
                body: JSON.stringify(batchData)
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    handleBatchSubmissionSuccess();
                } else {
                    handleBatchSubmissionError(data.message || 'Submission failed');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                handleBatchSubmissionError(error.message);
            });
    }

    function collectBatchData() {
        const stockControllerName = "<?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Stock Controller'); ?>";
        const stockControllerUsername = "<?php echo htmlspecialchars($_SESSION['username'] ?? 'Stock Controller'); ?>";
        const stockControllerId = "<?php echo $_SESSION['user_id'] ?? 0; ?>"; // ADD THIS
        console.log('🔍 collectBatchData(): Checking technician...');
        if (!authenticatedTechnician) {
            const savedTech = sessionStorage.getItem('authenticatedTechnician');
            if (savedTech) {
                try {
                    authenticatedTechnician = JSON.parse(savedTech);
                    isTechnicianAuthenticated = true;
                    console.log('🔄 Restored technician from sessionStorage:', authenticatedTechnician);
                } catch (e) {
                    console.error('Error parsing saved technician:', e);
                }
            }
        }
        if (!authenticatedTechnician || !authenticatedTechnician.full_name) {
            console.error('❌ CRITICAL: No valid technician data!');
            showToast('error', 'Technician authentication required. Please verify technician identity.');
            throw new Error('No authenticated technician data available');
        }
        console.log('✅ Using technician:', authenticatedTechnician.full_name);
        return {
            technician: authenticatedTechnician,
            stock_controller: {
                id: stockControllerId, // Make sure this is being sent
                username: stockControllerUsername,
                full_name: stockControllerName
            },
            items: batchItems.map(item => ({
                id: item.id,
                name: item.name || item.item_name,
                serial_number: item.serial_number,
                status: item.status,
                quantity: item.quantity || 1,
                category: item.category
            })),
            jobDetails: {
                stockLocation: document.getElementById('stockLocation')?.value || 'KCC',
                eventName: document.getElementById('eventName')?.value || 'N/A',
                jobSheet: document.getElementById('jobSheet')?.value || 'N/A',
                projectManager: document.getElementById('projectManager')?.value || 'N/A',
                vehicleNumber: document.getElementById('vehicleNumber')?.value || 'N/A',
                driverName: document.getElementById('driverName')?.value || 'N/A',
                batchAction: document.getElementById('batchAction')?.value || 'transfer',
                batchLocation: document.getElementById('batchLocation')?.value || 'KCC',
                batchNotes: document.getElementById('batchNotes')?.value || '',
                approvalNotes: document.getElementById('approvalNotes')?.value || '',
                requestedBy: authenticatedTechnician.full_name,
                submittedBy: stockControllerName
            },
            authentication: {
                verified_by: stockControllerUsername,
                verification_time: new Date().toISOString(),
                verification_method: authenticatedTechnician.demo_mode ? 'demo' : 'password_verification'
            },
            timestamp: new Date().toISOString(),
            batchId: 'BATCH-' + Date.now().toString().slice(-6)
        };
    }

    function handleBatchSubmissionSuccess() {
        console.log('✅ Batch submission successful!');
        showToast('success', 'Batch submitted successfully!');
        sessionStorage.removeItem('authenticatedTechnician');
        console.log('🧹 Cleared technician from sessionStorage');
        const modal = bootstrap.Modal.getInstance(document.getElementById('batchSubmitModal'));
        if (modal) {
            modal.hide();
        }
        batchItems = [];
        updateBatchUI();
        saveBatchToStorage();
        setTimeout(() => {
            resetTechnicianAuthentication();
        }, 500);
    }

    function handleBatchSubmissionError(errorMessage) {
        showToast('error', errorMessage);
        const submitBtn = document.getElementById('submitBatchBtn');
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Submit Batch';
            submitBtn.disabled = false;
        }
    }

    // ==================== UTILITY FUNCTIONS ====================
    function updateElementText(elementId, text) {
        const element = document.getElementById(elementId);
        if (element) element.textContent = text;
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function showNotification(type, message) {
        const existing = document.querySelectorAll('.alert-notification');
        existing.forEach(el => {
            try {
                el.remove();
            } catch (e) {}
        });
        try {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show alert-notification`;
            notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 500px;';
            notification.innerHTML = `
                ${type === 'success' ? '<i class="fas fa-check-circle me-2"></i>' : 
                  type === 'error' ? '<i class="fas fa-exclamation-triangle me-2"></i>' : 
                  '<i class="fas fa-info-circle me-2"></i>'}
                ${escapeHtml(message)}
                <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
            `;
            document.body.appendChild(notification);
            setTimeout(() => {
                if (notification.parentNode) {
                    try {
                        notification.remove();
                    } catch (e) {}
                }
            }, 5000);
        } catch (error) {
            console.error('Error showing notification:', error);
            alert(`${type.toUpperCase()}: ${message}`);
        }
    }

    function showToast(type, message) {
        const existingToasts = document.querySelectorAll('.toast');
        existingToasts.forEach(toast => {
            try {
                toast.remove();
            } catch (e) {}
        });
        try {
            const toastHtml = `
            <div class="toast align-items-center text-white bg-${type} border-0" role="alert" 
                 style="position: fixed; bottom: 20px; right: 20px; z-index: 1055;">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                        ${escapeHtml(message)}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
            const toast = document.createElement('div');
            toast.innerHTML = toastHtml;
            const toastElement = toast.firstElementChild;
            document.body.appendChild(toastElement);
            const bsToast = new bootstrap.Toast(toastElement, {
                delay: 3000
            });
            bsToast.show();
            toastElement.addEventListener('hidden.bs.toast', function() {
                this.remove();
            });
        } catch (error) {
            console.error('Error showing toast:', error);
        }
    }

    function generateBatchId() {
        return 'BATCH-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
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
        } catch (e) {}
    }

    function saveBatchToStorage() {
        try {
            localStorage.setItem('batch_items', JSON.stringify(batchItems));
            localStorage.setItem('batch_scan_count', scanCount.toString());
        } catch (e) {}
    }

    function loadBatchFromStorage() {
        try {
            const savedBatch = localStorage.getItem('batch_items');
            const savedScanCount = localStorage.getItem('batch_scan_count');
            if (savedBatch) batchItems = JSON.parse(savedBatch);
            if (savedScanCount) {
                scanCount = parseInt(savedScanCount);
                updateElementText('scanCount', scanCount + ' scans');
            }
            updateBatchUI();
        } catch (e) {}
    }

    function cleanupOnUnload() {
        if (html5QrCode && isScanning) {
            html5QrCode.stop().catch(e => console.log('Stop error on unload:', e));
        }
        saveBatchToStorage();
    }

    // ==================== EVENT LISTENER SETUP ====================
    function setupEventListeners() {
        const startBtn = document.getElementById('startBtn');
        const stopBtn = document.getElementById('stopBtn');
        const cameraSelect = document.getElementById('cameraSelect');
        const manualSearch = document.getElementById('manualSearch');
        if (startBtn) startBtn.addEventListener('click', handleStartScanner);
        if (stopBtn) stopBtn.addEventListener('click', stopScanner);
        if (cameraSelect) cameraSelect.addEventListener('change', onCameraSelect);
        if (manualSearch) {
            manualSearch.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performManualSearch();
                }
            });
        }
        setupModalEvents();
        document.addEventListener('click', handleGlobalClicks);
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#manualSearch') && !e.target.closest('#searchResults')) {
                hideSearchResults();
            }
        });
        window.addEventListener('beforeunload', cleanupOnUnload);
    }

    function setupModalEvents() {
        const batchSubmitModal = document.getElementById('batchSubmitModal');
        if (!batchSubmitModal) {
            console.warn('⚠️ batchSubmitModal element not found');
            return;
        }
        if (!batchSubmitModal.hasAttribute('data-listeners-set')) {
            batchSubmitModal.setAttribute('data-listeners-set', 'true');
            batchSubmitModal.addEventListener('shown.bs.modal', function(event) {
                console.log('Modal shown, initializing authentication...');
                const savedTech = sessionStorage.getItem('authenticatedTechnician');
                if (savedTech) {
                    try {
                        authenticatedTechnician = JSON.parse(savedTech);
                        isTechnicianAuthenticated = true;
                        console.log('🔍 Restored technician from sessionStorage:', authenticatedTechnician);
                        updateAuthUI(authenticatedTechnician);
                        showBatchFormSection();
                        updateBatchModalData();
                        showToast('info', 'Using previously authenticated technician');
                    } catch (e) {
                        console.error('Error restoring technician:', e);
                        initializeTechnicianAuthentication();
                    }
                } else {
                    initializeTechnicianAuthentication();
                }
            });
            batchSubmitModal.addEventListener('hidden.bs.modal', function(event) {
                console.log('Modal hidden, checking submission state...');
                if (!isSubmitting && !sessionStorage.getItem('authenticatedTechnician')) {
                    console.log('Not submitting, resetting authentication...');
                    resetTechnicianAuthentication();
                } else if (isSubmitting) {
                    console.log('Submission completed, cleaning up...');
                    isSubmitting = false;
                    sessionStorage.removeItem('authenticatedTechnician');
                }
            });
        }
        const submitBatchBtn = document.getElementById('submitBatchBtn');
        const printPreviewBtn = document.getElementById('printPreviewBtn');
        if (submitBatchBtn && !submitBatchBtn.hasAttribute('data-listener-set')) {
            submitBatchBtn.setAttribute('data-listener-set', 'true');
            submitBatchBtn.addEventListener('click', submitBatchToServer);
        }
        if (printPreviewBtn && !printPreviewBtn.hasAttribute('data-listener-set')) {
            printPreviewBtn.setAttribute('data-listener-set', 'true');
            printPreviewBtn.addEventListener('click', printBatchSummary);
        }
    }

    function handleGlobalClicks(e) {
        const target = e.target;
        const isSubmitBtn = target.id === 'submitBatchModalBtn' || target.closest('#submitBatchModalBtn');
        const isOpenBtn = target.id === 'openBatchModalBtn' || target.closest('#openBatchModalBtn');
        if (isSubmitBtn || isOpenBtn) {
            handleOpenModalClick(e);
            return;
        }
        if (target.id === 'clearBatchBtn' || target.closest('#clearBatchBtn')) {
            e.preventDefault();
            e.stopPropagation();
            clearBatch();
        }
    }

    function handleOpenModalClick(e) {
        if (modalOpenInProgress) {
            console.log('Modal opening already in progress...');
            return;
        }
        modalOpenInProgress = true;
        e.preventDefault();
        e.stopPropagation();
        console.log('Opening batch modal from button click...');
        openBatchModal();
        setTimeout(() => {
            modalOpenInProgress = false;
        }, 1000);
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
        const randomId = Math.floor(Math.random() * 1000) + 1;
        processScan(randomId.toString());
    }

    function testModal() {
        console.log('Testing modal...');
        const modal = new bootstrap.Modal(document.getElementById('batchSubmitModal'));
        modal.show();
    }

    // ==================== EXPOSE FUNCTIONS TO GLOBAL SCOPE ====================
    window.testModal = testModal;
    window.addRandomTestItem = addRandomTestItem;
    window.testScanJSON = testScanJSON;
    window.testScanNumber = testScanNumber;
    window.addManualItem = addManualItem;
    window.performManualSearch = performManualSearch;
    window.updateItemStatus = updateItemStatus;
    window.removeFromBatch = removeFromBatch;
    window.addRecentItemToBatch = addRecentItemToBatch;
    window.selectSearchItem = selectSearchItem;
    window.refreshCameras = refreshCameras;
    window.startScanner = handleStartScanner;
    window.stopScanner = stopScanner;

    // ==================== ENSURE GLOBAL ACCESS ====================
    // Force these functions to be globally available
    window.processScan = processScan;
    window.fetchAndAddToBatch = fetchAndAddToBatch;
    window.fetchItemData = fetchItemData;
    window.addToBatch = addToBatch; // THIS IS CRITICAL!
    window.removeFromBatch = removeFromBatch;
    window.updateItemStatus = updateItemStatus;
    window.clearBatch = clearBatch;
    window.extractItemId = extractItemId;
    window.updateScannerStatus = updateScannerStatus;
    window.provideScanVisualFeedback = provideScanVisualFeedback;
    window.incrementScanCount = incrementScanCount;
    window.pauseScannerTemporarily = pauseScannerTemporarily;
    window.generateBatchId = generateBatchId;
    window.playSuccessSound = playSuccessSound;
    window.saveBatchToStorage = saveBatchToStorage;
    window.updateBatchUI = updateBatchUI;
    window.findItemInBatch = findItemInBatch;
    window.updateExistingItem = updateExistingItem;
    window.addNewItemToBatch = addNewItemToBatch;

    // ==================== INITIALIZATION ====================
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🔍 Scan & Batch page loading...');
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
        setupEventListeners();
        loadCameras();
        loadBatchFromStorage();
        loadRecentItems();
        console.log('✅ Page initialization complete');
    });
</script>


<?php
require_once 'views/partials/footer.php';
?>