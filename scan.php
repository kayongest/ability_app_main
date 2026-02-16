<?php
// scan.php - WITH CHECK IN/OUT MODAL
$current_page = basename(__FILE__);
require_once 'bootstrap.php';

// Check authentication
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$pageTitle = "Scan QR Code - aBility";
$showBreadcrumb = true;
$breadcrumbItems = [
    'Dashboard' => 'dashboard.php',
    'Scan QR Code' => ''
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
    /* Debug Tools Styling */
    .debug-tools {
        border-left: 4px solid #6f42c1 !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .debug-tools h6 {
        font-size: 0.9rem;
        font-weight: 600;
    }

    .debug-tools .btn {
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
    }

    .debug-tools .btn i {
        font-size: 0.75rem;
    }


    /* Environment mode indicators */
    .env-mode-indicator {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: bold;
        margin-left: 8px;
        text-transform: uppercase;
    }

    .env-mode-normal {
        background-color: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }

    .env-mode-bright {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }

    .env-mode-lowlight {
        background-color: #cce5ff;
        color: #004085;
        border: 1px solid #b8daff;
    }

    /* Camera control sliders */
    .slider-container {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 10px;
    }

    .slider-container label {
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 5px;
        display: block;
    }

    .slider-value {
        display: inline-block;
        min-width: 40px;
        text-align: center;
        font-weight: bold;
        font-size: 14px;
    }

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

    .scanner-active {
        border-color: #28a745 !important;
        box-shadow: 0 0 20px rgba(40, 167, 69, 0.3) !important;
    }

    .scan-success {
        border-color: #28a745 !important;
        box-shadow: 0 0 20px rgba(40, 167, 69, 0.5) !important;
        animation: pulse 0.5s ease-in-out;
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
        }

        70% {
            box-shadow: 0 0 0 20px rgba(40, 167, 69, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
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

    /* Camera dropdown */
    .camera-select-container {
        max-width: 300px;
        margin: 0 auto;
    }

    /* Scanner fix */
    .html5-qrcode-element {
        width: 100% !important;
    }

    /* Camera view fix */
    video {
        width: 100% !important;
        height: auto !important;
        object-fit: cover !important;
    }

    /* Loading states */
    .scanning-status {
        font-size: 0.9rem;
    }

    .status-indicator {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 5px;
    }

    .status-active {
        background-color: #28a745;
        animation: blink 1s infinite;
    }

    @keyframes blink {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }

    .status-inactive {
        background-color: #6c757d;
    }

    .status-error {
        background-color: #dc3545;
    }

    /* Modal styles */
    .modal-lg-custom {
        max-width: 700px;
    }

    .form-label-required:after {
        content: " *";
        color: #dc3545;
    }

    .transport-icon {
        font-size: 1.2rem;
        margin-right: 8px;
        color: #6c757d;
    }
</style>

<!-- Check In/Out Modal -->
<div class="modal fade" id="scanActionModal" tabindex="-1" aria-labelledby="scanActionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-lg-custom">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scanActionModalLabel">
                    <i class="fas fa-truck-loading me-2"></i>
                    <span id="modalActionTitle">Check In/Out Item</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Please provide transportation details for tracking purposes.
                </div>

                <!-- Item Info -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-box me-2"></i>Item Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Item:</strong> <span id="modalItemName" class="text-primary">-</span></p>
                                <p><strong>Serial:</strong> <code id="modalSerialNumber">-</code></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Current Location:</strong> <span id="modalCurrentLocation">-</span></p>
                                <p><strong>Action:</strong> <span id="modalActionType" class="badge bg-success">Check In</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transportation Form -->
                <form id="transportForm">
                    <input type="hidden" id="modalItemId" value="">
                    <input type="hidden" id="modalScanType" value="">

                    <!-- Destination -->
                    <div class="mb-4">
                        <h6><i class="fas fa-map-marker-alt transport-icon"></i>Destination Details</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="fromLocation" class="form-label form-label-required">From Location</label>
                                <input type="text" class="form-control" id="fromLocation"
                                    value="Stock" readonly>
                                <div class="form-text">Source location (usually Stock)</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="toLocation" class="form-label form-label-required">To Location</label>
                                <input type="text" class="form-control" id="toLocation"
                                    placeholder="Enter destination (e.g., Site Office, Warehouse B, Client Location)" required>
                                <div class="form-text">Where is the item going?</div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="destinationAddress" class="form-label">Destination Address</label>
                                <textarea class="form-control" id="destinationAddress"
                                    rows="2" placeholder="Full address (optional)"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Personnel Details -->
                    <div class="mb-4">
                        <h6><i class="fas fa-user-tie transport-icon"></i>Personnel Details</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="transportUser" class="form-label form-label-required">Person Responsible</label>
                                <input type="text" class="form-control" id="transportUser"
                                    placeholder="Enter name of person transporting" required>
                                <div class="form-text">Who is responsible for transport?</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="userContact" class="form-label">Contact Number</label>
                                <input type="tel" class="form-control" id="userContact"
                                    placeholder="Phone number (optional)">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="userDepartment" class="form-label">Department</label>
                                <input type="text" class="form-control" id="userDepartment"
                                    placeholder="Department (optional)">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="userIdNumber" class="form-label">ID/Employee Number</label>
                                <input type="text" class="form-control" id="userIdNumber"
                                    placeholder="ID number (optional)">
                            </div>
                        </div>
                    </div>

                    <!-- Vehicle Details -->
                    <div class="mb-4">
                        <h6><i class="fas fa-truck transport-icon"></i>Vehicle Details</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="vehiclePlate" class="form-label form-label-required">Vehicle Number Plate</label>
                                <input type="text" class="form-control" id="vehiclePlate"
                                    placeholder="Enter vehicle registration number" required>
                                <div class="form-text">Format: ABC 123 GP or similar</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="vehicleType" class="form-label">Vehicle Type</label>
                                <select class="form-select" id="vehicleType">
                                    <option value="">Select vehicle type</option>
                                    <option value="truck">Truck</option>
                                    <option value="van">Van</option>
                                    <option value="car">Car</option>
                                    <option value="bike">Motorcycle</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="vehicleDescription" class="form-label">Vehicle Description</label>
                                <input type="text" class="form-control" id="vehicleDescription"
                                    placeholder="e.g., White Toyota Hilux, Red Ford Ranger (optional)">
                            </div>
                        </div>
                    </div>

                    <!-- Additional Notes -->
                    <div class="mb-4">
                        <h6><i class="fas fa-sticky-note transport-icon"></i>Additional Information</h6>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="transportNotes" class="form-label">Transport Notes</label>
                                <textarea class="form-control" id="transportNotes"
                                    rows="3" placeholder="Any additional notes about this transport (optional)"></textarea>
                                <div class="form-text">Purpose of transport, special instructions, etc.</div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="expectedReturn" class="form-label">Expected Return Date</label>
                                <input type="date" class="form-control" id="expectedReturn">
                                <div class="form-text">For check-out only (optional)</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="priority" class="form-label">Priority</label>
                                <select class="form-select" id="priority">
                                    <option value="normal" selected>Normal</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="high">High Priority</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Terms Agreement -->
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                        <label class="form-check-label" for="agreeTerms">
                            I confirm that the information provided is accurate and I take responsibility for this item during transport
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-success" id="submitTransport">
                    <i class="fas fa-check me-1"></i> Confirm & Save
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-qrcode me-2"></i>QR Code Scanner</h5>
                    <div class="scanning-status text-light">
                        <span class="status-indicator status-inactive" id="statusIndicator"></span>
                        <span id="statusText">Ready</span>
                    </div>
                </div>
                <div class="card-body text-center">
                    <!-- Camera Status -->
                    <div id="cameraStatus" class="alert alert-info">
                        <i class="fas fa-camera me-2"></i><span id="cameraStatusText">Ready to scan</span>
                    </div>

                    <!-- Add this after the test buttons section -->
                    <div class="mt-4 border-top pt-3">
                        <h6><i class="fas fa-qrcode me-2"></i>Visual Test QR Code</h6>
                        <div class="text-center">
                            <div class="border p-3 bg-white d-inline-block">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=%7B%22id%22%3A1%2C%22name%22%3A%22Test%20Laptop%22%2C%22serial%22%3A%22TEST-001%22%2C%22category%22%3A%22Electronics%22%7D"
                                    alt="Test QR Code"
                                    class="img-fluid"
                                    style="max-width: 200px;">
                            </div>
                            <p class="small text-muted mt-2">Scan this QR code to test</p>
                            <p class="small">Contains: <code>{"id":1,"name":"Test Laptop","serial":"TEST-001","category":"Electronics"}</code></p>
                        </div>
                    </div>

                    <!-- Camera Selection -->
                    <div id="cameraSelectContainer" class="camera-select-container mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-video"></i></span>
                            <select id="cameraSelect" class="form-select" disabled>
                                <option value="">Loading cameras...</option>
                            </select>
                            <button class="btn btn-outline-secondary" type="button" onclick="refreshCameras()" title="Refresh camera list">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <div class="form-text text-start">
                            <small id="cameraInfo">No cameras detected</small>
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

                    <!-- Scan Instructions -->
                    <div class="alert alert-light mt-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle text-primary me-3 fa-lg"></i>
                            <div class="text-start">
                                <small><strong>Tip:</strong> Select camera, then click Start Scanner. Point at QR code within the green frame.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Scanner Controls -->
                    <div class="mt-3">
                        <button id="startBtn" class="btn btn-success btn-lg">
                            <i class="fas fa-play me-2"></i> Start Scanner
                        </button>
                        <button id="stopBtn" class="btn btn-danger btn-lg" disabled>
                            <i class="fas fa-stop me-2"></i> Stop Scanner
                        </button>
                        <button id="flipBtn" class="btn btn-info btn-lg" onclick="flipCamera()" style="display: none;">
                            <i class="fas fa-sync-alt me-2"></i> Flip
                        </button>
                    </div>

                    <!-- Test QR Codes (for testing) -->
                    <div class="mt-4 border-top pt-3">
                        <h6><i class="fas fa-vial me-2"></i>Test QR Codes:</h6>
                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                            <button class="btn btn-outline-primary btn-sm" onclick="testScanJSON()">
                                <i class="fas fa-qrcode me-1"></i> Test JSON
                            </button>
                            <button class="btn btn-outline-success btn-sm" onclick="testScanURL()">
                                <i class="fas fa-link me-1"></i> Test URL
                            </button>
                            <button class="btn btn-outline-warning btn-sm" onclick="testScanNumber()">
                                <i class="fas fa-hashtag me-1"></i> Test Number
                            </button>
                        </div>
                        <small class="text-muted mt-2 d-block">Use these buttons to test without a physical QR code</small>
                    </div>

                    <!-- Add this near your other camera control buttons -->
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-info btn-sm"
                            onclick="applyLogitechProfile()"
                            title="Optimize for Logitech C903e">
                            <i class="fas fa-camera me-1"></i> Logitech Mode
                        </button>

                        <button type="button" class="btn btn-outline-secondary btn-sm"
                            onclick="restartWithLogitechSettings()"
                            title="Restart with Logitech optimization">
                            <i class="fas fa-redo me-1"></i> Optimize
                        </button>

                        <button type="button" class="btn btn-outline-warning btn-sm"
                            onclick="detectAndOptimizeForLogitech()"
                            title="Auto-detect Logitech camera">
                            <i class="fas fa-search me-1"></i> Detect
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-md-6">
            <!-- Results Panel -->
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Scan Results</h5>
                    <button class="btn btn-sm btn-light" onclick="clearResults()">
                        <i class="fas fa-times me-1"></i> Clear
                    </button>
                </div>
                <div class="card-body">
                    <div id="results">
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-qrcode fa-4x mb-3"></i>
                            <h5>No scans yet</h5>
                            <p>Scan a QR code to see results here</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Camera Info Panel -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-video me-2"></i>Camera Information</h5>
                </div>
                <div class="card-body">
                    <div id="cameraDetails">
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-camera fa-2x mb-2"></i>
                            <p>No camera information available</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Help -->
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>How to Scan</h5>
                </div>
                <div class="card-body">
                    <ol class="mb-3">
                        <li><strong>Select a camera</strong> from the dropdown</li>
                        <li>Click <strong>"Start Scanner"</strong> to activate</li>
                        <li>Point camera at QR code</li>
                        <li>Hold steady within the green frame</li>
                        <li>View results in the panel</li>
                    </ol>
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Note:</strong> Allow camera permissions when prompted. Use "Flip" button to switch between front/back cameras.
                    </div>
                </div>
                <div class="card-body">
                    <!-- Environment Selection -->
                    <div class="card-body">
                        <div class="camera-controls-panel mb-3">
                            <h6 class="mb-3"><i class="fas fa-sliders-h me-2"></i>Camera Settings</h6>

                            <!-- Preset Profiles -->
                            <div class="row g-2 mb-3">
                                <div class="col-4">
                                    <button class="btn btn-sm w-100 btn-outline-warning" onclick="applyCameraProfile('daylight')">
                                        <i class="fas fa-sun me-1"></i> Daylight
                                    </button>
                                </div>
                                <div class="col-4">
                                    <button class="btn btn-sm w-100 btn-outline-primary" onclick="applyCameraProfile('indoor')">
                                        <i class="fas fa-home me-1"></i> Indoor
                                    </button>
                                </div>
                                <div class="col-4">
                                    <button class="btn btn-sm w-100 btn-outline-dark" onclick="applyCameraProfile('night')">
                                        <i class="fas fa-moon me-1"></i> Night
                                    </button>
                                </div>
                            </div>

                            <!-- Advanced Controls -->
                            <div class="accordion mb-3" id="advancedControls">
                                <div class="accordion-item">
                                    <h6 class="accordion-header">
                                        <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAdvanced">
                                            <i class="fas fa-cog me-2"></i> Advanced Camera Settings
                                        </button>
                                    </h6>
                                    <div id="collapseAdvanced" class="accordion-collapse collapse" data-bs-parent="#advancedControls">
                                        <div class="accordion-body p-2">
                                            <div class="row g-2">
                                                <div class="col-md-6">
                                                    <label class="form-label small">
                                                        <i class="fas fa-sun me-1"></i> Saturation
                                                        <span id="saturationValue">100%</span>
                                                    </label>
                                                    <input type="range" class="form-range" id="saturationSlider"
                                                        min="0" max="200" value="100"
                                                        oninput="updateSaturation(this.value)">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small">
                                                        <i class="fas fa-magic me-1"></i> Sharpness
                                                        <span id="sharpnessValue">100%</span>
                                                    </label>
                                                    <input type="range" class="form-range" id="sharpnessSlider"
                                                        min="0" max="200" value="100"
                                                        oninput="updateSharpness(this.value)">
                                                </div>
                                            </div>
                                            <div class="row g-2 mt-2">
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="autoFocus" checked>
                                                        <label class="form-check-label small" for="autoFocus">
                                                            Auto Focus
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="flashlightToggle">
                                                        <label class="form-check-label small" for="flashlightToggle">
                                                            Flashlight
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Basic Controls -->
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small">
                                        <i class="fas fa-adjust me-1"></i> Brightness
                                        <span id="brightnessValue">100%</span>
                                    </label>
                                    <input type="range" class="form-range" id="brightnessSlider"
                                        min="0" max="200" value="100" step="1"
                                        oninput="updateBrightness(this.value)">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">
                                        <i class="fas fa-circle-half-stroke me-1"></i> Contrast
                                        <span id="contrastValue">100%</span>
                                    </label>
                                    <input type="range" class="form-range" id="contrastSlider"
                                        min="0" max="200" value="100" step="1"
                                        oninput="updateContrast(this.value)">
                                </div>
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label small">
                                        <i class="fas fa-satellite-dish me-1"></i> Exposure
                                        <span id="exposureValue">Auto</span>
                                    </label>
                                    <select class="form-select form-select-sm" id="exposureSelect" onchange="updateExposure(this.value)">
                                        <option value="auto">Auto</option>
                                        <option value="low">Low (Night)</option>
                                        <option value="medium">Medium (Indoor)</option>
                                        <option value="high">High (Daylight)</option>
                                        <option value="very-high">Very High (Sunlight)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mt-3">
                                        <input class="form-check-input" type="checkbox" id="autoEnhance" checked
                                            onchange="toggleAutoEnhance(this.checked)">
                                        <label class="form-check-label small" for="autoEnhance">
                                            <i class="fas fa-robot me-1"></i> Auto Enhance
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Scan Tips -->
                            <div class="alert alert-light mt-3 py-2" id="activeTips">
                                <small><i class="fas fa-lightbulb me-1"></i>
                                    <span id="scanningTip">Hold QR code steady within green frame</span>
                                </small>
                            </div>
                        </div>
                    </div>
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
    let lastScanTimestamp = 0;
    const SCAN_COOLDOWN = 1500; // 1.5 seconds between scans
    let scanAttempts = 0;
    let currentCameraIndex = 0;
    let currentItemData = null;
    let scanActionModal = null;

    // DEBUG mode
    const DEBUG = true;

    // Add these missing functions to your existing JavaScript code:

    // Restart scanner with new camera
    async function restartScanner(cameraId) {
        if (html5QrCode && isScanning) {
            await stopScanner();
            setTimeout(() => {
                startScannerWithCamera(cameraId);
            }, 500);
        }
    }

    // Refresh cameras function
    function refreshCameras() {
        console.log('🔄 Refreshing camera list...');
        loadCameras();
    }

    // Flip camera function
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

    // Toggle fullscreen function
    function toggleFullscreen() {
        const scannerContainer = document.getElementById('scanner-container');
        if (!document.fullscreenElement) {
            if (scannerContainer.requestFullscreen) {
                scannerContainer.requestFullscreen().catch(err => {
                    console.log(`Error attempting to enable fullscreen: ${err.message}`);
                });
            } else if (scannerContainer.webkitRequestFullscreen) {
                /* Safari */
                scannerContainer.webkitRequestFullscreen();
            } else if (scannerContainer.msRequestFullscreen) {
                /* IE11 */
                scannerContainer.msRequestFullscreen();
            }
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) {
                /* Safari */
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) {
                /* IE11 */
                document.msExitFullscreen();
            }
        }
    }

    // Manual scanner test function
    function manualScannerTest() {
        console.log('🔧 Manual scanner test...');

        // Test different QR formats
        const testFormats = [
            "1", // Simple number
            "ID:1", // ID prefix
            "ABIL:1", // ABIL prefix
            JSON.stringify({
                id: 1
            }), // JSON
            JSON.stringify({
                i: 1
            }), // Simple JSON
            JSON.stringify({
                id: 1,
                name: "Test Item"
            }) // Complex JSON
        ];

        let testIndex = 0;
        const testInterval = setInterval(() => {
            if (testIndex >= testFormats.length) {
                clearInterval(testInterval);
                console.log('✅ All tests completed');
                return;
            }

            const testData = testFormats[testIndex];
            console.log(`🧪 Testing format ${testIndex + 1}: ${testData}`);
            onScanSuccess(testData, {
                decodedText: testData
            });
            testIndex++;
        }, 1000);
    }

    // Add manual test button (call this in DOMContentLoaded)



    // 1. Apply Camera Profile Function
    function applyCameraProfile(profileName) {
        console.log('📷 Applying camera profile:', profileName);

        const profiles = {
            'default': {
                brightness: 100,
                contrast: 100,
                saturation: 100,
                sharpness: 0
            },
            'bright': {
                brightness: 120,
                contrast: 140,
                saturation: 100,
                sharpness: 20
            },
            'low-light': {
                brightness: 160,
                contrast: 120,
                saturation: 80,
                sharpness: 30
            },
            'document': {
                brightness: 130,
                contrast: 130,
                saturation: 50,
                sharpness: 40
            },
            'logitech': {
                brightness: 110,
                contrast: 120,
                saturation: 100,
                sharpness: 150
            }
        };

        const profile = profiles[profileName] || profiles['default'];

        // Update sliders
        const brightnessSlider = document.getElementById('brightnessSlider');
        const contrastSlider = document.getElementById('contrastSlider');
        const saturationSlider = document.getElementById('saturationSlider');
        const sharpnessSlider = document.getElementById('sharpnessSlider');

        if (brightnessSlider) {
            brightnessSlider.value = profile.brightness;
            updateBrightness(profile.brightness);
        }

        if (contrastSlider) {
            contrastSlider.value = profile.contrast;
            updateContrast(profile.contrast);
        }

        if (saturationSlider) {
            saturationSlider.value = profile.saturation;
            updateSaturation(profile.saturation);
        }

        if (sharpnessSlider) {
            sharpnessSlider.value = profile.sharpness;
            updateSharpness(profile.sharpness);
        }

        // Apply environment mode
        if (profileName === 'bright' || profileName === 'low-light') {
            setEnvironmentMode(profileName === 'bright' ? 'bright' : 'lowlight');
        }

        showNotification('success', `Applied ${profileName} profile`);
    }

    // 2. Update Saturation Function
    function updateSaturation(value = null) {
        if (value === null) {
            const slider = document.getElementById('saturationSlider');
            value = slider ? parseInt(slider.value) : 100;
        }

        const saturationValue = document.getElementById('saturationValue');
        if (saturationValue) {
            saturationValue.textContent = value + '%';
        }

        // Apply to video filter if scanner is active
        if (isScanning) {
            applyVideoFiltersWithSaturation();
        }

        if (DEBUG) console.log(`🎨 Saturation updated: ${value}%`);
    }

    // 3. Update Sharpness Function
    function updateSharpness(value = null) {
        if (value === null) {
            const slider = document.getElementById('sharpnessSlider');
            value = slider ? parseInt(slider.value) : 0;
        }

        const sharpnessValue = document.getElementById('sharpnessValue');
        if (sharpnessValue) {
            sharpnessValue.textContent = value;
        }

        // Note: Sharpness cannot be applied via CSS filters
        // In a real implementation, this would use WebGL or canvas processing
        console.log(`🔍 Sharpness updated: ${value} (WebGL processing required)`);

        // Show message if high sharpness is set
        if (value > 100) {
            console.log('⚠️ High sharpness may reduce QR detection accuracy');
        }
    }

    // 4. Extended Video Filters Function (includes saturation)
    function applyVideoFiltersWithSaturation() {
        const videoElement = document.querySelector('#qr-reader video');
        if (!videoElement) return;

        const brightness = (document.getElementById('brightnessSlider')?.value || 100) / 100;
        const contrast = (document.getElementById('contrastSlider')?.value || 100) / 100;
        const saturation = (document.getElementById('saturationSlider')?.value || 100) / 100;

        // Apply CSS filters
        videoElement.style.filter = `
        brightness(${brightness})
        contrast(${contrast})
        saturate(${saturation})
    `;

        console.log(`🎨 Applied filters: brightness(${brightness}), contrast(${contrast}), saturate(${saturation})`);
    }

    // 5. Update Scanner FPS (called by setEnvironmentMode)
    function updateScannerFPS(fps) {
        console.log(`⚡ Setting scanner FPS to: ${fps}`);

        // Note: HTML5 QR Scanner doesn't have direct FPS control
        // This would require stopping and restarting the scanner with new config
        if (isScanning) {
            console.log('⚠️ FPS changes require scanner restart. Stop and start scanner to apply.');
            showNotification('info', 'Stop and restart scanner to apply FPS changes');
        }
    }

    // 6. Missing autoEnhanceEnabled variable declaration
    let autoEnhanceEnabled = false;

    // 7. Missing currentEnvironment variable declaration
    let currentEnvironment = 'normal';

    // 8. Improved setEnvironmentMode function (replace existing one)
    function setEnvironmentMode(mode) {
        currentEnvironment = mode;
        console.log(`🌍 Environment mode set to: ${mode}`);

        // Update UI indicator
        const envIndicator = document.getElementById('envModeIndicator');
        if (envIndicator) {
            envIndicator.textContent = mode.charAt(0).toUpperCase() + mode.slice(1);
            envIndicator.className = `env-mode-indicator env-mode-${mode}`;
            envIndicator.style.display = 'inline-block';
        }

        // Apply environment-specific settings
        const settings = {
            'bright': {
                brightness: 40,
                contrast: 140,
                saturation: 100,
                sharpness: 20,
                exposure: 'high',
                tip: 'For bright areas: Use lower brightness, higher contrast'
            },
            'normal': {
                brightness: 100,
                contrast: 100,
                saturation: 100,
                sharpness: 0,
                exposure: 'normal',
                tip: 'Normal indoor lighting'
            },
            'lowlight': {
                brightness: 160,
                contrast: 120,
                saturation: 80,
                sharpness: 30,
                exposure: 'low',
                tip: 'Low light: Use high brightness, enable flashlight'
            }
        };

        const currentSettings = settings[mode] || settings.normal;

        // Apply settings to sliders
        if (document.getElementById('brightnessSlider')) {
            document.getElementById('brightnessSlider').value = currentSettings.brightness;
            updateBrightness(currentSettings.brightness);
        }

        if (document.getElementById('contrastSlider')) {
            document.getElementById('contrastSlider').value = currentSettings.contrast;
            updateContrast(currentSettings.contrast);
        }

        if (document.getElementById('saturationSlider')) {
            document.getElementById('saturationSlider').value = currentSettings.saturation;
            updateSaturation(currentSettings.saturation);
        }

        if (document.getElementById('sharpnessSlider')) {
            document.getElementById('sharpnessSlider').value = currentSettings.sharpness;
            updateSharpness(currentSettings.sharpness);
        }

        if (document.getElementById('exposureSelect')) {
            document.getElementById('exposureSelect').value = currentSettings.exposure;
            updateExposure(currentSettings.exposure);
        }

        // Update scanning tips
        updateScanningTips();
    }


    // Enhanced environment mode functions
    function setEnvironmentMode(mode) {
        currentEnvironment = mode;
        console.log(`🌍 Environment mode set to: ${mode}`);

        // Update UI indicator
        const envIndicator = document.getElementById('envModeIndicator');
        if (envIndicator) {
            envIndicator.textContent = mode.charAt(0).toUpperCase() + mode.slice(1);
            envIndicator.className = `env-mode-indicator env-mode-${mode}`;
            envIndicator.style.display = 'inline-block';
        }

        // Apply environment-specific settings
        applyEnvironmentSettings();

        // Update scanning tips
        updateScanningTips();
    }

    function applyEnvironmentSettings() {
        const brightnessSlider = document.getElementById('brightnessSlider');
        const contrastSlider = document.getElementById('contrastSlider');
        const exposureSelect = document.getElementById('exposureSelect');

        if (!brightnessSlider || !contrastSlider || !exposureSelect) return;

        switch (currentEnvironment) {
            case 'bright':
                // Settings for bright sunlight/warehouse
                brightnessSlider.value = 40; // Lower brightness to reduce glare
                contrastSlider.value = 140; // Higher contrast for visibility
                exposureSelect.value = 'high'; // High exposure for bright areas
                updateBrightness(40);
                updateContrast(140);
                updateExposure('high');
                break;

            case 'lowlight':
                // Settings for low light conditions
                brightnessSlider.value = 160; // Higher brightness
                contrastSlider.value = 120; // Moderate contrast
                exposureSelect.value = 'low'; // Low exposure for dark areas
                updateBrightness(160);
                updateContrast(120);
                updateExposure('low');
                break;

            default: // 'normal'
                // Default settings
                brightnessSlider.value = 100;
                contrastSlider.value = 100;
                exposureSelect.value = 'normal';
                updateBrightness(100);
                updateContrast(100);
                updateExposure('normal');
        }
    }

    function updateBrightness(value) {
        const brightnessValue = document.getElementById('brightnessValue');
        if (brightnessValue) brightnessValue.textContent = value + '%';

        // Apply to video if scanning
        if (isScanning) {
            applyVideoFilters();
        }
    }

    function updateContrast(value) {
        const contrastValue = document.getElementById('contrastValue');
        if (contrastValue) contrastValue.textContent = value + '%';

        // Apply to video if scanning
        if (isScanning) {
            applyVideoFilters();
        }
    }

    function updateExposure(value) {
        const exposureValue = document.getElementById('exposureValue');
        if (exposureValue) exposureValue.textContent = value;

        // In a real implementation, you would use MediaTrackConstraints
        // For now, we'll adjust brightness/contrast accordingly
        if (value === 'high') {
            document.getElementById('brightnessSlider').value = 120;
            document.getElementById('contrastSlider').value = 130;
        } else if (value === 'low') {
            document.getElementById('brightnessSlider').value = 80;
            document.getElementById('contrastSlider').value = 110;
        }

        updateBrightness(document.getElementById('brightnessSlider').value);
        updateContrast(document.getElementById('contrastSlider').value);
    }

    function applyVideoFilters() {
        const videoElement = document.querySelector('#qr-reader video');
        if (!videoElement) return;

        const brightness = document.getElementById('brightnessSlider').value / 100;
        const contrast = document.getElementById('contrastSlider').value / 100;

        // Apply CSS filters for visual enhancement
        videoElement.style.filter = `
        brightness(${brightness})
        contrast(${contrast})
        saturate(1.2)
    `;

        console.log(`🎨 Applied filters: brightness(${brightness}), contrast(${contrast})`);
    }

    function updateScanningTips() {
        const tipsElement = document.getElementById('activeTips');
        if (!tipsElement) return;

        let tips = '';
        switch (currentEnvironment) {
            case 'bright':
                tips = 'For bright areas: Reduce glare, increase contrast, use flashlight if needed';
                break;
            case 'lowlight':
                tips = 'For low light: Increase brightness, use flashlight, hold steady';
                break;
            default:
                tips = 'Hold QR code steady within green frame, ensure good lighting';
        }

        tipsElement.textContent = tips;
    }

    function toggleAutoEnhance(enabled) {
        autoEnhanceEnabled = enabled;
        console.log(`🤖 Auto-enhance ${enabled ? 'enabled' : 'disabled'}`);

        if (enabled && isScanning) {
            // Auto-adjust based on environment
            setTimeout(() => {
                if (autoEnhanceEnabled) {
                    autoAdjustSettings();
                }
            }, 1000);
        }
    }

    function autoAdjustSettings() {
        if (!autoEnhanceEnabled || !isScanning) return;

        console.log('⚡ Auto-adjusting settings for better scanning...');

        // Gradually adjust settings if no scans after 3 seconds
        setTimeout(() => {
            if (scanAttempts === 0 && isScanning) {
                console.log('🔄 No scans detected, adjusting settings...');

                if (currentEnvironment === 'bright') {
                    // For bright environments, try increasing contrast
                    const currentContrast = parseInt(document.getElementById('contrastSlider').value);
                    const newContrast = Math.min(currentContrast + 20, 200);
                    document.getElementById('contrastSlider').value = newContrast;
                    updateContrast(newContrast);
                } else if (currentEnvironment === 'lowlight') {
                    // For low light, try increasing brightness
                    const currentBrightness = parseInt(document.getElementById('brightnessSlider').value);
                    const newBrightness = Math.min(currentBrightness + 30, 200);
                    document.getElementById('brightnessSlider').value = newBrightness;
                    updateBrightness(newBrightness);
                }
            }
        }, 3000);
    }

    // Logitech C903e Optimized Profile
    function applyLogitechProfile() {
        console.log('🎮 Applying Logitech C903e optimized profile');

        // Logitech C903e specific settings (optimized for QR scanning)
        const logitechSettings = {
            // Video settings
            brightness: 112, // Slightly brighter than default
            contrast: 125, // Enhanced contrast for QR edges
            saturation: 90, // Reduced saturation (QR codes are black/white)
            sharpness: 25, // Moderate sharpness (too high adds noise)

            // Camera-specific adjustments
            focusMode: "continuous", // Auto-focus enabled
            exposureMode: "auto", // Auto exposure
            whiteBalance: "auto", // Auto white balance
            fps: 15, // Optimal FPS for this camera
            resolution: "1280x720", // Optimal resolution for QR scanning

            // QR detection optimization
            qrbox: {
                width: 320, // Larger scan area for this camera's wide angle
                height: 320
            }
        };

        // Apply to sliders
        if (document.getElementById('brightnessSlider')) {
            document.getElementById('brightnessSlider').value = logitechSettings.brightness;
            updateBrightness(logitechSettings.brightness);
        }

        if (document.getElementById('contrastSlider')) {
            document.getElementById('contrastSlider').value = logitechSettings.contrast;
            updateContrast(logitechSettings.contrast);
        }

        if (document.getElementById('saturationSlider')) {
            document.getElementById('saturationSlider').value = logitechSettings.saturation;
            updateSaturation(logitechSettings.saturation);
        }

        if (document.getElementById('sharpnessSlider')) {
            document.getElementById('sharpnessSlider').value = logitechSettings.sharpness;
            updateSharpness(logitechSettings.sharpness);
        }

        // Store Logitech settings for scanner restart
        window.logitechScannerConfig = {
            fps: logitechSettings.fps,
            qrbox: logitechSettings.qrbox,
            focusMode: logitechSettings.focusMode
        };

        showNotification('success', 'Logitech C903e profile applied');
        console.log('✅ Logitech C903e settings:', logitechSettings);
    }

    // Enhanced camera detection for Logitech
    function detectAndOptimizeForLogitech() {
        const cameraSelect = document.getElementById('cameraSelect');
        if (!cameraSelect) return;

        const selectedOption = cameraSelect.options[cameraSelect.selectedIndex];
        const cameraLabel = selectedOption.text.toLowerCase();

        // Check if it's a Logitech camera
        if (cameraLabel.includes('logitech') ||
            cameraLabel.includes('c903') ||
            cameraLabel.includes('webcam')) {

            console.log('🎮 Logitech camera detected:', cameraLabel);

            // Auto-apply Logitech profile after 2 seconds
            setTimeout(() => {
                applyLogitechProfile();
                setEnvironmentMode('normal'); // Reset environment mode
            }, 2000);
        }
    }

    // Advanced Webcam Control (if browser supports)
    function enableAdvancedWebcamControls() {
        // Try to get advanced camera controls
        const video = document.querySelector('#qr-reader video');
        if (!video || !video.srcObject) return;

        const stream = video.srcObject;
        const track = stream.getVideoTracks()[0];

        if (!track || !track.getCapabilities) return;

        const capabilities = track.getCapabilities();
        console.log('📷 Camera capabilities:', capabilities);

        // Logitech C903e typically supports:
        // - focusMode (auto, manual)
        // - exposureMode
        // - whiteBalanceMode
        // - zoom (digital)

        if (capabilities.focusMode && capabilities.focusMode.includes('continuous')) {
            console.log('✅ Auto-focus supported');

            // Try to set continuous auto-focus
            try {
                track.applyConstraints({
                    advanced: [{
                        focusMode: 'continuous'
                    }]
                });
            } catch (e) {
                console.log('⚠️ Auto-focus constraint failed:', e.message);
            }
        }
    }

    // Restart with Logitech settings
    async function restartWithLogitechSettings() {
        if (!isScanning) {
            console.log('⚠️ Scanner not running');
            return;
        }

        console.log('🔄 Restarting scanner with Logitech optimization...');

        const cameraId = currentCameraId;
        await stopScanner();

        // Wait for cleanup
        setTimeout(async () => {
            try {
                // Use Logitech config if available
                const config = window.logitechScannerConfig || {
                    fps: 15,
                    qrbox: {
                        width: 320,
                        height: 320
                    },
                    focusMode: "continuous"
                };

                console.log('⚙️ Starting with Logitech config:', config);

                // Start scanner with optimized config
                await startScannerWithCamera(cameraId);

                // Try to enable advanced controls
                setTimeout(enableAdvancedWebcamControls, 1000);

            } catch (error) {
                console.error('❌ Failed to restart with Logitech settings:', error);
            }
        }, 1000);
    }


    // Submit transport form - UPDATED WITH FIXED SELECTOR
    async function submitTransportForm() {
        console.log('🚚 Starting transport form submission...');

        const form = document.getElementById('transportForm');

        // Validate form
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            showNotification('error', 'Please fill in all required fields');
            return;
        }

        if (!document.getElementById('agreeTerms').checked) {
            showNotification('error', 'Please agree to the terms');
            return;
        }

        const itemId = document.getElementById('modalItemId').value;
        const scanType = document.getElementById('modalScanType').value;

        // Validate item ID and scan type
        if (!itemId) {
            showNotification('error', 'Item ID is missing');
            return;
        }

        // Collect form data
        const transportData = {
            item_id: itemId,
            scan_type: scanType,
            from_location: document.getElementById('fromLocation').value,
            to_location: document.getElementById('toLocation').value,
            destination_address: document.getElementById('destinationAddress').value,
            transport_user: document.getElementById('transportUser').value,
            user_contact: document.getElementById('userContact').value,
            user_department: document.getElementById('userDepartment').value,
            user_id_number: document.getElementById('userIdNumber').value,
            vehicle_plate: document.getElementById('vehiclePlate').value,
            vehicle_type: document.getElementById('vehicleType').value,
            vehicle_description: document.getElementById('vehicleDescription').value,
            transport_notes: document.getElementById('transportNotes').value,
            expected_return: document.getElementById('expectedReturn').value,
            priority: document.getElementById('priority').value,
            notes: ''
        };

        console.log('📦 Transport data:', transportData);

        try {
            // Show loading
            const submitBtn = document.getElementById('submitTransport');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';

            // Send to API
            const response = await fetch('api/scan/log.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(transportData)
            });

            console.log('📡 API Response status:', response.status);
            console.log('📡 API Response headers:', response.headers);

            // Get response as text first
            const responseText = await response.text();
            console.log('📡 API Response text:', responseText.substring(0, 1000));

            let data;
            try {
                data = JSON.parse(responseText);
                console.log('📡 API Response data:', data);
            } catch (jsonError) {
                console.error('❌ Failed to parse JSON:', jsonError);
                console.error('❌ Raw response that failed to parse:', responseText);

                // Reset button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;

                // Show more detailed error
                let errorMsg = 'Server returned invalid JSON. ';
                if (responseText.includes('<!DOCTYPE') || responseText.includes('<html')) {
                    errorMsg += 'Server returned HTML instead of JSON. Likely a PHP error.';
                } else if (responseText.includes('PDOException') || responseText.includes('SQLSTATE')) {
                    errorMsg += 'Database error detected in response.';
                }

                showNotification('error', errorMsg);
                updateStatus('error', 'Invalid server response');

                // Open error in new window for debugging
                const errorWindow = window.open();
                if (errorWindow) {
                    errorWindow.document.write('<h2>Error Details</h2>');
                    errorWindow.document.write('<h3>Response Text:</h3>');
                    errorWindow.document.write('<pre>' + responseText + '</pre>');
                    errorWindow.document.write('<h3>Transport Data Sent:</h3>');
                    errorWindow.document.write('<pre>' + JSON.stringify(transportData, null, 2) + '</pre>');
                }

                return;
            }

            if (!data.success) {
                throw new Error(data.message || 'Unknown error from server');
            }

            // Success - close modal and show notification
            if (scanActionModal) {
                scanActionModal.hide();
            }

            // Reset button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;

            // Show success message
            showNotification('success', data.message || 'Scan logged successfully!');

            // Update UI if item data is available
            if (currentItemData && data.item) {
                currentItemData.status = data.item.status;
                currentItemData.stock_location = data.item.location;

                // Update the displayed status - IMPROVED SELECTOR
                const scanResultDiv = document.querySelector('.scan-result');
                if (scanResultDiv) {
                    // Update status badge
                    const statusBadge = scanResultDiv.querySelector('.badge');
                    if (statusBadge) {
                        statusBadge.className = `badge bg-${getStatusColor(data.item.status)} fs-6`;
                        statusBadge.textContent = data.item.status;
                    }

                    // Update location display - MORE ROBUST METHOD
                    // Find all paragraphs in the scan result
                    const paragraphs = scanResultDiv.querySelectorAll('p');
                    paragraphs.forEach(p => {
                        // Check if this paragraph contains "Location" text
                        if (p.textContent.includes('Location')) {
                            // Get the current HTML
                            const currentHtml = p.innerHTML;
                            // Find where the <br> is to split
                            const brIndex = currentHtml.indexOf('<br>');
                            if (brIndex !== -1) {
                                // Keep everything before <br> and replace what's after
                                p.innerHTML = currentHtml.substring(0, brIndex + 4) + data.item.location;
                            }
                        }
                    });
                }
            }

            // Show debug info in console
            console.log('✅ Transport saved successfully!');
            console.log('📊 Scan ID:', data.scan_id);
            console.log('📊 Scanned data:', data.scanned_data);

        } catch (error) {
            console.error('❌ Transport log error:', error);
            console.error('❌ Error stack:', error.stack);

            // Reset button
            const submitBtn = document.getElementById('submitTransport');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check me-1"></i> Confirm & Save';

            // Show error with more details
            let errorMessage = error.message;
            if (error.message.includes('fetch')) {
                errorMessage = 'Network error. Please check your connection.';
            } else if (error.message.includes('JSON')) {
                errorMessage = 'Server returned invalid data. Please try again.';
            }

            showNotification('error', 'Error saving transport: ' + errorMessage);
            updateStatus('error', 'Save failed');
        }
    }

    // Add debug tools to the page
    function addDebugTools() {
        const debugBtn = document.createElement('button');
        debugBtn.className = 'btn btn-danger btn-sm mt-2';
        debugBtn.innerHTML = '<i class="fas fa-bug me-1"></i> Debug Scanner';
        debugBtn.onclick = manualScannerTest;

        // Add visual QR code for testing
        const qrCodeContainer = document.createElement('div');
        qrCodeContainer.className = 'mt-4 border-top pt-3';
        qrCodeContainer.innerHTML = `
            <h6><i class="fas fa-qrcode me-2"></i>Test QR Code Image</h6>
            <div class="text-center">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=%7B%22id%22%3A1%2C%22name%22%3A%22Test%20Item%22%2C%22serial%22%3A%22TEST-001%22%7D" 
                     alt="Test QR Code" 
                     class="img-fluid border p-2"
                     style="max-width: 200px;">
                <p class="small text-muted mt-2">Scan this test QR code</p>
                <p class="small">Contains: <code>{"id":1,"name":"Test Item","serial":"TEST-001"}</code></p>
            </div>
        `;

        const testSection = document.querySelector('.border-top.pt-3');
        if (testSection) {
            testSection.appendChild(debugBtn);
            testSection.parentNode.appendChild(qrCodeContainer);
        }
    }

    // SIMPLE status update function
    function updateStatus(status, text) {
        if (DEBUG) console.log(`Status: ${status} - ${text}`);

        const statusText = document.getElementById('statusText');
        const cameraStatusText = document.getElementById('cameraStatusText');

        if (statusText) statusText.textContent = text;
        if (cameraStatusText) cameraStatusText.textContent = text;

        const indicator = document.getElementById('statusIndicator');
        if (indicator) {
            indicator.className = 'status-indicator';

            if (status === 'ready') {
                indicator.classList.add('status-inactive');
            } else if (status === 'scanning') {
                indicator.classList.add('status-active');
            } else if (status === 'success') {
                indicator.classList.add('status-active');
                indicator.style.backgroundColor = '#28a745';
            } else if (status === 'error') {
                indicator.classList.add('status-error');
            }
        }
    }

    // Initialize everything when page loads
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🔍 Scanner page loaded - Initializing...');

        // Initialize modal
        const modalElement = document.getElementById('scanActionModal');
        if (modalElement) {
            scanActionModal = new bootstrap.Modal(modalElement);

            const submitBtn = document.getElementById('submitTransport');
            if (submitBtn) {
                submitBtn.addEventListener('click', submitTransportForm);
            }
        }

        // Initialize status
        updateStatus('ready', 'Ready to scan');

        // Check if HTML5 QR Code library is loaded
        if (typeof Html5Qrcode === 'undefined') {
            showError('QR Scanner library not loaded');
            return;
        }

        console.log('✅ Html5Qrcode library loaded');

        try {
            // Create scanner instance - FIXED: Use simpler constructor
            html5QrCode = new Html5Qrcode("qr-reader");
            console.log('✅ Scanner instance created');
        } catch (error) {
            console.error('❌ Error creating scanner:', error);
            showError('Error initializing scanner');
            return;
        }

        // Setup button events
        const startBtn = document.getElementById('startBtn');
        const stopBtn = document.getElementById('stopBtn');
        const cameraSelect = document.getElementById('cameraSelect');

        if (startBtn) startBtn.addEventListener('click', startScanner);
        if (stopBtn) stopBtn.addEventListener('click', stopScanner);
        if (cameraSelect) cameraSelect.addEventListener('change', onCameraSelect);

        // Load available cameras
        loadCameras();

        console.log('✅ Scanner initialization complete');

        // Add manual test button
        addManualTestButton();
    });

    // Load available cameras
    async function loadCameras() {
        console.log('📷 Loading available cameras...');

        updateStatus('scanning', 'Detecting cameras...');

        const cameraSelect = document.getElementById('cameraSelect');
        if (cameraSelect) {
            cameraSelect.disabled = true;
            cameraSelect.innerHTML = '<option value="">Loading cameras...</option>';
        }

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            console.error('❌ Camera API not supported');
            updateStatus('error', 'Camera not supported');
            return;
        }

        try {
            // Get camera permission first
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'environment'
                } // Prefer back camera
            });

            // Stop the stream immediately after getting permission
            stream.getTracks().forEach(track => track.stop());

            // Enumerate devices
            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoDevices = devices.filter(device => device.kind === 'videoinput');

            console.log(`✅ Found ${videoDevices.length} camera(s)`);
            availableCameras = videoDevices;

            if (videoDevices.length > 0) {
                updateCameraSelect(videoDevices);
                updateStatus('ready', 'Select camera and start scanning');

                // Show flip button if more than 1 camera
                const flipBtn = document.getElementById('flipBtn');
                if (flipBtn && videoDevices.length > 1) {
                    flipBtn.style.display = 'inline-block';
                }
            } else {
                updateStatus('error', 'No cameras found');
            }
        } catch (error) {
            console.error('❌ Error loading cameras:', error);
            updateStatus('error', 'Camera access denied');

            const cameraInfo = document.getElementById('cameraInfo');
            if (cameraInfo) {
                cameraInfo.textContent = 'Please allow camera access';
                cameraInfo.className = 'form-text text-danger text-start';
            }
        }
    }

    // Update camera select dropdown
    function updateCameraSelect(cameras) {
        const select = document.getElementById('cameraSelect');
        if (!select) return;

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

        // Select first camera by default
        select.value = cameras[0].deviceId;
        select.disabled = false;
        currentCameraIndex = 0;
        currentCameraId = cameras[0].deviceId;

        // Enable start button
        const startBtn = document.getElementById('startBtn');
        if (startBtn) startBtn.disabled = false;
    }

    // Handle camera selection change
    function onCameraSelect() {
        const select = document.getElementById('cameraSelect');
        if (!select) return;

        const cameraId = select.value;
        const selectedOption = select.options[select.selectedIndex];
        const cameraIndex = parseInt(selectedOption.dataset.index);

        if (cameraId && availableCameras[cameraIndex]) {
            const camera = availableCameras[cameraIndex];
            console.log(`📷 Selected camera: ${camera.label || 'Camera ' + (cameraIndex + 1)}`);

            currentCameraId = cameraId;
            currentCameraIndex = cameraIndex;

            // If scanner is running, restart with new camera
            if (isScanning) {
                console.log('🔄 Restarting scanner with new camera...');
                restartScanner(cameraId);
            }
        }
    }

    // Start scanner with specific camera - FIXED VERSION
    async function startScannerWithCamera(cameraId) {
        console.log(`🚀 STARTING SCANNER with camera ID: ${cameraId}`);
        scanAttempts = 0;

        // Update UI
        const startBtn = document.getElementById('startBtn');
        const stopBtn = document.getElementById('stopBtn');

        if (startBtn) startBtn.disabled = true;
        if (stopBtn) stopBtn.disabled = false;

        updateStatus('scanning', 'Starting camera...');

        try {
            // SIMPLER Scanner configuration - FIXED for better QR detection
            const config = {
                fps: 10, // Higher FPS for faster scanning
                qrbox: {
                    width: 300,
                    height: 300
                },
                aspectRatio: 1.0,
                disableFlip: false,
                // ADD THESE FOR BETTER PERFORMANCE:
                experimentalFeatures: {
                    useBarCodeDetectorIfSupported: true
                },
                formatsToSupport: [
                    Html5QrcodeSupportedFormats.QR_CODE
                ],
                focusMode: "continuous", // For autofocus cameras
                focusDistance: 0.2 // 0=infinity, 1=closest
            };

            console.log('⚙️ Scanner config:', config);

            // FIXED: Add proper error handling in callback
            const qrCodeSuccessCallback = (decodedText, decodedResult) => {
                scanAttempts++;
                console.log(`✅ QR Code detected (attempt ${scanAttempts}):`, decodedText.substring(0, 50));
                onScanSuccess(decodedText, decodedResult);
            };

            const qrCodeErrorCallback = (errorMessage) => {
                // Suppress the common "not found" error messages
                if (errorMessage &&
                    !errorMessage.includes('NotFoundException') &&
                    !errorMessage.includes('No MultiFormat Readers')) {
                    if (DEBUG) console.log('Scan error:', errorMessage);
                }
            };

            // Start scanning with specified camera
            await html5QrCode.start(
                cameraId,
                config,
                qrCodeSuccessCallback,
                qrCodeErrorCallback
            );

            // Update UI - Scanner is now active
            isScanning = true;
            updateStatus('scanning', 'Scanning... Point camera at QR code');

            // Add visual feedback
            const qrReader = document.getElementById('qr-reader');
            if (qrReader) qrReader.classList.add('scanner-active');

            console.log('🎉 === SCANNER STARTED SUCCESSFULLY ===');

            // Test scan after 1 second to verify it's working
            setTimeout(() => {
                if (isScanning) {
                    console.log('✅ Scanner is active and ready');
                }
            }, 1000);

        } catch (error) {
            console.error('❌ Scanner startup error:', error);

            let userMessage = 'Failed to start scanner. ';
            if (error.name === 'NotAllowedError') {
                userMessage = 'Camera permission denied. Please allow camera access.';
            } else if (error.name === 'NotFoundError') {
                userMessage = 'Selected camera not found. Try another camera.';
            } else if (error.name === 'NotReadableError') {
                userMessage = 'Camera is already in use by another application.';
            } else {
                userMessage += error.message;
            }

            // Update UI
            updateStatus('error', 'Startup failed');

            if (startBtn) startBtn.disabled = false;
            if (stopBtn) stopBtn.disabled = true;

            showNotification('error', userMessage);

            // Try to stop scanner if it partially started
            try {
                if (html5QrCode.getState() && html5QrCode.getState() !== Html5QrcodeScannerState.STOPPED) {
                    await html5QrCode.stop();
                }
            } catch (e) {
                console.log('Error cleaning up scanner:', e);
            }
        }
    }

    // Main start scanner function
    async function startScanner() {
        const cameraSelect = document.getElementById('cameraSelect');
        if (!cameraSelect) {
            showNotification('error', 'Camera selection not available');
            return;
        }

        const cameraId = cameraSelect.value;

        if (!cameraId) {
            showNotification('error', 'Please select a camera first');
            return;
        }

        startScannerWithCamera(cameraId);
    }

    // Stop scanner function
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

                updateStatus('ready', 'Ready to scan');

                // Remove visual feedback
                const qrReader = document.getElementById('qr-reader');
                if (qrReader) qrReader.classList.remove('scanner-active');

                console.log('✅ Scanner stopped successfully');

            } catch (error) {
                console.error('❌ Error stopping scanner:', error);
                showNotification('error', 'Error stopping scanner');
            }
        }
    }

    // Scan success callback
    function onScanSuccess(decodedText, decodedResult) {
        const now = Date.now();

        // Prevent multiple scans in quick succession
        if (now - lastScanTimestamp < SCAN_COOLDOWN) {
            console.log('⏳ Scan ignored (too soon after last scan)');
            return;
        }

        lastScanTimestamp = now;
        console.log('🔍 Processing QR code:', decodedText.substring(0, 100));

        updateStatus('success', 'Scan successful!');

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

        // Pause scanner briefly to prevent duplicate scans
        if (html5QrCode && isScanning) {
            html5QrCode.pause();

            // Resume after cooldown
            setTimeout(() => {
                if (html5QrCode && isScanning) {
                    html5QrCode.resume();
                    updateStatus('scanning', 'Scanning...');
                }
            }, SCAN_COOLDOWN);
        }
    }

    // Logitech-specific camera optimization
    function optimizeForLogitech() {
        // These are typical Logitech camera settings
        const logitechDefaults = {
            brightness: 110,
            contrast: 120,
            saturation: 100,
            sharpness: 150,
            exposure: 'auto',
            whiteBalance: 'auto'
        };

        console.log('📷 Applying Logitech camera optimization');
        return logitechDefaults;
    }

    // Add to your environment mode function
    function setEnvironmentMode(mode) {
        currentEnvironment = mode;

        const settings = {
            'bright': {
                brightness: 40,
                contrast: 140,
                exposure: 'high',
                fps: 8,
                tip: 'For bright areas: Use lower brightness, higher contrast'
            },
            'normal': {
                brightness: 100,
                contrast: 100,
                exposure: 'normal',
                fps: 10,
                tip: 'Normal indoor lighting'
            },
            'lowlight': {
                brightness: 160,
                contrast: 120,
                exposure: 'low',
                fps: 15, // Higher FPS for dark conditions
                tip: 'Low light: Use high brightness, enable flashlight'
            }
        };

        const currentSettings = settings[mode] || settings.normal;

        // Apply settings
        document.getElementById('brightnessSlider').value = currentSettings.brightness;
        document.getElementById('contrastSlider').value = currentSettings.contrast;
        document.getElementById('exposureSelect').value = currentSettings.exposure;

        updateBrightness(currentSettings.brightness);
        updateContrast(currentSettings.contrast);
        updateExposure(currentSettings.exposure);

        // Update FPS if scanner is running
        if (isScanning) {
            updateScannerFPS(currentSettings.fps);
        }

        console.log(`🌍 Environment: ${mode}, FPS: ${currentSettings.fps}`);
    }


    // In scan.php JavaScript, update the processScan function:

    function processScan(scanData) {
        console.log('🔧 Processing scan data:', scanData);

        let itemId = null;

        // Clean the data
        scanData = scanData.trim();

        // Check for simple ID formats first
        if (scanData.startsWith('ID:')) {
            itemId = scanData.substring(3);
        } else if (scanData.startsWith('ABIL:')) {
            itemId = scanData.substring(5);
        } else if (scanData.startsWith('ITEM-')) {
            itemId = scanData.substring(5);
        }
        // Try to parse as number
        else if (/^\d+$/.test(scanData)) {
            itemId = scanData;
        }
        // Try JSON as fallback
        else {
            try {
                const parsedData = JSON.parse(scanData);
                // Handle different JSON formats
                if (parsedData.i || parsedData.id || parsedData.item_id) {
                    itemId = parsedData.i || parsedData.id || parsedData.item_id;
                }
            } catch (e) {
                // Not JSON
            }
        }

        if (itemId) {
            console.log('📋 Found item ID:', itemId);
            getItemDetails(itemId);
        } else {
            console.log('🔍 No ID found, showing raw data');
            showRawScanData(scanData);
        }
    }


    // Get item details from API - UPDATED DEBUG VERSION
    async function getItemDetails(itemId) {
        try {
            console.log('🌐 Fetching item details for ID:', itemId);
            updateStatus('scanning', 'Fetching item details...');

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
                        // Get the response as text first to see what's actually returned
                        const responseText = await response.text();
                        console.log(`✅ Raw response from ${apiUrl}:`, responseText.substring(0, 500));

                        try {
                            data = JSON.parse(responseText);
                            console.log(`✅ Parsed JSON from ${apiUrl}:`, data);
                            break;
                        } catch (jsonError) {
                            console.error(`❌ Failed to parse JSON from ${apiUrl}:`, jsonError);
                            console.log('Raw response was:', responseText);
                            continue;
                        }
                    } else {
                        console.log(`Failed ${apiUrl}: HTTP ${response.status}`);
                    }
                } catch (e) {
                    console.log(`Network error for ${apiUrl}:`, e.message);
                }
            }

            if (!data) {
                throw new Error('Could not fetch item details from any API endpoint');
            }

            // Check the data structure
            console.log('📊 Full API response:', data);

            if (data.success && data.item) {
                currentItemData = data.item;
                console.log('📋 Current item data:', currentItemData);

                // Determine check in/out based on status
                const currentStatus = data.item.status?.toLowerCase() || 'available';
                const isAvailable = currentStatus === 'available' ||
                    currentStatus === 'in_stock' ||
                    currentStatus === 'stock' ||
                    currentStatus === 'in stock';

                // Show transport modal with appropriate action
                showTransportModal(itemId, isAvailable ? 'check_out' : 'check_in');

                // Also show basic item info
                showResult(data.item);
            } else if (data.item) {
                // If data.item exists but data.success doesn't
                console.log('⚠️ Response has item data but no success flag');
                currentItemData = data.item;

                const currentStatus = data.item.status?.toLowerCase() || 'available';
                const isAvailable = currentStatus === 'available' ||
                    currentStatus === 'in_stock' ||
                    currentStatus === 'stock' ||
                    currentStatus === 'in stock';

                showTransportModal(itemId, isAvailable ? 'check_out' : 'check_in');
                showResult(data.item);
            } else if (data.name) {
                // If the API returns the item directly (not wrapped in item property)
                console.log('⚠️ Response is direct item object');
                currentItemData = data;

                const currentStatus = data.status?.toLowerCase() || 'available';
                const isAvailable = currentStatus === 'available' ||
                    currentStatus === 'in_stock' ||
                    currentStatus === 'stock' ||
                    currentStatus === 'in stock';

                showTransportModal(itemId, isAvailable ? 'check_out' : 'check_in');
                showResult(data);
            } else {
                console.log('❌ Unexpected API response structure:', data);
                showError('Unexpected response format from server');
            }
        } catch (error) {
            console.error('❌ Fetch error:', error);
            showError('Error fetching item details: ' + error.message);
            updateStatus('error', 'Fetch error');
        }
    }

    // Show raw scan data when no ID found
    function showRawScanData(scanData) {
        updateStatus('error', 'No item ID found');

        const resultsContainer = document.getElementById('results');
        if (!resultsContainer) return;

        const html = `
        <div class="alert alert-warning">
            <h5><i class="fas fa-exclamation-triangle me-2"></i>No Item ID Found</h5>
            <p>The scanned QR code doesn't contain a valid item ID.</p>
            <div class="mt-3">
                <strong>Scanned Data:</strong>
                <div class="alert alert-light mt-2">
                    <code>${scanData.substring(0, 200)}${scanData.length > 200 ? '...' : ''}</code>
                </div>
            </div>
            <div class="mt-3">
                <button class="btn btn-primary" onclick="testScanJSON()">
                    <i class="fas fa-vial me-1"></i> Test with Sample Data
                </button>
                <button class="btn btn-secondary ms-2" onclick="clearResults()">
                    <i class="fas fa-times me-1"></i> Clear
                </button>
            </div>
        </div>`;

        resultsContainer.innerHTML = html;
    }

    // Get item details from API
    // Show scan result - UPDATED VERSION
    function showResult(item) {
        console.log('🎯 Displaying result for item:', item);

        // Extract item properties based on different possible structures
        const itemName = item.name || item.item_name || 'Unknown Item';
        const serialNumber = item.serial_number || item.serial || item.serial_no || 'N/A';
        const category = item.category || item.category_name || 'N/A';
        const status = item.status || item.item_status || 'available';
        const location = item.stock_location || item.location || item.current_location || 'N/A';

        console.log('📊 Parsed item data:', {
            itemName,
            serialNumber,
            category,
            status,
            location
        });

        updateStatus('success', 'Scan complete!');

        const resultsContainer = document.getElementById('results');
        if (!resultsContainer) return;

        const html = `
    <div class="scan-result">
        <div class="text-center mb-3">
            <div class="scan-success-icon">
                <i class="fas fa-check-circle fa-3x text-success"></i>
            </div>
            <h4 class="text-success mt-2">Scan Successful!</h4>
            <p class="text-muted">
                <i class="fas fa-clock me-1"></i>${new Date().toLocaleTimeString()}
            </p>
        </div>
        
        <div class="card border-success">
            <div class="card-header bg-success bg-opacity-10 border-success">
                <h5 class="mb-0 text-success">${itemName}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong><i class="fas fa-barcode me-1"></i>Serial:</strong><br>
                        <code class="fs-6">${serialNumber}</code></p>
                        <p><strong><i class="fas fa-tags me-1"></i>Category:</strong><br>
                        ${category}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong><i class="fas fa-circle me-1"></i>Status:</strong><br>
                        <span class="badge bg-${getStatusColor(status)} fs-6">
                            ${status}
                        </span></p>
                        <p><strong><i class="fas fa-map-marker-alt me-1"></i>Location:</strong><br>
                        ${location}</p>
                    </div>
                </div>
                
                <hr>
                
                <div class="mt-3">
                    <h6><i class="fas fa-bolt me-2"></i>Quick Actions:</h6>
                    <div class="d-flex gap-2 flex-wrap">
                        ${item.id ? `<a href="items/view.php?id=${item.id}" class="btn btn-primary btn-sm">
                            <i class="fas fa-eye me-1"></i> View Details
                        </a>` : ''}
                        <button class="btn btn-success btn-sm" onclick="showTransportModal(${item.id || item.item_id || 'null'}, 'check_in')">
                            <i class="fas fa-sign-in-alt me-1"></i> Check In
                        </button>
                        <button class="btn btn-warning btn-sm" onclick="showTransportModal(${item.id || item.item_id || 'null'}, 'check_out')">
                            <i class="fas fa-sign-out-alt me-1"></i> Check Out
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>`;

        resultsContainer.innerHTML = html;

        // Play success sound
        playSuccessSound();

        // Show success notification
        showNotification('success', `Scanned: ${itemName}`);
    }

    // Show transport modal
    // Show transport modal
    function showTransportModal(itemId, scanType) {
        if (!currentItemData) {
            showNotification('error', 'Item data not available');
            return;
        }

        console.log('📦 Current item data for modal:', currentItemData);

        // Set modal title based on action
        const actionTitle = scanType === 'check_in' ? 'Check In Item' : 'Check Out Item';
        const actionText = scanType === 'check_in' ? 'Check In' : 'Check Out';

        // Get item name from either name or item_name property
        const itemName = currentItemData.name || currentItemData.item_name || 'Unknown Item';
        const serialNumber = currentItemData.serial_number || currentItemData.serial || 'N/A';
        const currentLocation = currentItemData.stock_location || currentItemData.location || 'Stock';

        // Update modal content
        document.getElementById('modalActionTitle').textContent = actionTitle;
        document.getElementById('modalItemId').value = itemId;
        document.getElementById('modalScanType').value = scanType;
        document.getElementById('modalItemName').textContent = itemName;
        document.getElementById('modalSerialNumber').textContent = serialNumber;
        document.getElementById('modalCurrentLocation').textContent = currentLocation;
        document.getElementById('modalActionType').textContent = actionText;
        document.getElementById('modalActionType').className = `badge bg-${scanType === 'check_in' ? 'success' : 'warning'}`;

        // Set default "From" location
        document.getElementById('fromLocation').value = currentLocation;

        // Pre-fill destination for check-in
        if (scanType === 'check_in') {
            document.getElementById('toLocation').value = 'Stock';
        } else {
            document.getElementById('toLocation').value = '';
        }

        // Clear other form fields
        document.getElementById('destinationAddress').value = '';
        document.getElementById('transportUser').value = '';
        document.getElementById('userContact').value = '';
        document.getElementById('userDepartment').value = '';
        document.getElementById('userIdNumber').value = '';
        document.getElementById('vehiclePlate').value = '';
        document.getElementById('vehicleType').value = '';
        document.getElementById('vehicleDescription').value = '';
        document.getElementById('transportNotes').value = '';
        document.getElementById('expectedReturn').value = '';
        document.getElementById('priority').value = 'normal';
        document.getElementById('agreeTerms').checked = false;

        // Show/hide expected return based on action
        const expectedReturnField = document.getElementById('expectedReturn').closest('.mb-3');
        if (expectedReturnField) {
            if (scanType === 'check_out') {
                expectedReturnField.style.display = 'block';
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 7);
                document.getElementById('expectedReturn').value = tomorrow.toISOString().split('T')[0];
            } else {
                expectedReturnField.style.display = 'none';
            }
        }

        // Show modal
        if (scanActionModal) {
            console.log('📋 Showing transport modal with item:', itemName);
            scanActionModal.show();
        }
    }

    // Add manual test button for debugging


    // Show error message
    function showError(message) {
        console.error('❌ Displaying error:', message);

        updateStatus('error', 'Scan failed');

        const resultsContainer = document.getElementById('results');
        if (!resultsContainer) return;

        const html = `
        <div class="alert alert-danger">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                <div>
                    <h5 class="mb-1">Scan Error</h5>
                    <p class="mb-0">${message}</p>
                    <p class="small mt-2">
                        <strong>Tip:</strong> Make sure the QR code contains a valid item ID in JSON format like: 
                        <code>{"id": 123}</code> or a URL with <code>?id=123</code>
                    </p>
                </div>
            </div>
        </div>
        <div class="text-center mt-3">
            <button class="btn btn-outline-secondary" onclick="clearResults()">
                <i class="fas fa-times me-1"></i> Clear
            </button>
            <button class="btn btn-outline-primary ms-2" onclick="testScanJSON()">
                <i class="fas fa-redo me-1"></i> Try Test Scan
            </button>
        </div>`;

        resultsContainer.innerHTML = html;
    }

    // Clear results
    function clearResults() {
        const resultsContainer = document.getElementById('results');
        if (!resultsContainer) return;

        resultsContainer.innerHTML = `
        <div class="text-center text-muted py-5">
            <i class="fas fa-qrcode fa-4x mb-3"></i>
            <h5>No scans yet</h5>
            <p>Scan a QR code to see results here</p>
        </div>`;

        updateStatus('ready', 'Ready to scan');
    }

    // Show notification function
    function showNotification(type, message) {
        // Remove existing notifications
        const existing = document.querySelectorAll('.alert-notification');
        existing.forEach(el => el.remove());

        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show`;
        notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${type === 'success' ? '<i class="fas fa-check-circle me-2"></i>' : 
              type === 'error' ? '<i class="fas fa-exclamation-triangle me-2"></i>' : 
              '<i class="fas fa-info-circle me-2"></i>'}
            ${message}
            <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    // Test functions
    function testScanJSON() {
        console.log('🧪 Testing JSON QR code');
        const testData = {
            id: 1,
            name: "Test Laptop",
            serial_number: "TEST-001",
            category: "Electronics",
            status: "available",
            stock_location: "Warehouse A"
        };
        processScan(JSON.stringify(testData));
    }

    function testScanURL() {
        console.log('🧪 Testing URL QR code');
        const testData = "http://localhost/ability_app/items/view.php?id=1";
        processScan(testData);
    }

    function testScanNumber() {
        console.log('🧪 Testing Number QR code');
        const testData = "1";
        processScan(testData);
    }

    // Helper function for status colors
    function getStatusColor(status) {
        if (!status) return 'secondary';

        const statusLower = status.toLowerCase().replace(/\s+/g, '_');
        const colors = {
            'available': 'success',
            'in_stock': 'success',
            'in stock': 'success',
            'in_use': 'primary',
            'in use': 'primary',
            'checked_out': 'warning',
            'checked out': 'warning',
            'check_out': 'warning',
            'check_in': 'success',
            'maintenance': 'warning',
            'reserved': 'info',
            'lost': 'danger',
            'damaged': 'danger'
        };
        return colors[statusLower] || 'secondary';
    }

    // Play success sound
    function playSuccessSound() {
        try {
            // Simple beep
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

    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        if (html5QrCode && isScanning) {
            html5QrCode.stop().catch(e => console.log('Stop error on unload:', e));
        }
    });

    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Space to start/stop scanner
        if (e.code === 'Space' && !e.target.matches('input, textarea, select')) {
            e.preventDefault();
            if (isScanning) {
                stopScanner();
            } else {
                startScanner();
            }
        }

        // Escape to clear results
        if (e.code === 'Escape') {
            clearResults();
        }
    });

    // ============================================================================
    // DEBUG TOOLS - SINGLE VERSION
    // ============================================================================

    function addManualTestButton() {
        console.log('🔧 Adding debug tools...');

        const testSection = document.querySelector('.border-top.pt-3');
        if (!testSection) {
            console.warn('❌ Test section not found');
            return;
        }

        // Create container for debug tools
        const debugContainer = document.createElement('div');
        debugContainer.className = 'debug-tools mt-3 p-3 border rounded bg-light';

        // Title
        const title = document.createElement('h6');
        title.className = 'mb-2 text-primary';
        title.innerHTML = '<i class="fas fa-vial me-1"></i> Debug & Test Tools';

        // Description
        const desc = document.createElement('p');
        desc.className = 'small text-muted mb-3';
        desc.textContent = 'Use these tools for testing without physical QR codes';

        // Button container
        const buttonContainer = document.createElement('div');
        buttonContainer.className = 'd-flex flex-wrap gap-2';

        // Button 1: Quick JSON Test
        const jsonBtn = document.createElement('button');
        jsonBtn.className = 'btn btn-warning btn-sm';
        jsonBtn.innerHTML = '<i class="fas fa-qrcode me-1"></i> Quick JSON Test';
        jsonBtn.onclick = testScanJSON;
        jsonBtn.title = 'Test with sample JSON data';

        // Button 2: Debug Scanner
        const debugBtn = document.createElement('button');
        debugBtn.className = 'btn btn-danger btn-sm';
        debugBtn.innerHTML = '<i class="fas fa-bug me-1"></i> Debug Scanner';
        debugBtn.onclick = manualScannerTest;
        debugBtn.title = 'Run automated scanner tests';

        // Button 3: Manual Input
        const manualBtn = document.createElement('button');
        manualBtn.className = 'btn btn-info btn-sm';
        manualBtn.innerHTML = '<i class="fas fa-keyboard me-1"></i> Manual Input';
        manualBtn.onclick = function() {
            const testId = prompt('Enter item ID (number) or paste QR content:', '1');
            if (testId) {
                if (!isNaN(testId)) {
                    // It's a number
                    console.log(`Manual test with ID: ${testId}`);
                    getItemDetails(parseInt(testId));
                } else {
                    // It's text/JSON/URL
                    console.log(`Manual test with data: ${testId.substring(0, 50)}`);
                    processScan(testId);
                }
            }
        };
        manualBtn.title = 'Enter ID or QR content manually';

        // Button 4: Clear Results
        const clearBtn = document.createElement('button');
        clearBtn.className = 'btn btn-secondary btn-sm';
        clearBtn.innerHTML = '<i class="fas fa-broom me-1"></i> Clear Results';
        clearBtn.onclick = clearResults;
        clearBtn.title = 'Clear scan results';

        // Button 5: Test URL
        const urlBtn = document.createElement('button');
        urlBtn.className = 'btn btn-success btn-sm';
        urlBtn.innerHTML = '<i class="fas fa-link me-1"></i> Test URL';
        urlBtn.onclick = testScanURL;
        urlBtn.title = 'Test with URL QR code';

        // Button 6: Test Number
        const numBtn = document.createElement('button');
        numBtn.className = 'btn btn-primary btn-sm';
        numBtn.innerHTML = '<i class="fas fa-hashtag me-1"></i> Test Number';
        numBtn.onclick = testScanNumber;
        numBtn.title = 'Test with numeric QR code';

        // Add all buttons
        buttonContainer.appendChild(jsonBtn);
        buttonContainer.appendChild(urlBtn);
        buttonContainer.appendChild(numBtn);
        buttonContainer.appendChild(manualBtn);
        buttonContainer.appendChild(debugBtn);
        buttonContainer.appendChild(clearBtn);

        // Assemble the container
        debugContainer.appendChild(title);
        debugContainer.appendChild(desc);
        debugContainer.appendChild(buttonContainer);

        // Add to page
        testSection.appendChild(debugContainer);

        console.log('✅ Debug tools added successfully');
    }

    // ============================================================================
    // END DEBUG TOOLS
    // ============================================================================
</script>


<?php
require_once 'views/partials/footer.php';
?>