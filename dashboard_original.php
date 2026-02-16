<?php
// dashboard.php or index.php

// Include bootstrap first
require_once 'bootstrap.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Check authentication - Use the new method
if (!isLoggedIn()) {
    $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
    redirect('login.php');
}

// Set page variables
$pageTitle = "Dashboard - Equipment Inventory";
$showBreadcrumb = true;
$breadcrumbItems = [
    'Dashboard' => ''
];

// Get dashboard stats
$db = new Database();
$conn = $db->getConnection();
$stats = getDashboardStats($conn);
$recentItems = getRecentItems($conn, 10);

// Include header
require_once 'views/partials/header.php';
?>

<!-- Dashboard Content -->
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
        <div class="btn-group">
            <a href="items/create.php" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                <i class="fas fa-plus me-1"></i> Add Equipment
            </a>
            <a href="scan.php" class="btn btn-sm btn-success">
                <i class="fas fa-qrcode me-1"></i> Scan QR
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Equipment
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['total_items']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tools fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Available
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['available']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                In Use
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['in_use']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-wrench fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Categories
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['categories']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <!-- Recent Items Table -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history me-2"></i>Recently Added Equipment
                    </h6>
                    <button class="btn btn-sm btn-primary" id="refreshItemsBtn">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="recentItemsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th width="5%">ID</th>
                                    <th>Name</th>
                                    <th>Serial</th>
                                    <th>Category</th>
                                    <th width="10%">Status</th>
                                    <th>Location</th>
                                    <th width="15%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentItems)): ?>
                                    <?php foreach ($recentItems as $item): ?>
                                        <tr>
                                            <td><?php echo $item['id']; ?></td>
                                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['serial_number']); ?></td>
                                            <td><?php echo getCategoryBadge($item['category']); ?></td>
                                            <td><?php echo getStatusBadge($item['status']); ?></td>
                                            <td><?php echo htmlspecialchars($item['stock_location'] ?? 'N/A'); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="items/view.php?id=<?php echo $item['id']; ?>"
                                                        class="btn btn-info" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="items/edit.php?id=<?php echo $item['id']; ?>"
                                                        class="btn btn-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No equipment found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-3">
                        <a href="scan.php" class="btn btn-lg btn-outline-primary text-start">
                            <i class="fas fa-qrcode fa-2x me-3"></i>
                            <div>
                                <h5 class="mb-1">Scan QR Code</h5>
                                <p class="mb-0 text-muted">Scan equipment QR codes</p>
                            </div>
                        </a>

                        <a href="reports.php" class="btn btn-lg btn-outline-success text-start">
                            <i class="fas fa-file-export fa-2x me-3"></i>
                            <div>
                                <h5 class="mb-1">Export Data</h5>
                                <p class="mb-0 text-muted">Export to Excel or PDF</p>
                            </div>
                        </a>

                        <a href="items/print-labels.php" class="btn btn-lg btn-outline-warning text-start">
                            <i class="fas fa-print fa-2x me-3"></i>
                            <div>
                                <h5 class="mb-1">Print Labels</h5>
                                <p class="mb-0 text-muted">Print QR code labels</p>
                            </div>
                        </a>

                        <a href="items/create.php" class="btn btn-lg btn-outline-info text-start">
                            <i class="fas fa-plus-circle fa-2x me-3"></i>
                            <div>
                                <h5 class="mb-1">Add Equipment</h5>
                                <p class="mb-0 text-muted">Add new equipment item</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Status Distribution -->
            <?php $statusCounts = getEquipmentCountByStatus($conn); ?>
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie me-2"></i>Status Distribution
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie">
                        <canvas id="statusChart" height="200"></canvas>
                    </div>
                    <div class="mt-4 small">
                        <?php foreach ($statusCounts as $status => $count): ?>
                            <?php if ($count > 0): ?>
                                <div class="d-flex justify-content-between mb-1">
                                    <span><?php echo ucfirst(str_replace('_', ' ', $status)); ?></span>
                                    <span class="font-weight-bold"><?php echo $count; ?></span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ... Modal content ... -->

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addItemModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Add New Equipment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addItemForm" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <!-- Item Name -->
                            <div class="mb-3">
                                <label for="item_name" class="form-label required">Equipment Name</label>
                                <input type="text" class="form-control" id="item_name" name="item_name" required>
                                <div class="form-text">Enter a descriptive name for the equipment</div>
                            </div>

                            <!-- Serial Number -->
                            <div class="mb-3">
                                <label for="serial_number" class="form-label required">Serial Number</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="serial_number" name="serial_number" required>
                                    <button class="btn btn-outline-secondary" type="button" id="generateSerialBtn">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                                <div class="form-text">Unique identifier for tracking</div>
                            </div>

                            <!-- Category -->
                            <div class="mb-3">
                                <label for="category" class="form-label required">Category</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <?php
                                    $categories = getCategories();
                                    foreach ($categories as $key => $value):
                                    ?>
                                        <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Department -->
                            <div class="mb-3">
                                <label for="department" class="form-label">Department</label>
                                <select class="form-select" id="department" name="department">
                                    <option value="">Select Department</option>
                                    <?php
                                    $departments = getDepartments();
                                    foreach ($departments as $key => $value):
                                    ?>
                                        <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <!-- Status -->
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <?php
                                    $statuses = getStatuses();
                                    foreach ($statuses as $key => $value):
                                    ?>
                                        <option value="<?php echo $key; ?>" <?php echo $key === 'available' ? 'selected' : ''; ?>>
                                            <?php echo $value; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Condition -->
                            <div class="mb-3">
                                <label for="condition" class="form-label">Condition</label>
                                <select class="form-select" id="condition" name="condition">
                                    <?php
                                    $conditions = getConditions();
                                    foreach ($conditions as $key => $value):
                                    ?>
                                        <option value="<?php echo $key; ?>" <?php echo $key === 'good' ? 'selected' : ''; ?>>
                                            <?php echo $value; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Location -->
                            <div class="mb-3">
                                <label for="stock_location" class="form-label">Location</label>
                                <select class="form-select" id="stock_location" name="stock_location">
                                    <option value="">Select Location</option>
                                    <?php
                                    $locations = getLocations();
                                    foreach ($locations as $key => $value):
                                    ?>
                                        <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Quantity -->
                            <div class="mb-3">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1">
                            </div>
                        </div>
                    </div>

                    <!-- Brand/Model -->
                    <div class="mb-3">
                        <label for="brand_model" class="form-label">Brand/Model</label>
                        <input type="text" class="form-control" id="brand_model" name="brand_model" placeholder="e.g., Dell Latitude, Sony HD">
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2" placeholder="Additional details about the equipment..."></textarea>
                    </div>

                    <!-- Notes -->
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Any additional notes..."></textarea>
                    </div>

                    <!-- Image Upload -->
                    <div class="mb-3">
                        <label for="item_image" class="form-label">Equipment Image</label>
                        <input type="file" class="form-control" id="item_image" name="item_image" accept="image/*">
                        <div class="form-text">Max size: 5MB. Allowed: JPG, PNG, GIF, WebP</div>
                        <div class="mt-2" id="imagePreview" style="display: none;">
                            <img src="" alt="Preview" class="img-thumbnail" style="max-height: 150px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save me-1"></i> Save Equipment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>



<!-- Chart.js for Status Distribution -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php
// Include footer (which loads jQuery)
require_once 'views/partials/footer.php';
$db->close();
?>


<script>
    // ========== GLOBAL FUNCTIONS (MUST BE OUTSIDE $(document).ready()) ==========

    // Function to download single QR code
    function downloadSingleQRCode(qrUrl, itemName, serial) {
        if (!qrUrl) {
            toastr.error('No QR code available to download');
            return;
        }

        const link = document.createElement('a');
        link.href = qrUrl;

        // Create a safe filename
        const safeName = (itemName || 'item')
            .replace(/[<>:"/\\|?*]/g, '_')
            .replace(/\s+/g, '_')
            .substring(0, 50);

        const safeSerial = (serial || 'item').replace(/[^a-z0-9]/gi, '_');
        link.download = `QR_${safeName}_${safeSerial}.png`;

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        toastr.success('QR Code downloaded!');
    }

    // Function to download ALL QR codes
    function downloadAllQRCodes() {
        // Get all table rows (skip header)
        const rows = document.querySelectorAll('#recentItemsTable tbody tr');

        if (rows.length === 0) {
            toastr.error('No items found in the table');
            return;
        }

        toastr.info(`Found ${rows.length} items. Checking for QR codes...`);

        let qrCount = 0;
        const promises = [];

        // Process each row
        rows.forEach((row, index) => {
            // Find the quick view button to get item data
            const quickBtn = row.querySelector('.quick-view-btn');
            if (!quickBtn) return;

            const itemId = quickBtn.dataset.itemId;
            const itemName = quickBtn.dataset.itemName || `Item_${index + 1}`;
            const serial = quickBtn.dataset.itemSerial || '';
            const qrCode = quickBtn.dataset.qrCode || '';

            if (!qrCode || qrCode === '' || qrCode === 'pending') {
                console.log(`No QR code for ${itemName}`);
                return;
            }

            qrCount++;

            // Create promise for downloading this QR code
            promises.push(new Promise((resolve) => {
                setTimeout(() => {
                    try {
                        // Clean the filename
                        const safeName = itemName
                            .replace(/[<>:"/\\|?*]/g, '')
                            .replace(/\s+/g, '_')
                            .substring(0, 50);

                        const safeSerial = serial.replace(/[^a-z0-9]/gi, '_');
                        const filename = `QR_${safeName}_${safeSerial || 'item'}.png`;

                        // Create download link
                        const link = document.createElement('a');
                        link.href = qrCode;
                        link.download = filename;

                        // Trigger download
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);

                        console.log(`Downloaded: ${itemName}`);
                        resolve();
                    } catch (error) {
                        console.error(`Error downloading ${itemName}:`, error);
                        resolve();
                    }
                }, index * 500);
            }));
        });

        if (qrCount === 0) {
            toastr.error('No QR codes found in the current table view');
            return;
        }

        toastr.info(`Starting download of ${qrCount} QR codes...`);

        // Process all promises
        Promise.all(promises).then(() => {
            setTimeout(() => {
                toastr.success(`Successfully downloaded ${qrCount} QR codes!`);
            }, 500);
        });
    }

    // Function to generate QR code from quick view
    function generateQRCodeFromQuickView(itemId, itemName) {
        if (!itemId) {
            toastr.error('Invalid item ID');
            return;
        }

        if (confirm(`Generate QR Code for "${itemName}"?`)) {
            $.ajax({
                url: 'api/generate_qr.php',
                method: 'POST',
                data: {
                    item_id: itemId
                },
                dataType: 'json',
                beforeSend: function() {
                    toastr.info('Generating QR code...');
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success('QR Code generated successfully!');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        toastr.error(response.message || 'Failed to generate QR code');
                    }
                },
                error: function() {
                    toastr.error('Error generating QR code');
                }
            });
        }
    }

    // Main function to generate QR codes and download as ZIP
    function generateAndDownloadQRZip() {
        // Create confirmation modal HTML
        const confirmModalHtml = `
    <div class="modal fade" id="qrConfirmModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Generate QR Codes</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-qrcode fa-3x text-primary mb-3"></i>
                        <p class="lead">This will generate QR codes for all items and create a ZIP file.</p>
                        <p class="text-muted">This may take a few moments.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="confirmGenerateBtn">
                        <i class="fas fa-play me-1"></i> Continue
                    </button>
                </div>
            </div>
        </div>
    </div>
    `;

        // Remove existing modal if any
        $('#qrConfirmModal').remove();
        $('body').append(confirmModalHtml);

        const confirmModal = new bootstrap.Modal(document.getElementById('qrConfirmModal'));
        confirmModal.show();

        // Handle confirmation button click
        $('#confirmGenerateBtn').off('click').on('click', function() {
            confirmModal.hide();
            $('#qrConfirmModal').remove();
            proceedWithQRGeneration();
        });

        // Handle modal close
        $('#qrConfirmModal').on('hidden.bs.modal', function() {
            $('#qrConfirmModal').remove();
            toastr.info('Operation cancelled.');
        });

        function proceedWithQRGeneration() {
            // Disable the button to prevent multiple clicks
            const button = document.querySelector('button[onclick*="generateAndDownloadQRZipWithProgress"]');
            if (button) {
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processing...';
                button.disabled = true;
            }

            // Create progress modal
            const modalHtml = `
        <div class="modal fade" id="qrZipProgressModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Generating QR Codes & ZIP</h5>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div class="mb-2">
                            <span id="qrZipProgressText">Initializing...</span>
                        </div>
                        <div class="progress" style="height: 20px;">
                            <div id="qrZipProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: 0%">0%</div>
                        </div>
                        <div class="mt-3 small text-muted">
                            <div>Status: <span id="qrZipStatus">Preparing...</span></div>
                            <div>Progress: <span id="qrZipProgress">0%</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        `;

            // Remove existing modal if any
            $('#qrZipProgressModal').remove();
            $('body').append(modalHtml);

            const modal = new bootstrap.Modal(document.getElementById('qrZipProgressModal'));
            modal.show();

            // Update progress
            $('#qrZipProgressText').text('Starting QR code generation...');
            $('#qrZipStatus').text('Connecting to server...');

            // Start the process
            $.ajax({
                url: 'api/generate_all_qr_codes.php',
                method: 'POST',
                dataType: 'json',
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();

                    // Track progress
                    xhr.addEventListener('progress', function(evt) {
                        if (evt.lengthComputable) {
                            const percentComplete = (evt.loaded / evt.total) * 100;
                            $('#qrZipProgressBar').css('width', percentComplete + '%')
                                .text(Math.round(percentComplete) + '%');
                            $('#qrZipProgress').text(Math.round(percentComplete) + '%');

                            if (percentComplete < 100) {
                                $('#qrZipProgressText').text('Processing...');
                                $('#qrZipStatus').text('Uploading: ' + Math.round(percentComplete) + '%');
                            }
                        }
                    });

                    return xhr;
                },
                beforeSend: function() {
                    $('#qrZipProgressText').text('Sending request to server...');
                },
                success: function(response) {
                    console.log('Server response:', response);

                    if (response.success) {
                        $('#qrZipProgressText').text('Processing complete!');
                        $('#qrZipProgressBar').css('width', '100%').text('100%').removeClass('progress-bar-animated');
                        $('#qrZipStatus').text('Creating download...');
                        $('#qrZipProgress').text('100%');

                        setTimeout(() => {
                            modal.hide();
                            $('#qrZipProgressModal').remove();

                            toastr.success(response.message);

                            // Trigger download
                            if (response.download_url) {
                                const downloadLink = document.createElement('a');
                                downloadLink.href = response.download_url;
                                downloadLink.download = response.filename || 'qr_codes.zip';
                                downloadLink.target = '_blank';
                                document.body.appendChild(downloadLink);
                                downloadLink.click();
                                document.body.removeChild(downloadLink);

                                toastr.success('Download started! Check your downloads folder.');
                            }

                            // Refresh page after a delay
                            setTimeout(() => {
                                location.reload();
                            }, 3000);
                        }, 1000);
                    } else {
                        modal.hide();
                        $('#qrZipProgressModal').remove();
                        toastr.error(response.message || 'Failed to generate QR codes');
                    }

                    // Re-enable button
                    if (button) {
                        button.innerHTML = '<i class="fas fa-file-archive me-1"></i> Generate & Download ZIP';
                        button.disabled = false;
                    }
                },
                error: function(xhr, status, error) {
                    modal.hide();
                    $('#qrZipProgressModal').remove();

                    console.error('Error details:', error);
                    console.error('XHR response:', xhr.responseText);

                    let errorMessage = 'Failed to generate QR codes';

                    try {
                        // Try to parse error response
                        if (xhr.responseText) {
                            // Check if it's HTML error
                            if (xhr.responseText.includes('<br') || xhr.responseText.includes('<b>')) {
                                // Extract just the error message
                                const tempDiv = document.createElement('div');
                                tempDiv.innerHTML = xhr.responseText;
                                const text = tempDiv.textContent || tempDiv.innerText || '';

                                // Find the actual error message
                                const lines = text.split('\n').filter(line => line.trim());
                                errorMessage = lines.length > 0 ? lines[0].substring(0, 200) : 'Server error occurred';
                            } else {
                                // Try to parse as JSON
                                const jsonResponse = JSON.parse(xhr.responseText);
                                if (jsonResponse && jsonResponse.message) {
                                    errorMessage = jsonResponse.message;
                                }
                            }
                        }
                    } catch (e) {
                        errorMessage = xhr.statusText || 'Server error';
                    }

                    toastr.error('Error: ' + errorMessage);

                    // Re-enable button
                    if (button) {
                        button.innerHTML = '<i class="fas fa-file-archive me-1"></i> Generate & Download ZIP';
                        button.disabled = false;
                    }
                }
            });
        }
    }

    // Function to generate QR codes and download as ZIP
    function generateAndDownloadQRZipWithProgress() {
        // Disable the button to prevent multiple clicks
        const button = document.querySelector('button[onclick*="generateAndDownloadQRZipWithProgress"]');
        if (button) {
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processing...';
            button.disabled = true;
        }

        toastr.info('Starting QR code generation...');

        // Create a simple progress modal
        const modalHtml = `
<div class="modal fade" id="qrProcessingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Generating QR Codes</h5>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Generating QR codes for all items...</p>
                    <p class="text-muted small">This may take a few moments.</p>
                </div>
            </div>
        </div>
    </div>
</div>`;

        // Remove existing modal if any
        $('#qrProcessingModal').remove();
        $('body').append(modalHtml);

        const modal = new bootstrap.Modal(document.getElementById('qrProcessingModal'));
        modal.show();

        // Make the AJAX call
        $.ajax({
            url: 'api/quick_qr_zip.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('Response:', response);

                // Close modal first
                modal.hide();
                setTimeout(() => {
                    $('#qrProcessingModal').remove();
                }, 300);

                if (response.success) {
                    toastr.success(response.message);

                    // Trigger download immediately
                    if (response.download_url) {
                        const link = document.createElement('a');
                        link.href = response.download_url;
                        link.download = response.filename || 'qr_codes.zip';
                        link.style.display = 'none';
                        document.body.appendChild(link);
                        link.click();

                        // Clean up after a short delay
                        setTimeout(() => {
                            document.body.removeChild(link);
                            toastr.success('Download started! Check your downloads folder.');
                        }, 100);
                    }
                } else {
                    toastr.error(response.message || 'Failed to generate QR codes');
                }

                // Re-enable button
                if (button) {
                    button.innerHTML = '<i class="fas fa-file-archive me-1"></i> Generate & Download ZIP';
                    button.disabled = false;
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                console.log('Response:', xhr.responseText);

                modal.hide();
                $('#qrProcessingModal').remove();

                let errorMessage = 'Failed to generate QR codes';

                try {
                    // Try to parse as JSON
                    const jsonResponse = JSON.parse(xhr.responseText);
                    if (jsonResponse && jsonResponse.message) {
                        errorMessage = jsonResponse.message;
                    }
                } catch (e) {
                    // If not JSON, show raw error
                    errorMessage = 'Server error: ' + error;
                }

                toastr.error(errorMessage);

                // Re-enable button
                if (button) {
                    button.innerHTML = '<i class="fas fa-file-archive me-1"></i> Generate & Download ZIP';
                    button.disabled = false;
                }
            }
        });
    }

    // Function to clear image preview
    function clearImagePreview() {
        $('#item_image').val('');
        $('#imagePreview').hide();
    }

    // Function to generate QR code for item (used in quick view modal)
    function generateQRCodeForItem(itemId, itemName) {
        if (!itemId) {
            toastr.error('Invalid item ID');
            return;
        }

        if (confirm(`Generate QR Code for "${itemName}"?`)) {
            $.ajax({
                url: 'api/generate_qr.php',
                method: 'POST',
                data: {
                    item_id: itemId,
                    item_name: itemName
                },
                dataType: 'json',
                beforeSend: function() {
                    toastr.info('Generating QR code...');
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success('QR Code generated successfully!');
                        // Refresh the modal
                        $('.quick-view-btn[data-item-id="' + itemId + '"]').click();
                    } else {
                        toastr.error(response.message || 'Failed to generate QR code');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('QR generation error:', error);
                    toastr.error('Error generating QR code');
                }
            });
        }
    }

    // ========== NEW VIEW & EDIT MODAL FUNCTIONS ==========
    
    // Function to fetch item data
    function fetchItemData(itemId) {
        return new Promise(function(resolve, reject) {
            const apiPaths = [
                'api/get_item.php',
                'api/items/get.php',
                '../api/get_item.php'
            ];
            
            let currentPathIndex = 0;
            
            function tryNextPath() {
                if (currentPathIndex >= apiPaths.length) {
                    reject(new Error('Could not find API endpoint'));
                    return;
                }
                
                const apiPath = apiPaths[currentPathIndex];
                
                $.ajax({
                    url: apiPath,
                    method: 'GET',
                    data: { id: itemId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data) {
                            resolve(response.data);
                        } else {
                            currentPathIndex++;
                            tryNextPath();
                        }
                    },
                    error: function() {
                        currentPathIndex++;
                        tryNextPath();
                    }
                });
            }
            
            tryNextPath();
        });
    }

    // ========== VIEW ITEM MODAL FUNCTIONS ==========
    function openViewItemModal(itemId) {
        // Close quick actions modal first
        const quickModal = bootstrap.Modal.getInstance(document.getElementById('quickActionsModal'));
        if (quickModal) {
            quickModal.hide();
        }
        
        // Show loading
        $('#viewItemModal .modal-body').html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading item details...</p>
            </div>
        `);
        
        // Fetch and populate data
        fetchItemData(itemId).then(function(data) {
            populateViewItemModal(data);
            
            // Show modal
            const viewModal = new bootstrap.Modal(document.getElementById('viewItemModal'));
            viewModal.show();
        }).catch(function(error) {
            toastr.error('Failed to load item details for viewing');
        });
    }

    function populateViewItemModal(data) {
        // Set modal title
        $('#viewItemModalLabel').html(`<i class="fas fa-eye me-2"></i>View: ${data.item_name}`);
        
        // Item Image
        if (data.image && data.image !== '') {
            $('#viewItemImage img').attr('src', data.image);
            $('#viewItemImage').show();
            $('#viewNoImage').hide();
        } else {
            $('#viewItemImage').hide();
            $('#viewNoImage').show();
        }
        
        // QR Code
        updateViewQRCode(data.qr_code, data.item_name, data.serial_number);
        
        // Basic Information
        $('#viewItemName').text(data.item_name || 'N/A');
        $('#viewSerialNumber').text(data.serial_number || 'N/A');
        $('#viewCategory').text(data.category || 'N/A');
        $('#viewBrand').text(data.brand || 'N/A');
        $('#viewModel').text(data.model || 'N/A');
        $('#viewQuantity').text(data.quantity || 1);
        $('#viewStockLocation').text(data.stock_location || 'N/A');
        $('#viewStorageLocation').text(data.storage_location || 'N/A');
        $('#viewDepartment').text(data.department || 'N/A');
        $('#viewCreatedAt').text(formatDateTime(data.created_at) || 'N/A');
        $('#viewUpdatedAt').text(formatDateTime(data.updated_at) || 'N/A');
        
        // Condition badge
        const conditionBadge = $('#viewCondition');
        conditionBadge.removeClass().addClass('badge');
        switch(data.condition) {
            case 'new':
            case 'excellent':
                conditionBadge.addClass('bg-success').text('Excellent');
                break;
            case 'good':
                conditionBadge.addClass('bg-primary').text('Good');
                break;
            case 'fair':
                conditionBadge.addClass('bg-info').text('Fair');
                break;
            case 'poor':
                conditionBadge.addClass('bg-warning').text('Poor');
                break;
            case 'damaged':
            case 'broken':
                conditionBadge.addClass('bg-danger').text('Damaged');
                break;
            default:
                conditionBadge.addClass('bg-secondary').text(data.condition || 'Unknown');
        }
        
        // Status badge
        const statusBadge = $('#viewItemStatusBadge');
        statusBadge.removeClass().addClass('badge');
        switch(data.status) {
            case 'available':
                statusBadge.addClass('bg-success').text('Available');
                break;
            case 'in_use':
                statusBadge.addClass('bg-primary').text('In Use');
                break;
            case 'maintenance':
                statusBadge.addClass('bg-warning').text('Maintenance');
                break;
            case 'reserved':
                statusBadge.addClass('bg-info').text('Reserved');
                break;
            case 'disposed':
                statusBadge.addClass('bg-danger').text('Disposed');
                break;
            case 'lost':
                statusBadge.addClass('bg-dark').text('Lost');
                break;
            default:
                statusBadge.addClass('bg-secondary').text(data.status || 'Unknown');
        }
        
        // Description
        if (data.description && data.description.trim() !== '') {
            $('#viewDescription').text(data.description);
        } else {
            $('#viewDescription').text('No description available');
        }
        
        // Specifications
        if (data.specifications && data.specifications.trim() !== '') {
            $('#viewSpecifications').text(data.specifications);
            $('#viewSpecificationsSection').show();
        } else {
            $('#viewSpecificationsSection').hide();
        }
        
        // Accessories
        if (data.accessories && data.accessories !== 'None') {
            $('#viewAccessories').html(data.accessories.split(',').map(acc => 
                `<span class="badge bg-info me-1 mb-1">${acc.trim()}</span>`
            ).join(''));
            $('#viewAccessoriesSection').show();
        } else {
            $('#viewAccessoriesSection').hide();
        }
        
        // Notes
        if (data.notes && data.notes.trim() !== '') {
            $('#viewNotes').text(data.notes);
            $('#viewNotesSection').show();
        } else {
            $('#viewNotesSection').hide();
        }
        
        // Set up action buttons
        $('#viewDownloadQRBtn').off('click').on('click', function() {
            if (data.qr_code && data.qr_code !== '') {
                downloadSingleQRCode(data.qr_code, data.item_name, data.serial_number);
            } else {
                toastr.error('No QR code available to download');
            }
        });
        
        $('#viewPrintQRBtn').off('click').on('click', function() {
            if (data.qr_code && data.qr_code !== '') {
                window.open(data.qr_code, '_blank');
            } else {
                toastr.error('No QR code available to print');
            }
        });
        
        $('#viewRefreshBtn').off('click').on('click', function() {
            openViewItemModal(data.id);
        });
        
        $('#viewEditBtn').off('click').on('click', function() {
            const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewItemModal'));
            if (viewModal) {
                viewModal.hide();
            }
            setTimeout(() => {
                openEditItemModal(data.id);
            }, 300);
        });
        
        // Store current item data
        window.currentViewItemData = data;
    }

    function updateViewQRCode(qrCode, itemName, serial) {
        const qrContainer = $('#viewQRCode');
        qrContainer.empty();
        
        if (qrCode && qrCode !== '' && qrCode !== 'pending') {
            qrContainer.html(`
                <img src="${qrCode}" alt="QR Code" 
                     style="width: 150px; height: 150px;" 
                     class="img-fluid border rounded">
                <div class="mt-2 small">${itemName || 'QR Code'}</div>
            `);
        } else if (qrCode === 'pending') {
            qrContainer.html(`
                <div class="text-center">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-2 small">Generating QR Code...</div>
                </div>
            `);
        } else {
            qrContainer.html(`
                <div class="text-center">
                    <i class="fas fa-qrcode fa-3x text-muted mb-2"></i>
                    <div class="small text-muted">No QR Code</div>
                </div>
            `);
        }
    }

    // ========== EDIT ITEM MODAL FUNCTIONS ==========
    function openEditItemModal(itemId) {
        // Show loading
        $('#editItemModal .modal-body').html(`
            <div class="text-center py-5">
                <div class="spinner-border text-warning mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading item for editing...</p>
            </div>
        `);
        
        // Fetch item data
        fetchItemData(itemId).then(function(data) {
            populateEditItemModal(data);
            
            // Load categories, departments, and accessories
            loadEditFormData().then(function() {
                // Show modal
                const editModal = new bootstrap.Modal(document.getElementById('editItemModal'));
                editModal.show();
            });
        }).catch(function(error) {
            toastr.error('Failed to load item details for editing');
        });
    }

    function populateEditItemModal(data) {
        // Set modal title
        $('#editItemModalLabel').html(`<i class="fas fa-edit me-2"></i>Edit: ${data.item_name}`);
        
        // Set hidden ID
        $('#editItemId').val(data.id);
        
        // Current Image
        if (data.image && data.image !== '') {
            $('#editCurrentImage img').attr('src', data.image);
            $('#editCurrentImage').show();
        } else {
            $('#editCurrentImage').html('<div class="text-muted">No image</div>');
        }
        
        // QR Code
        updateEditQRCode(data.qr_code);
        
        // Basic Information
        $('#editItemName').val(data.item_name || '');
        $('#editSerialNumber').val(data.serial_number || '');
        $('#editBrand').val(data.brand || '');
        $('#editModel').val(data.model || '');
        $('#editStockLocation').val(data.stock_location || '');
        $('#editStorageLocation').val(data.storage_location || '');
        $('#editDescription').val(data.description || '');
        $('#editSpecifications').val(data.specifications || '');
        $('#editNotes').val(data.notes || '');
        $('#editTags').val(data.tags || '');
        $('#editQuantity').val(data.quantity || 1);
        
        // Select fields will be populated in loadEditFormData()
        setTimeout(function() {
            $('#editStatus').val(data.status || 'available');
            $('#editCondition').val(data.condition || 'good');
            $('#editCategory').val(data.category || '');
            $('#editDepartment').val(data.department || '');
            
            // Handle accessories if available
            if (data.accessory_ids) {
                data.accessory_ids.forEach(function(accId) {
                    $(`#editAccessories option[value="${accId}"]`).prop('selected', true);
                });
                updateEditSelectedAccessories();
            }
        }, 100);
        
        // Image upload handling
        $('#editChangeImage').off('change').on('change', function() {
            if ($(this).is(':checked')) {
                $('#editImageUploadSection').show();
            } else {
                $('#editImageUploadSection').hide();
            }
        });
        
        $('#editItemImage').off('change').on('change', function(e) {
            const file = e.target.files[0];
            const preview = $('#editImagePreview');
            const previewImg = preview.find('img');
            
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    toastr.error('File size must be less than 5MB');
                    $(this).val('');
                    preview.hide();
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(event) {
                    previewImg.attr('src', event.target.result);
                    preview.show();
                };
                reader.readAsDataURL(file);
            } else {
                preview.hide();
            }
        });
        
        // QR regeneration
        $('#editRegenerateQRBtn').off('click').on('click', function() {
            if (confirm('Regenerate QR code for this item?')) {
                regenerateQRCode(data.id, data.item_name);
            }
        });
        
        // Accessories handling
        $('#editAccessories').off('change').on('change', updateEditSelectedAccessories);
        
        // Form submission
        $('#editItemForm').off('submit').on('submit', function(e) {
            e.preventDefault();
            submitEditItemForm(data.id);
        });
        
        // Delete button
        $('#editDeleteBtn').off('click').on('click', function() {
            if (confirm(`Are you sure you want to delete "${data.item_name}"? This action cannot be undone.`)) {
                deleteItem(data.id, data.item_name);
            }
        });
        
        // Store current item data
        window.currentEditItemData = data;
    }

    function loadEditFormData() {
        return new Promise(function(resolve) {
            // Load categories
            $.ajax({
                url: 'api/get_categories.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.categories) {
                        const categorySelect = $('#editCategory');
                        categorySelect.empty();
                        categorySelect.append('<option value="">Select Category</option>');
                        
                        response.categories.forEach(function(category) {
                            categorySelect.append(
                                `<option value="${category.id}">${category.name}</option>`
                            );
                        });
                    }
                }
            });
            
            // Load departments
            $.ajax({
                url: 'api/get_departments.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.departments) {
                        const deptSelect = $('#editDepartment');
                        deptSelect.empty();
                        deptSelect.append('<option value="">Select Department</option>');
                        
                        response.departments.forEach(function(dept) {
                            deptSelect.append(
                                `<option value="${dept.id}">${dept.name}</option>`
                            );
                        });
                    }
                }
            });
            
            // Load accessories
            $.ajax({
                url: 'api/get_accessories.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.accessories) {
                        const accSelect = $('#editAccessories');
                        accSelect.empty();
                        accSelect.append('<option value="">-- No Accessories --</option>');
                        
                        response.accessories.forEach(function(acc) {
                            accSelect.append(
                                `<option value="${acc.id}">${acc.name} (${acc.available_quantity} available)</option>`
                            );
                        });
                    }
                }
            });
            
            // Resolve after a short delay to ensure selects are populated
            setTimeout(resolve, 500);
        });
    }

    function updateEditSelectedAccessories() {
        const selectedContainer = $('#editSelectedAccessories');
        const selected = $('#editAccessories').find('option:selected');
        
        if (selected.length === 0 || (selected.length === 1 && !selected.val())) {
            selectedContainer.html('<p class="text-muted mb-0">No accessories selected</p>');
            return;
        }
        
        let html = '<div class="d-flex flex-wrap gap-1">';
        selected.each(function() {
            if ($(this).val()) {
                const accessoryName = $(this).text().split(' (')[0];
                html += `
                    <span class="badge bg-info d-flex align-items-center gap-1">
                        ${accessoryName}
                        <button type="button" class="btn-close btn-close-white btn-sm ms-1" 
                                data-accessory-id="${$(this).val()}" 
                                style="font-size: 0.5rem;"></button>
                    </span>
                `;
            }
        });
        html += '</div>';
        selectedContainer.html(html);
        
        // Add click handlers for remove buttons
        $('[data-accessory-id]').off('click').on('click', function(e) {
            e.preventDefault();
            const accessoryId = $(this).data('accessory-id');
            $(`#editAccessories option[value="${accessoryId}"]`).prop('selected', false);
            updateEditSelectedAccessories();
        });
    }

    function updateEditQRCode(qrCode) {
        const qrContainer = $('#editQRCode');
        qrContainer.empty();
        
        if (qrCode && qrCode !== '' && qrCode !== 'pending') {
            qrContainer.html(`
                <img src="${qrCode}" alt="QR Code" 
                     style="width: 120px; height: 120px;" 
                     class="img-fluid border rounded">
                <div class="mt-1 small text-muted">Current QR Code</div>
            `);
        } else if (qrCode === 'pending') {
            qrContainer.html(`
                <div class="text-center">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-1 small">Generating QR Code...</div>
                </div>
            `);
        } else {
            qrContainer.html(`
                <div class="text-center">
                    <i class="fas fa-qrcode fa-2x text-muted mb-1"></i>
                    <div class="small text-muted">No QR Code</div>
                </div>
            `);
        }
    }

    function submitEditItemForm(itemId) {
        // Validate form
        if (!validateEditForm()) {
            return;
        }
        
        // Get form data
        const formData = new FormData();
        formData.append('id', itemId);
        formData.append('item_name', $('#editItemName').val().trim());
        formData.append('serial_number', $('#editSerialNumber').val().trim());
        formData.append('brand', $('#editBrand').val().trim());
        formData.append('model', $('#editModel').val().trim());
        formData.append('status', $('#editStatus').val());
        formData.append('condition', $('#editCondition').val());
        formData.append('category', $('#editCategory').val());
        formData.append('department', $('#editDepartment').val());
        formData.append('stock_location', $('#editStockLocation').val().trim());
        formData.append('storage_location', $('#editStorageLocation').val().trim());
        formData.append('quantity', $('#editQuantity').val());
        formData.append('description', $('#editDescription').val().trim());
        formData.append('specifications', $('#editSpecifications').val().trim());
        formData.append('notes', $('#editNotes').val().trim());
        formData.append('tags', $('#editTags').val().trim());
        
        // Get selected accessories
        const accessories = $('#editAccessories').val();
        if (accessories && accessories.length > 0) {
            accessories.forEach(function(accId) {
                formData.append('accessory_ids[]', accId);
            });
        }
        
        // Handle image
        const changeImage = $('#editChangeImage').is(':checked');
        if (changeImage) {
            const imageFile = $('#editItemImage')[0].files[0];
            if (imageFile) {
                formData.append('image', imageFile);
            } else {
                formData.append('remove_image', '1');
            }
        }
        
        // Show loading
        $('#editSubmitBtn').html(`
            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
            Saving...
        `).prop('disabled', true);
        
        // Submit via AJAX
        $.ajax({
            url: 'api/update_item.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success('Item updated successfully');
                    
                    // Close modal
                    const editModal = bootstrap.Modal.getInstance(document.getElementById('editItemModal'));
                    if (editModal) {
                        editModal.hide();
                    }
                    
                    // Refresh data table if exists
                    if (typeof refreshDataTable === 'function') {
                        refreshDataTable();
                    }
                    
                    // Update any open modals
                    if (window.currentViewItemData && window.currentViewItemData.id === itemId) {
                        openViewItemModal(itemId);
                    }
                } else {
                    toastr.error(response.message || 'Failed to update item');
                }
            },
            error: function(xhr, status, error) {
                toastr.error('Error updating item: ' + error);
            },
            complete: function() {
                $('#editSubmitBtn').html('Save Changes').prop('disabled', false);
            }
        });
    }

    function validateEditForm() {
        // Check required fields
        if (!$('#editItemName').val().trim()) {
            toastr.error('Item name is required');
            $('#editItemName').focus();
            return false;
        }
        
        if (!$('#editCategory').val()) {
            toastr.error('Category is required');
            $('#editCategory').focus();
            return false;
        }
        
        if (!$('#editQuantity').val() || parseInt($('#editQuantity').val()) <= 0) {
            toastr.error('Quantity must be a positive number');
            $('#editQuantity').focus();
            return false;
        }
        
        return true;
    }

    function regenerateQRCode(itemId, itemName) {
        if (!confirm(`Regenerate QR code for "${itemName}"? This will replace the existing QR code.`)) {
            return;
        }
        
        // Show loading
        $('#editRegenerateQRBtn').html(`
            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
            Generating...
        `).prop('disabled', true);
        
        $.ajax({
            url: 'api/generate_qr.php',
            method: 'POST',
            data: { 
                id: itemId,
                item_name: itemName
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success('QR code regenerated successfully');
                    updateEditQRCode(response.qr_code);
                    
                    // Update the item data
                    if (window.currentEditItemData) {
                        window.currentEditItemData.qr_code = response.qr_code;
                    }
                } else {
                    toastr.error(response.message || 'Failed to regenerate QR code');
                }
            },
            error: function() {
                toastr.error('Error regenerating QR code');
            },
            complete: function() {
                $('#editRegenerateQRBtn').html('Regenerate QR Code').prop('disabled', false);
            }
        });
    }

    function deleteItem(itemId, itemName) {
        if (!confirm(`Are you sure you want to delete "${itemName}"? This action cannot be undone.`)) {
            return;
        }
        
        // Show loading
        $('#editDeleteBtn').html(`
            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
            Deleting...
        `).prop('disabled', true);
        
        $.ajax({
            url: 'api/delete_item.php',
            method: 'POST',
            data: { 
                id: itemId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success('Item deleted successfully');
                    
                    // Close all modals
                    const modals = ['editItemModal', 'viewItemModal', 'quickActionsModal'];
                    modals.forEach(function(modalId) {
                        const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
                        if (modal) {
                            modal.hide();
                        }
                    });
                    
                    // Refresh data table
                    if (typeof refreshDataTable === 'function') {
                        refreshDataTable();
                    }
                    
                    // Clear stored data
                    delete window.currentItemData;
                    delete window.currentViewItemData;
                    delete window.currentEditItemData;
                } else {
                    toastr.error(response.message || 'Failed to delete item');
                }
            },
            error: function() {
                toastr.error('Error deleting item');
            },
            complete: function() {
                $('#editDeleteBtn').html('Delete Item').prop('disabled', false);
            }
        });
    }

    // ========== HELPER FUNCTIONS ==========
    function formatDateTime(dateTimeStr) {
        if (!dateTimeStr) return 'N/A';
        
        const date = new Date(dateTimeStr);
        if (isNaN(date)) return dateTimeStr;
        
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // ========== END OF GLOBAL FUNCTIONS ==========
</script>