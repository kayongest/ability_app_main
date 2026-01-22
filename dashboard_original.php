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
    // Dashboard App - Server-side Version (No AJAX)
    window.dashboardApp = (function() {
        'use strict';

        let dataTable = null;
        let statusChart = null;
        let refreshTimeout = null;

        return {
            // Initialize dashboard
            init: function() {
                console.log('Initializing dashboard...');

                // Initialize with a small delay
                setTimeout(() => {
                    this.initDataTable();
                    this.initStatusChart();
                    this.bindEvents();
                    console.log('Dashboard initialized successfully');
                }, 100);
            },

            // Initialize DataTable (server-side version)
            initDataTable: function() {
                const $table = $('#recentItemsTable');

                if (!$table.length) {
                    console.warn('Table #recentItemsTable not found');
                    return;
                }

                // Check if table has data
                const hasData = $table.find('tbody tr').length > 1 ||
                    ($table.find('tbody tr').length === 1 &&
                        !$table.find('tbody tr td').text().includes('No equipment'));

                if (!hasData) {
                    console.log('Table has no data, skipping DataTable initialization');
                    return;
                }

                // Destroy existing instance if any
                if ($.fn.DataTable.isDataTable($table)) {
                    dataTable.destroy();
                    $table.removeAttr('style');
                }

                try {
                    dataTable = $table.DataTable({
                        pageLength: 5,
                        lengthChange: false,
                        order: [
                            [0, 'desc']
                        ],
                        columnDefs: [{
                                orderable: false,
                                targets: [6]
                            } // Actions column not sortable
                        ],
                        language: {
                            emptyTable: "No equipment found",
                            info: "Showing _START_ to _END_ of _TOTAL_ items",
                            infoEmpty: "Showing 0 to 0 of 0 items",
                            infoFiltered: "(filtered from _MAX_ total items)",
                            search: "Search:",
                            paginate: {
                                first: "First",
                                last: "Last",
                                next: "Next",
                                previous: "Previous"
                            }
                        },
                        dom: '<"top"f>rt<"bottom"lip><"clear">',
                        initComplete: function() {
                            console.log('DataTable initialized successfully');
                        }
                    });
                } catch (error) {
                    console.error('DataTable initialization failed:', error);
                }
            },

            // Initialize status chart
            initStatusChart: function() {
                const canvas = document.getElementById('statusChart');
                if (!canvas) {
                    console.warn('Status chart canvas not found');
                    return;
                }

                // Destroy existing chart
                if (statusChart) {
                    statusChart.destroy();
                }

                try {
                    statusChart = new Chart(canvas, {
                        type: 'doughnut',
                        data: {
                            labels: ['Available', 'In Use', 'Maintenance', 'Reserved', 'Disposed', 'Lost'],
                            datasets: [{
                                data: [
                                    <?php echo $stats['available']; ?>,
                                    <?php echo $stats['in_use']; ?>,
                                    <?php echo $stats['maintenance']; ?>,
                                    <?php echo $stats['reserved'] ?? 0; ?>,
                                    <?php echo $stats['disposed'] ?? 0; ?>,
                                    <?php echo $stats['lost'] ?? 0; ?>
                                ],
                                backgroundColor: [
                                    '#28a745', '#007bff', '#ffc107',
                                    '#17a2b8', '#dc3545', '#343a40'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 10,
                                        usePointStyle: true,
                                        boxWidth: 8
                                    }
                                }
                            }
                        }
                    });
                } catch (error) {
                    console.error('Chart initialization failed:', error);
                }
            },

            // Bind event listeners
            bindEvents: function() {
                const $refreshBtn = $('#refreshItemsBtn');

                if ($refreshBtn.length) {
                    // Remove any existing handlers
                    $refreshBtn.off('click.dashboard');

                    // Add click handler
                    $refreshBtn.on('click.dashboard', () => {
                        this.handleRefresh();
                    });
                }

                // Export buttons
                $('.export-btn').off('click.dashboard').on('click.dashboard', function(e) {
                    e.preventDefault();
                    const format = $(this).data('format');
                    if (format) {
                        window.location.href = `export.php?format=${format}`;
                    }
                });
            },

            // Handle refresh button click
            handleRefresh: function() {
                const $btn = $('#refreshItemsBtn');
                const originalHtml = $btn.html();

                // Clear any existing timeout
                if (refreshTimeout) {
                    clearTimeout(refreshTimeout);
                }

                // Show loading state
                $btn.prop('disabled', true)
                    .html('<i class="fas fa-spinner fa-spin"></i>');

                // Simple page reload (for server-side rendering)
                refreshTimeout = setTimeout(() => {
                    window.location.reload();
                }, 500);
            },

            // Clean up
            destroy: function() {
                if (refreshTimeout) {
                    clearTimeout(refreshTimeout);
                }

                if (dataTable) {
                    try {
                        dataTable.destroy();
                    } catch (error) {
                        console.warn('Error destroying DataTable:', error);
                    }
                    dataTable = null;
                }

                if (statusChart) {
                    try {
                        statusChart.destroy();
                    } catch (error) {
                        console.warn('Error destroying chart:', error);
                    }
                    statusChart = null;
                }

                // Remove event listeners
                $('#refreshItemsBtn').off('click.dashboard');
                $('.export-btn').off('click.dashboard');
            }
        };
    })();

    // Initialize when DOM is ready
    $(document).ready(function() {
        // Check if we're on the dashboard
        if ($('#recentItemsTable').length || $('#statusChart').length) {
            // Initialize with a delay to ensure everything is loaded
            setTimeout(() => {
                try {
                    window.dashboardApp.init();
                } catch (error) {
                    console.error('Dashboard initialization error:', error);
                }
            }, 200);
        }
    });

    // Add Item Modal Functionality
    $(document).ready(function() {
        // Generate serial number
        $('#generateSerialBtn').on('click', function() {
            const name = $('#item_name').val().trim();
            const prefix = name ? name.substring(0, 3).toUpperCase().replace(/\s/g, '') : 'EQP';
            const timestamp = Date.now().toString().substr(-8);
            const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
            $('#serial_number').val(`${prefix}-${timestamp}-${random}`);
        });

        // Auto-generate serial when name is entered
        $('#item_name').on('blur', function() {
            if ($(this).val().trim() && !$('#serial_number').val()) {
                $('#generateSerialBtn').click();
            }
        });

        // Image preview
        $('#item_image').on('change', function(e) {
            const preview = $('#imagePreview');
            const img = preview.find('img');

            if (this.files && this.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    img.attr('src', e.target.result);
                    preview.show();
                }

                reader.readAsDataURL(this.files[0]);
            } else {
                preview.hide();
                img.attr('src', '');
            }
        });

        // Form submission with better error handling
        $('#addItemForm').on('submit', function(e) {
            e.preventDefault();

            const form = this;
            const formData = new FormData(form);
            const submitBtn = $('#submitBtn');
            const originalBtnText = submitBtn.html();

            // Show loading state
            submitBtn.prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');

            // Clear previous errors
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            console.log('Submitting to api/items/create.php...');

            const BASE_URL = '<?php echo BASE_URL; ?>';

            // Submit via AJAX
            $.ajax({
                url: 'api/items/create.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    console.log('API Response:', response);

                    if (response.success) {
                        // Show success message
                        toastr.success(response.message);

                        // Close modal after delay
                        setTimeout(() => {
                            $('#addItemModal').modal('hide');

                            // Reset form
                            form.reset();
                            $('#imagePreview').hide();

                            // Refresh dashboard
                            setTimeout(() => {
                                location.reload();
                            }, 500);

                        }, 1000);

                    } else {
                        // Show error message
                        toastr.error(response.message || 'Failed to save equipment');

                        // Display field errors
                        if (response.errors) {
                            Object.keys(response.errors).forEach(field => {
                                const input = $(`[name="${field}"]`);
                                const error = response.errors[field];

                                input.addClass('is-invalid');
                                input.after(`<div class="invalid-feedback">${error}</div>`);
                            });
                        }

                        submitBtn.prop('disabled', false).html(originalBtnText);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error Details:');
                    console.error('Status:', status);
                    console.error('Error:', error);
                    console.error('Response Text:', xhr.responseText.substring(0, 200) + '...');

                    let errorMsg = 'Network error. Please try again.';

                    // Try to parse the response as JSON
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            errorMsg = response.message;
                        }
                    } catch (e) {
                        // If not JSON, show generic error
                        console.error('Response is not valid JSON');
                    }

                    toastr.error(errorMsg);
                    submitBtn.prop('disabled', false).html(originalBtnText);
                }
            });
        });

        // Reset form when modal is closed
        $('#addItemModal').on('hidden.bs.modal', function() {
            const form = document.getElementById('addItemForm');
            form.reset();
            $('#imagePreview').hide();
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();
            $('#submitBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save Equipment');
        });
    });

    // Clean up on page unload
    window.addEventListener('beforeunload', function() {
        if (window.dashboardApp && typeof window.dashboardApp.destroy === 'function') {
            window.dashboardApp.destroy();
        }
    });
</script>