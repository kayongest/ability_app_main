<?php
// dashboard.php with Sidebar Layout
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include bootstrap first
require_once 'bootstrap.php';

// Check if redirect function exists before using it
if (!function_exists('redirect')) {
    function redirect($url, $statusCode = 303)
    {
        header('Location: ' . $url, true, $statusCode);
        exit();
    }
}

// Check authentication
if (!isLoggedIn()) {
    $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
    redirect('login.php');
}

// Determine current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Set page variables
$pageTitle = "Dashboard - aBility";
$showBreadcrumb = true;
$breadcrumbItems = [
    'Dashboard' => ''
];

// Include sidebar header instead of regular header
require_once 'views/partials/header_sidebar.php';

// Include functions
require_once 'includes/functions.php';

require_once 'includes/db_connect.php';

// Helper function for category colors
function getCategoryColor($index) {
    $colors = [
        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', 
        '#e74a3b', '#6f42c1', '#fd7e14', '#20c9a6'
    ];
    return $colors[$index % count($colors)];
}

// Get database connection
$db = new Database();
$conn = $db->getConnection();

// Get dashboard stats
$stats = getDashboardStats($conn);

// Get detailed category statistics
$category_stats = $conn->query("
    SELECT 
        COALESCE(category, 'Uncategorized') as category_name,
        COUNT(*) as item_count,
        SUM(quantity) as total_quantity,
        ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM items)), 2) as percentage
    FROM items
    GROUP BY category
    ORDER BY item_count DESC
")->fetch_all(MYSQLI_ASSOC);

// Get aggregated item totals
$itemTotals = $conn->query("
    SELECT 
        item_name,
        category,
        COUNT(*) as serial_count,
        SUM(quantity) as total_quantity,
        GROUP_CONCAT(DISTINCT serial_number ORDER BY serial_number SEPARATOR ', ') as serials
    FROM items 
    GROUP BY item_name, category
    HAVING COUNT(*) > 1 OR SUM(quantity) > 1
    ORDER BY total_quantity DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Get recent items
$recentItems = getRecentItems($conn, 100);
?>

<!-- Dashboard Content -->
<div class="dashboard-content">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard Overview
            </h1>
            <p class="text-muted mb-0">Welcome back! Here's what's happening with your equipment.</p>
        </div>
        <div class="btn-group">
            <a href="items/create.php" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                <i class="fas fa-plus me-1"></i> Add Equipment
            </a>
            <a href="scan.php" class="btn btn-success">
                <i class="fas fa-qrcode me-1"></i> Scan QR
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2 text-white" style="background-color: #44444E; border-radius: 5px;">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-white text-uppercase mb-1">
                                Total Equipment
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['total_items']; ?>
                            </div>
                            <div class="mt-2 mb-0 text-muted text-xs">
                                <span class="text-success mr-2"><i class="fas fa-boxes"></i> Items</span>
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
            <div class="card border-left-success shadow h-100 py-2 text-white" style="background-color: #715A5A; border-radius: 5px;">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-white text-uppercase mb-1">
                                Available
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['available']; ?>
                            </div>
                            <div class="mt-2 mb-0 text-muted text-xs">
                                <span class="text-success mr-2">Ready for use</span>
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
            <div class="card border-left-warning shadow h-100 py-2 text-white" style="background-color: #234C6A; border-radius: 5px;">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-white text-uppercase mb-1">
                                In Use
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['in_use']; ?>
                            </div>
                            <div class="mt-2 mb-0 text-muted text-xs">
                                <span class="text-warning mr-2">Currently assigned</span>
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
            <div class="card border-left-info shadow h-100 py-2 text-white" style="background-color: #456882; border-radius: 5px;">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-white text-uppercase mb-1">
                                Categories
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['categories']; ?>
                            </div>
                            <div class="mt-2 mb-0 text-muted text-xs">
                                <span class="text-info mr-2">Active categories</span>
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

    <!-- Charts and Statistics Row -->
    <div class="row mb-4">
        <!-- Status Chart -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center text-white" style="background-color: rgba(35, 54, 67, 1);">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-chart-pie me-2"></i>Equipment Status Distribution
                    </h6>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" onclick="updateChart('all')">All Status</a></li>
                            <li><a class="dropdown-item" href="#" onclick="updateChart('available')">Available Only</a></li>
                            <li><a class="dropdown-item" href="#" onclick="updateChart('in_use')">In Use Only</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height: 300px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Hover over chart segments for details
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Categories -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center text-white" style="background-color: rgba(35, 54, 67, 1);">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-list-alt me-2"></i>Top Categories
                    </h6>
                    <span class="badge bg-light text-dark"><?php echo count($category_stats); ?> total</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless mb-0">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th class="text-end">Count</th>
                                    <th class="text-end">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($category_stats)): ?>
                                    <?php foreach (array_slice($category_stats, 0, 8) as $index => $cat): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge badge-circle me-2" style="background-color: <?php echo getCategoryColor($index); ?>; width: 12px; height: 12px; border-radius: 50%;"></span>
                                                    <span><?php echo htmlspecialchars($cat['category_name']); ?></span>
                                                </div>
                                            </td>
                                            <td class="text-end"><?php echo $cat['item_count']; ?></td>
                                            <td class="text-end">
                                                <span class="badge bg-light text-dark"><?php echo $cat['percentage']; ?>%</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (count($category_stats) > 8): ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-2">
                                                <a href="reports.php" class="text-decoration-none">
                                                    <i class="fas fa-ellipsis-h me-1"></i>
                                                    View all <?php echo count($category_stats); ?> categories
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4">
                                            <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                            <p class="text-muted mb-0">No category data available</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Equipment Table -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center text-white" style="background-color: rgba(35, 54, 67, 1);">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-list me-2"></i>Recent Equipment
                    </h6>
                    <div>
                        <button class="btn btn-sm btn-light me-2" id="exportTableBtn" title="Export Data">
                            <i class="fas fa-download"></i>
                        </button>
                        <button class="btn btn-sm btn-light" id="refreshItemsBtn" title="Refresh">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="recentItemsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th width="3%">ID</th>
                                    <th>Item Name</th>
                                    <th>Serial #</th>
                                    <th>Category</th>
                                    <th>Brand</th>
                                    <th>Model</th>
                                    <th>Department</th>
                                    <th>Location</th>
                                    <th>Condition</th>
                                    <th>Status</th>
                                    <th width="15%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentItems)): ?>
                                    <?php foreach ($recentItems as $item): ?>
                                        <tr>
                                            <td><?php echo $item['id']; ?></td>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                                <?php if (!empty($item['description'])): ?>
                                                    <small class="text-muted"><?php echo substr(htmlspecialchars($item['description']), 0, 50); ?>...</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <code class="text-primary"><?php echo htmlspecialchars($item['serial_number']); ?></code>
                                            </td>
                                            <td><?php echo getCategoryBadge($item['category'] ?? 'Uncategorized'); ?></td>
                                            <td>
                                                <?php echo !empty($item['brand']) ? htmlspecialchars($item['brand']) : '<span class="text-muted">N/A</span>'; ?>
                                            </td>
                                            <td>
                                                <?php echo !empty($item['model']) ? htmlspecialchars($item['model']) : '<span class="text-muted">N/A</span>'; ?>
                                            </td>
                                            <td><?php echo !empty($item['department']) ? htmlspecialchars($item['department']) : '<span class="text-muted">N/A</span>'; ?></td>
                                            <td><?php echo !empty($item['stock_location']) ? htmlspecialchars($item['stock_location']) : '<span class="text-muted">N/A</span>'; ?></td>
                                            <td><?php echo getConditionBadge($item['condition'] ?? 'good'); ?></td>
                                            <td><?php echo getStatusBadge($item['status'] ?? 'available'); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="items/view.php?id=<?php echo $item['id']; ?>"
                                                        class="btn btn-info" title="View Details" data-bs-toggle="tooltip">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="items/edit.php?id=<?php echo $item['id']; ?>"
                                                        class="btn btn-warning" title="Edit" data-bs-toggle="tooltip">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if (!empty($item['qr_code'])): ?>
                                                        <a href="<?php echo htmlspecialchars($item['qr_code']); ?>"
                                                            class="btn btn-success" title="View QR Code" target="_blank" data-bs-toggle="tooltip">
                                                            <i class="fas fa-qrcode"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <button class="btn btn-outline-success generate-qr-btn"
                                                            data-item-id="<?php echo $item['id']; ?>"
                                                            data-item-name="<?php echo htmlspecialchars($item['item_name']); ?>"
                                                            title="Generate QR Code" data-bs-toggle="tooltip">
                                                            <i class="fas fa-qrcode"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="11" class="text-center py-4">
                                            <i class="fas fa-box-open fa-2x text-muted mb-3"></i>
                                            <h5>No Equipment Found</h5>
                                            <p class="text-muted">Add your first equipment item to get started</p>
                                            <a href="items/create.php" class="btn btn-primary mt-2">
                                                <i class="fas fa-plus me-1"></i> Add Equipment
                                            </a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addItemModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Add New Equipment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addItemForm" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="alert alert-info d-flex align-items-center mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <div>Fields marked with <span class="text-danger">*</span> are required</div>
                    </div>

                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <!-- Item Name -->
                            <div class="mb-3">
                                <label for="item_name" class="form-label">
                                    Equipment Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="item_name" name="item_name" required
                                    placeholder="e.g., Mini Converter SDI to HDMI">
                                <div class="form-text">Descriptive name for identification</div>
                            </div>

                            <!-- Serial Number -->
                            <div class="mb-3">
                                <label for="serial_number" class="form-label">
                                    Serial Number <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="serial_number" name="serial_number" required
                                        placeholder="Enter unique serial number">
                                    <button class="btn btn-outline-secondary" type="button" id="generateSerialBtn" title="Generate Serial">
                                        <i class="fas fa-bolt"></i> Auto
                                    </button>
                                </div>
                                <div class="form-text">Each serial number must be unique</div>
                            </div>

                            <!-- Quantity -->
                            <div class="mb-3">
                                <label for="quantity" class="form-label">Quantity</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1">
                                    <span class="input-group-text">units</span>
                                </div>
                                <div class="form-text">Number of units for this serial number</div>
                            </div>

                            <!-- Brand -->
                            <div class="mb-3">
                                <label for="brand" class="form-label">Brand</label>
                                <input type="text" class="form-control" id="brand" name="brand"
                                    placeholder="e.g., Blackmagic Design">
                                <div class="form-text">Manufacturer or brand name</div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <!-- Model -->
                            <div class="mb-3">
                                <label for="model" class="form-label">Model</label>
                                <input type="text" class="form-control" id="model" name="model"
                                    placeholder="e.g., Mini Converter SDI to HDMI">
                                <div class="form-text">Specific model number or name</div>
                            </div>

                            <!-- Category -->
                            <div class="mb-3">
                                <label for="category" class="form-label required">Category</label>
                                <div class="input-group">
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
                            </div>

                            <!-- Department -->
                            <div class="mb-3">
                                <label for="department" class="form-label">Department</label>
                                <div class="input-group">
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
                        </div>
                    </div>

                    <!-- Second Row -->
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Location -->
                            <div class="mb-3">
                                <label for="stock_location" class="form-label">Location</label>
                                <div class="input-group">
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
                                <div class="form-text">Where the equipment is stored</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <!-- Image Upload -->
                            <div class="mb-3">
                                <label for="item_image" class="form-label">Equipment Image</label>
                                <input type="file" class="form-control" id="item_image" name="item_image" accept="image/*">
                                <div class="form-text">JPG, PNG, GIF, WebP (Max 5MB)</div>
                                <div class="mt-2" id="imagePreview" style="display: none;">
                                    <img src="" alt="Preview" class="img-thumbnail" style="max-height: 120px;">
                                    <button type="button" class="btn btn-sm btn-danger mt-1" onclick="clearImagePreview()">
                                        <i class="fas fa-times"></i> Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Additional details about this equipment"></textarea>
                    </div>

                    <!-- QR Code Generator -->
                    <div class="card border-info mb-3">
                        <div class="card-header bg-info text-white py-2">
                            <i class="fas fa-qrcode me-2"></i>QR Code Settings
                        </div>
                        <div class="card-body py-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="generate_qr" name="generate_qr" checked>
                                <label class="form-check-label" for="generate_qr">
                                    Generate QR Code for this equipment
                                </label>
                            </div>
                            <div class="form-text">QR codes make inventory scanning easier</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <button type="reset" class="btn btn-outline-secondary" id="resetBtn">
                        <i class="fas fa-redo me-1"></i> Reset
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save me-1"></i> Save Equipment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if (isset($_SESSION['success'])): ?>
    <script>
        $(document).ready(function() {
            toastr.success('<?php echo addslashes($_SESSION['success']); ?>');
        });
    </script>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <script>
        $(document).ready(function() {
            toastr.error('<?php echo addslashes($_SESSION['error']); ?>');
        });
    </script>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- Load DataTables CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">

<!-- Dashboard JavaScript -->
<script>
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#recentItemsTable').DataTable({
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
        order: [[0, 'desc']],
        responsive: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
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
        columnDefs: [
            { orderable: false, targets: [10] },
            { responsivePriority: 1, targets: [1, 2, 3] },
            { responsivePriority: 2, targets: [0, 9] },
            { responsivePriority: 3, targets: [4, 5, 6, 7, 8] }
        ]
    });

    // Initialize status chart
    const ctx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Available', 'In Use', 'Maintenance', 'Reserved', 'Disposed'],
            datasets: [{
                data: [
                    <?php echo $stats['available']; ?>,
                    <?php echo $stats['in_use']; ?>,
                    <?php echo $stats['maintenance']; ?>,
                    <?php echo $stats['reserved'] ?? 0; ?>,
                    <?php echo $stats['disposed'] ?? 0; ?>
                ],
                backgroundColor: [
                    '#28a745',
                    '#007bff',
                    '#ffc107',
                    '#6f42c1',
                    '#dc3545'
                ],
                borderWidth: 1,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.raw + ' items';
                            return label;
                        }
                    }
                }
            }
        }
    });

    // Export table functionality
    $('#exportTableBtn').click(function() {
        const data = table.buttons.exportData();
        const csv = data.join('\n');
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'equipment_' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    });

    // Refresh button
    $('#refreshItemsBtn').click(function() {
        const $btn = $(this);
        const originalHtml = $btn.html();
        
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        setTimeout(() => {
            table.ajax.reload(null, false);
            $btn.prop('disabled', false).html(originalHtml);
            toastr.success('Data refreshed successfully');
        }, 500);
    });

    // Generate QR code button
    $('.generate-qr-btn').click(function() {
        const itemId = $(this).data('item-id');
        const itemName = $(this).data('item-name');
        
        toastr.info('Generating QR code for ' + itemName + '...');
        
        $.ajax({
            url: 'api/generate_qr.php',
            method: 'POST',
            data: { item_id: itemId },
            success: function(response) {
                if (response.success) {
                    toastr.success('QR code generated successfully');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    toastr.error(response.message || 'Failed to generate QR code');
                }
            },
            error: function() {
                toastr.error('Failed to generate QR code');
            }
        });
    });

    // Add Item Modal functionality
    $('#generateSerialBtn').click(function() {
        const name = $('#item_name').val().trim();
        const prefix = name ? name.substring(0, 3).toUpperCase().replace(/\s/g, '') : 'EQP';
        const timestamp = Date.now().toString().substr(-8);
        const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
        $('#serial_number').val(`${prefix}-${timestamp}-${random}`);
    });

    $('#item_name').on('blur', function() {
        if ($(this).val().trim() && !$('#serial_number').val()) {
            $('#generateSerialBtn').click();
        }
    });

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

    $('#addItemForm').on('submit', function(e) {
        e.preventDefault();

        const form = this;
        const formData = new FormData(form);
        const submitBtn = $('#submitBtn');
        const originalBtnText = submitBtn.html();

        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');

        $.ajax({
            url: 'api/items/create.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    
                    // Close modal and reload
                    setTimeout(() => {
                        $('#addItemModal').modal('hide');
                        form.reset();
                        $('#imagePreview').hide();
                        window.location.reload();
                    }, 1500);
                    
                } else {
                    toastr.error(response.message || 'Failed to save');
                    submitBtn.prop('disabled', false).html(originalBtnText);
                }
            },
            error: function(xhr, status, error) {
                let errorMsg = 'Error: ' + (xhr.responseText || error);
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMsg = response.message || errorMsg;
                } catch (e) {}
                
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
        $('#submitBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save Equipment');
    });

    // Keyboard shortcuts
    $(document).keydown(function(e) {
        // Ctrl+Enter to submit form
        if (e.ctrlKey && e.key === 'Enter' && $('#addItemModal').hasClass('show')) {
            $('#submitBtn').click();
        }
        
        // Escape to close modal
        if (e.key === 'Escape' && $('#addItemModal').hasClass('show')) {
            $('#addItemModal').modal('hide');
        }
        
        // Ctrl+R to refresh
        if (e.ctrlKey && e.key === 'r') {
            e.preventDefault();
            $('#refreshItemsBtn').click();
        }
    });

    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Sidebar toggle for mobile
    $('#sidebarToggle').click(function() {
        $('body').toggleClass('sidebar-collapsed');
        localStorage.setItem('sidebar-collapsed', $('body').hasClass('sidebar-collapsed'));
    });

    // Check saved sidebar state
    if (localStorage.getItem('sidebar-collapsed') === 'true') {
        $('body').addClass('sidebar-collapsed');
    }
});

function clearImagePreview() {
    $('#item_image').val('');
    $('#imagePreview').hide();
}

function updateChart(filter) {
    // This function would typically make an AJAX call to get filtered data
    // For now, we'll just show a message
    toastr.info('Filtering chart for ' + filter + ' status');
}

// Helper function for category colors (add this to your functions.php or define here)
function getCategoryColor(index) {
    const colors = [
        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', 
        '#e74a3b', '#6f42c1', '#fd7e14', '#20c9a6'
    ];
    return colors[index % colors.length];
}
</script>

<?php
// Close database connection
$db->close();

// Include sidebar footer
require_once 'views/partials/footer_sidebar.php';
?>