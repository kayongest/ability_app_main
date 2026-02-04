<?php
// dashboard.php - Complete fixed version
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Try to include bootstrap.php, but handle if it doesn't exist
if (file_exists('includes/bootstrap.php')) {
    require_once 'includes/bootstrap.php';
} else {
    // If bootstrap.php doesn't exist, include required files directly
    require_once 'includes/database_fix.php';
    require_once 'includes/functions.php';

    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Simple login check function
    function isLoggedIn()
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    // Simple redirect function
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

// Set page variables
$current_page = basename(__FILE__);
$pageTitle = "Dashboard - aBility";
$showBreadcrumb = true;
$breadcrumbItems = [
    'Dashboard' => ''
];

// Include database connection fix - MUST BE BEFORE USING $conn
require_once 'includes/database_fix.php';
require_once 'includes/functions.php';

// Get database connection
try {
    $db = new DatabaseFix();
    $conn = $db->getConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Helper function to get accessories list as string
function getAccessoriesList($item_id)
{
    global $conn;
    $accessories = [];
    try {
        $stmt = $conn->prepare("
            SELECT a.name 
            FROM accessories a 
            INNER JOIN item_accessories ia ON a.id = ia.accessory_id 
            WHERE ia.item_id = ?
        ");
        if ($stmt) {
            $stmt->bind_param("i", $item_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $accessories[] = $row['name'];
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log("Error getting accessories list: " . $e->getMessage());
    }
    return !empty($accessories) ? implode(', ', $accessories) : 'None';
}

// NOW you can use $conn - Get all unique item names for datalist
$allItems = [];
try {
    $itemsResult = $conn->query("
        SELECT DISTINCT item_name 
        FROM items 
        WHERE status NOT IN ('disposed', 'lost')
        ORDER BY item_name
        LIMIT 100
    ");

    if ($itemsResult) {
        $allItems = $itemsResult->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    error_log("Error getting items for datalist: " . $e->getMessage());
}

// ADD THIS SECTION - Get specific item count
$specific_item_count = 0;
$specific_item_name = "Mini Converter - Optical Fiber 12G";

try {
    $specificStmt = $conn->prepare("
        SELECT SUM(quantity) as total_quantity 
        FROM items 
        WHERE item_name LIKE ? 
        AND status != 'disposed' 
        AND status != 'lost'
    ");

    if ($specificStmt) {
        $searchTerm = "%" . $specific_item_name . "%";
        $specificStmt->bind_param("s", $searchTerm);
        $specificStmt->execute();
        $specificResult = $specificStmt->get_result();

        if ($row = $specificResult->fetch_assoc()) {
            $specific_item_count = $row['total_quantity'] ?? 0;
        }

        $specificStmt->close();
    }
} catch (Exception $e) {
    error_log("Error getting specific item count: " . $e->getMessage());
}

// Get dashboard stats
$stats = getDashboardStats($conn);

// Get detailed category statistics
$category_stats = [];
try {
    $result = $conn->query("
        SELECT 
            COALESCE(category, 'Uncategorized') as category_name,
            COUNT(*) as item_count,
            SUM(quantity) as total_quantity,
            ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM items)), 2) as percentage
        FROM items
        GROUP BY category
        ORDER BY item_count DESC
    ");

    if ($result) {
        $category_stats = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    error_log("Error getting category stats: " . $e->getMessage());
}

// Get recent items - WITH ERROR HANDLING
// Get recent items - WITH ERROR HANDLING
$recentItems = [];
try {
    $recentItems = getRecentItems($conn, 100);
    error_log("Got " . count($recentItems) . " recent items");

    if (empty($recentItems)) {
        error_log("No recent items found - table might be empty or error occurred");
    }
} catch (Exception $e) {
    error_log("Error in getRecentItems: " . $e->getMessage());
    $recentItems = [];
}

// Get accessories for dropdown
$accessories = [];
try {
    $accResult = $conn->query("
        SELECT id, name, description, total_quantity, available_quantity, is_active 
        FROM accessories 
        WHERE is_active = 1 
        ORDER BY name
    ");

    if ($accResult) {
        $accessories = $accResult->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    error_log("Error fetching accessories: " . $e->getMessage());
}

// Include header (MUST BE AFTER all PHP variables are set)
require_once 'views/partials/header.php';
require_once 'assets/css/chart.css';
?>

<style>
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 0;
        margin-left: 2px;
        border-radius: 3px;
        border: 1px solid #ddd;
        background-color: #f8f9fa;
        color: #333;
    }

    .active>.page-link,
    .page-link.active {
        background-color: #2c6792 !important;
        border-color: #234C6A !important;
        color: white !important;
    }

    /* Quick Actions Modal Styles */
    .quick-view-btn {
        transition: all 0.3s ease;
        background: linear-gradient(135deg, #233643 0%, #2c4760 100%);
        border: none;
        color: white;
        padding: 0.25rem 0.75rem;
    }

    .quick-view-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(35, 54, 67, 0.3);
        background: linear-gradient(135deg, #2c4760 0%, #233643 100%);
    }

    .quick-view-btn:active {
        transform: translateY(0);
    }

    #digitalTime {
        letter-spacing: 1px;
        text-shadow: 0 1px 2px rgba(30, 62, 86, 0.77);
    }

    .bg-primary.rounded-circle {
        background-color: rgba(35, 54, 67, 1) !important;
        box-shadow: 0 2px 4px rgba(35, 54, 67, 0.3);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .border-end {
            border-right: none !important;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
        }

        .pe-3 {
            padding-right: 0 !important;
        }

        .ps-3 {
            padding-left: 0 !important;
        }

        #digitalTime {
            font-size: 1.5rem !important;
        }

        #quickActionsModal .row.g-0>.col-md-8,
        #quickActionsModal .row.g-0>.col-md-4 {
            border: none !important;
        }
    }

    /* Fix modal backdrop and blur issues */
    .modal-backdrop {
        z-index: 1040 !important;
    }

    .modal {
        z-index: 1050 !important;
    }

    body.modal-open {
        overflow: auto !important;
        padding-right: 0 !important;
    }

    /* Fix for blurry content after modal close */
    .modal.fade.show {
        opacity: 1;
        background-color: rgba(0, 0, 0, 0.5);
    }

    /* Clear modal backdrop on page load */
    .modal-backdrop {
        display: none;
    }

    /* Fix for DataTable in modal */
    .dataTables_wrapper {
        position: relative;
        clear: both;
    }

    /* Fix for quick actions modal */
    #quickActionsModal .modal-content {
        border-radius: 0.5rem;
    }

    #quickActionsModal .modal-body {
        max-height: 70vh;
        overflow-y: auto;
    }
</style>

<!-- Dashboard Content -->
<div class="container-fluid">
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4 text-white">
            <div class="card border-left-primary shadow h-100 py-2 text-white" style="background-color: #44444E; border-radius: 5px;">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-white text-uppercase mb-1">
                                Total Equipment
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['total_items'] ?? 0; ?>
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
                                <?php echo $stats['available'] ?? 0; ?>
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
                                <?php echo $stats['in_use'] ?? 0; ?>
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
                                <?php echo $stats['categories'] ?? 0; ?>
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


    <style>
        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-available {
            background-color: rgba(75, 192, 192, 0.15);
            color: #2e8b57;
            border: 1px solid rgba(75, 192, 192, 0.3);
        }

        .status-in_use {
            background-color: rgba(54, 162, 235, 0.15);
            color: #1e6b8a;
            border: 1px solid rgba(54, 162, 235, 0.3);
        }

        .status-maintenance {
            background-color: rgba(255, 206, 86, 0.15);
            color: #b8860b;
            border: 1px solid rgba(255, 206, 86, 0.3);
        }

        .status-reserved {
            background-color: rgba(153, 102, 255, 0.15);
            color: #6a5acd;
            border: 1px solid rgba(153, 102, 255, 0.3);
        }

        .status-disposed {
            background-color: rgba(255, 99, 132, 0.15);
            color: #780404;
            border: 1px solid rgba(199, 54, 85, 0.3);
        }

        .status-lost {
            background-color: rgba(128, 128, 128, 0.15);
            color: #696969;
            border: 1px solid rgba(128, 128, 128, 0.3);
        }
    </style>

    <!-- Charts and Categories -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card mb-5 shadow">
                <div class="card-header" style="background-color: rgba(35, 54, 67, 1); color: white;">
                    Quick Inventory Search
                </div>
                <div class="card-body">
                    <h5 class="card-title"></h5>
                    <!-- Quick Search Button -->
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#quickSearchModal">
                        <i class="fas fa-search"></i>
                    </button>
                    Find items and check stock instantly
                </div>
            </div>

            <!-- Get real equipment status data -->
            <?php
            // Get equipment status distribution from database
            $statusData = [];
            try {
                $statusQuery = $conn->query("
                SELECT 
                    status,
                    COUNT(*) as count,
                    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM items), 1) as percentage
                FROM items 
                WHERE status IS NOT NULL
                GROUP BY status 
                ORDER BY 
                    FIELD(status, 'available', 'in_use', 'maintenance', 'reserved', 'disposed', 'lost')
            ");

                if ($statusQuery) {
                    $statusData = $statusQuery->fetch_all(MYSQLI_ASSOC);
                }
            } catch (Exception $e) {
                error_log("Error getting status data: " . $e->getMessage());
            }

            // Prepare data for chart
            $statusLabels = [];
            $statusCounts = [];
            $statusColors = [];
            $statusPercentages = [];

            foreach ($statusData as $status) {
                $statusLabels[] = ucfirst($status['status']);
                $statusCounts[] = $status['count'];
                $statusPercentages[] = $status['percentage'];

                // Assign colors based on status
                switch (strtolower($status['status'])) {
                    case 'available':
                        $statusColors[] = '#3a869d';
                        break;
                    case 'in_use':
                        $statusColors[] = '#233D4D';
                        break;
                    case 'maintenance':
                        $statusColors[] = '#FF9B51';
                        break;
                    case 'reserved':
                        $statusColors[] = '#B7BDF7';
                        break;
                    case 'disposed':
                        $statusColors[] = '#C3110C';
                        break;
                    case 'lost':
                        $statusColors[] = 'rgba(128, 128, 128, 0.8)';
                        break;
                    default:
                        $statusColors[] = 'rgba(201, 203, 207, 0.8)';
                }
            }

            // If no data, show empty state
            if (empty($statusData)) {
                $statusLabels = ['No Data'];
                $statusCounts = [1];
                $statusColors = ['rgba(202, 205, 210, 0.58)'];
                $statusPercentages = [100];
            }
            ?>

            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center text-white" style="background-color: rgba(35, 54, 67, 1);">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-list-alt me-2"></i>Equipment Status Breakdown
                    </h6>
                    <button class="btn btn-sm btn-outline-light" onclick="refreshStatusChart()" title="Refresh Data">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th class="text-end">Count</th>
                                    <th class="text-end">%</th>
                                </tr>
                            </thead>
                            <tbody id="statusTableBody">
                                <?php if (!empty($statusData)): ?>
                                    <?php foreach ($statusData as $status): ?>
                                        <tr>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($status['status']); ?>">
                                                    <i class="fas fa-circle me-1"></i>
                                                    <?php echo ucfirst($status['status']); ?>
                                                </span>
                                            </td>
                                            <td class="text-end fw-bold"><?php echo $status['count']; ?></td>
                                            <td class="text-end"><?php echo $status['percentage']; ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">
                                            <i class="fas fa-chart-pie fa-2x mb-2 d-block"></i>
                                            No status data available
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center text-white" style="background-color: rgba(35, 54, 67, 1);">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-chart-pie me-2"></i>Equipment Status Distribution
                    </h6>
                    <button class="btn btn-sm btn-outline-light" onclick="refreshStatusChart()" title="Refresh Chart">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height: 300px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Total Equipment: <span id="totalEquipmentCount"><?php echo $stats['total_items'] ?? 0; ?></span>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Keep your existing Live Time & Calendar section -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center text-white" style="background-color: rgba(35, 54, 67, 1);">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-calendar-clock me-2"></i>Live Time & Calendar
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="row">
                        <!-- Digital Clock Column -->
                        <div class="col-md-6 border-end pe-3">
                            <div class="text-center">
                                <div id="digitalDay" class="h5 mb-2 fw-bold" style="color: #233643;"></div>
                                <div id="digitalDate" class="h6 text-muted mb-3"></div>
                                <div class="bg-light rounded-3 p-3 mb-2">
                                    <div id="digitalTime" class="display-6 fw-bold mb-1" style="color: #233643; font-family: 'Courier New', monospace;"></div>
                                    <div id="amPm" class="h6 text-muted"></div>
                                </div>
                                <div class="small text-muted mt-2">
                                    <i class="fas fa-globe-americas me-1"></i>
                                    <span id="timezone">Local Time</span>
                                </div>
                            </div>
                        </div>

                        <!-- Calendar Column -->
                        <div class="col-md-6 ps-3">
                            <div class="text-center">
                                <div id="currentMonth" class="h6 fw-bold mb-3" style="color: #233643;"></div>
                                <div id="monthCalendar" class="small"></div>
                                <div class="mt-3 pt-2 border-top">
                                    <div class="small text-muted mb-1">Today is</div>
                                    <div id="todayDate" class="fw-bold" style="color: #233643;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Equipment Table -->
    <div class="row">
        <div class="col-lg-12">
            <!-- Add this in your button group section (around line 532) -->

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">Equipments Table</h1>
                <div class="btn-group">

                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="fas fa-plus me-1"></i> Add Equipment
                    </button>
                    <a href="scan.php" class="btn btn-sm btn- text-white" style="background-color: #29843d;">
                        <i class="fas fa-qrcode me-1"></i> Scan QR
                    </a>
                    <!-- Download All QR Codes Button -->
                    <button onclick="downloadAllQRCodes()" class="btn btn-sm btn- text-white" style="background-color: #294084;">
                        <i class="fas fa-download me-1"></i> Download All QR Codes
                    </button>
                    <!-- Generate & Download ZIP Button -->
                    <button onclick="generateAndDownloadQRZipWithProgress()" class="btn btn-sm btn- text-white" style="background-color: #2e7ca7;">
                        <i class="fas fa-file-archive me-1"></i> Generate & Download ZIP
                    </button>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center text-white" style="background-color: rgba(35, 54, 67, 1);">
                    <h6 class="m-0 font-weight-bold text-white">
                        <i class="fas fa-list me-2"></i>Recently Added Equipment
                    </h6>
                    <button class="btn btn-sm btn-" id="refreshItemsBtn" title="Refresh Table" style="background-color: rgb(42, 73, 113); color: white;">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="recentItemsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Created At</th>
                                    <th>Item Name</th>
                                    <th>Serial #</th>
                                    <th>Category</th>
                                    <th>Accessories</th>
                                    <th>Brand</th>
                                    <th>Model</th>
                                    <th>Department</th>
                                    <th>Location</th>
                                    <th>Condition</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentItems)): ?>
                                    <?php foreach ($recentItems as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['id'] ?? ''); ?></td>

                                            <!-- NEW COLUMN: Created At -->
                                            <td>
                                                <?php
                                                if (!empty($item['created_at'])) {
                                                    // Format the date for better display
                                                    $createdDate = new DateTime($item['created_at']);
                                                    echo '<span class="badge bg-secondary" title="' .
                                                        htmlspecialchars($item['created_at']) . '">' .
                                                        $createdDate->format('M d, Y') . '</span><br>' .
                                                        '<small class="text-muted">' .
                                                        $createdDate->format('h:i A') . '</small>';
                                                } else {
                                                    echo '<span class="text-muted">N/A</span>';
                                                }
                                                ?>
                                            </td>

                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($item['item_name'] ?? ''); ?></div>
                                                <?php if (!empty($item['description'])): ?>
                                                    <small class="text-muted d-block"><?php echo substr(htmlspecialchars($item['description'] ?? ''), 0, 50); ?>...</small>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <code><?php echo htmlspecialchars($item['serial_number'] ?? 'N/A'); ?></code>
                                            </td>

                                            <td>
                                                <?php
                                                $category = $item['category'] ?? ($item['category_id'] ?? 'Uncategorized');
                                                echo '<span class="badge bg-secondary">' . htmlspecialchars($category) . '</span>';
                                                ?>
                                            </td>

                                            <td>
                                                <?php
                                                if (isset($item['id'])) {
                                                    try {
                                                        $accessories = getItemAccessories($item['id'], $conn);
                                                        if (!empty($accessories)) {
                                                            foreach ($accessories as $acc) {
                                                                echo '<span class="badge bg-info me-1 mb-1">' . htmlspecialchars($acc['name'] ?? 'Unknown') . '</span> ';
                                                            }
                                                        } else {
                                                            echo '<span class="text-muted">None</span>';
                                                        }
                                                    } catch (Exception $e) {
                                                        echo '<span class="text-muted">Error</span>';
                                                    }
                                                } else {
                                                    echo '<span class="text-muted">None</span>';
                                                }
                                                ?>
                                            </td>

                                            <td>
                                                <?php echo !empty($item['brand']) ? htmlspecialchars($item['brand']) : '<span class="text-muted">N/A</span>'; ?>
                                            </td>

                                            <td>
                                                <?php echo !empty($item['model']) ? htmlspecialchars($item['model']) : '<span class="text-muted">N/A</span>'; ?>
                                            </td>

                                            <td><?php echo !empty($item['department']) ? htmlspecialchars($item['department']) : (!empty($item['department_id']) ? 'Dept ID: ' . $item['department_id'] : '<span class="text-muted">N/A</span>'); ?></td>

                                            <td><?php echo !empty($item['stock_location']) ? htmlspecialchars($item['stock_location']) : '<span class="text-muted">N/A</span>'; ?></td>

                                            <td>
                                                <?php
                                                $condition = $item['condition'] ?? 'good';
                                                $conditionClass = '';
                                                switch (strtolower($condition)) {
                                                    case 'new':
                                                        $conditionClass = 'bg-success';
                                                        break;
                                                    case 'good':
                                                        $conditionClass = 'bg-primary';
                                                        break;
                                                    case 'fair':
                                                        $conditionClass = 'bg-warning';
                                                        break;
                                                    case 'poor':
                                                        $conditionClass = 'bg-danger';
                                                        break;
                                                    default:
                                                        $conditionClass = 'bg-secondary';
                                                }
                                                echo '<span class="badge ' . $conditionClass . '">' . ucfirst($condition) . '</span>';
                                                ?>
                                            </td>

                                            <td>
                                                <?php
                                                $status = $item['status'] ?? 'available';
                                                $statusClass = '';
                                                switch (strtolower($status)) {
                                                    case 'available':
                                                        $statusClass = 'bg-success';
                                                        break;
                                                    case 'in_use':
                                                        $statusClass = 'bg-primary';
                                                        break;
                                                    case 'maintenance':
                                                        $statusClass = 'bg-warning';
                                                        break;
                                                    case 'reserved':
                                                        $statusClass = 'bg-info';
                                                        break;
                                                    case 'disposed':
                                                        $statusClass = 'bg-danger';
                                                        break;
                                                    case 'lost':
                                                        $statusClass = 'bg-dark';
                                                        break;
                                                    default:
                                                        $statusClass = 'bg-secondary';
                                                }
                                                echo '<span class="badge ' . $statusClass . '">' . ucfirst($status) . '</span>';
                                                ?>
                                            </td>

                                            <td>
                                                <!-- Quick Actions Button -->
                                                <button type="button" class="btn btn-sm btn-primary quick-view-btn mb-1"
                                                    data-item-id="<?php echo $item['id'] ?? ''; ?>"
                                                    data-item-name="<?php echo htmlspecialchars($item['item_name'] ?? ''); ?>"
                                                    data-item-serial="<?php echo htmlspecialchars($item['serial_number'] ?? ''); ?>"
                                                    data-item-category="<?php echo htmlspecialchars($item['category'] ?? ($item['category_id'] ?? 'N/A')); ?>"
                                                    data-item-quantity="<?php echo $item['quantity'] ?? 1; ?>"
                                                    data-item-status="<?php echo htmlspecialchars($item['status'] ?? 'available'); ?>"
                                                    data-item-condition="<?php echo htmlspecialchars($item['condition'] ?? 'good'); ?>"
                                                    data-item-location="<?php echo htmlspecialchars($item['stock_location'] ?? 'N/A'); ?>"
                                                    data-item-description="<?php echo htmlspecialchars($item['description'] ?? ''); ?>"
                                                    data-item-brand="<?php echo htmlspecialchars($item['brand'] ?? 'N/A'); ?>"
                                                    data-item-model="<?php echo htmlspecialchars($item['model'] ?? 'N/A'); ?>"
                                                    data-item-department="<?php echo htmlspecialchars($item['department'] ?? ($item['department_id'] ?? 'N/A')); ?>"
                                                    data-item-accessories="<?php echo htmlspecialchars(getAccessoriesList($item['id'] ?? 0)); ?>"
                                                    data-view-url="items/view.php?id=<?php echo $item['id'] ?? ''; ?>"
                                                    data-edit-url="items/edit.php?id=<?php echo $item['id'] ?? ''; ?>"
                                                    data-qr-code="<?php echo htmlspecialchars($item['qr_code'] ?? ''); ?>"
                                                    title="Quick Actions">
                                                    <i class="fas fa-bolt"></i> Quick Actions
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="13" class="text-center">No equipment found</td>
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
                    <div class="alert alert-info d-flex align-items-center mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <div>Fields marked with <span class="text-danger">*</span> are required</div>
                    </div>

                    <div class="row">
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
                        <textarea class="form-control" id="description" name="description" rows="3"
                            placeholder="Additional details about this equipment"></textarea>
                    </div>

                    <!-- Accessories Section -->
                    <div class="card border-info mb-4">
                        <div class="card-header bg-info text-white py-2">
                            <i class="fas fa-puzzle-piece me-2"></i>Accessories
                            <small class="float-end">Select accessories included with this equipment</small>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="accessories" class="form-label">Select Accessories</label>
                                        <select class="form-select" id="accessories" name="accessories[]" multiple size="6">
                                            <option value="">-- No Accessories --</option>
                                            <?php foreach ($accessories as $accessory): ?>
                                                <option value="<?php echo $accessory['id']; ?>"
                                                    data-description="<?php echo htmlspecialchars($accessory['description']); ?>">
                                                    <?php echo htmlspecialchars($accessory['name']); ?>
                                                    (<?php echo $accessory['available_quantity']; ?> available)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">
                                            Hold Ctrl/Cmd to select multiple accessories
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Selected Accessories</label>
                                        <div id="selectedAccessories" class="border rounded p-2" style="min-height: 150px; max-height: 150px; overflow-y: auto;">
                                            <p class="text-muted mb-0">No accessories selected</p>
                                        </div>
                                        <small class="text-muted">Click to remove</small>
                                    </div>
                                </div>
                            </div>
                        </div>
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

<!-- Quick Actions Modal -->
<div class="modal fade" id="quickActionsModal" tabindex="-1" aria-labelledby="quickActionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="quickActionsModalLabel">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    <!-- Left Column: Item Details -->
                    <div class="col-md-8 border-end">
                        <div class="p-4">
                            <!-- Item Header -->
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div>
                                    <h4 id="qvItemName" class="fw-bold mb-1" style="color: #233643;"></h4>
                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                        <span class="badge bg-secondary" id="qvItemCategory"></span>
                                        <span class="badge" id="qvItemStatusBadge"></span>
                                        <span class="badge" id="qvItemConditionBadge"></span>
                                        <span class="text-muted small">
                                            <i class="fas fa-hashtag me-1"></i>ID: <span id="qvItemId"></span>
                                        </span>
                                    </div>
                                </div>
                                <!-- In your quick actions modal HTML -->
                                <div id="qvQRCode" class="text-center">
                                    <small class="d-block text-muted" id="qrItemName"></small>
                                    <!-- rest of your QR code HTML -->
                                </div>
                            </div>

                            <!-- Quick Stats -->
                            <div class="row mb-4">
                                <div class="col-6 col-md-3 mb-3">
                                    <div class="border rounded p-3 text-center">
                                        <div class="text-muted small">Serial</div>
                                        <div class="fw-bold mt-1" id="qvItemSerial"></div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3 mb-3">
                                    <div class="border rounded p-3 text-center">
                                        <div class="text-muted small">Quantity</div>
                                        <div class="fw-bold mt-1" id="qvItemQuantity"></div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3 mb-3">
                                    <div class="border rounded p-3 text-center">
                                        <div class="text-muted small">Location</div>
                                        <div class="fw-bold mt-1" id="qvItemLocation"></div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3 mb-3">
                                    <div class="border rounded p-3 text-center">
                                        <div class="text-muted small">Department</div>
                                        <div class="fw-bold mt-1" id="qvItemDepartment"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Details Table -->
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless">
                                    <tbody>
                                        <tr>
                                            <th width="30%">Brand:</th>
                                            <td id="qvItemBrand"></td>
                                        </tr>
                                        <tr>
                                            <th>Model:</th>
                                            <td id="qvItemModel"></td>
                                        </tr>
                                        <tr>
                                            <th>Accessories:</th>
                                            <td id="qvItemAccessories"></td>
                                        </tr>
                                        <tr>
                                            <th>Description:</th>
                                            <td id="qvItemDescription"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Actions -->
                    <div class="col-md-4 bg-light">
                        <div class="p-4 h-100">
                            <h6 class="fw-bold mb-4">Actions</h6>

                            <!-- View Details Button -->
                            <div class="mb-3">
                                <a href="#" id="qvViewBtn" class="btn btn-primary w-100 py-3" target="_blank">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <i class="fas fa-eye fa-2x me-3"></i>
                                        <div class="text-start">
                                            <div class="fw-bold">View Details</div>
                                            <small class="opacity-75">Complete item information</small>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <!-- Edit Button -->
                            <div class="mb-3">
                                <a href="#" id="qvEditBtn" class="btn btn-warning w-100 py-3" target="_blank">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <i class="fas fa-edit fa-2x me-3"></i>
                                        <div class="text-start">
                                            <div class="fw-bold">Edit Item</div>
                                            <small class="opacity-75">Modify item details</small>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <!-- QR Code Actions -->
                            <div class="mb-3" id="qvQRActions"></div>

                            <!-- Additional Actions -->
                            <div class="border-top pt-3 mt-3">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <button class="btn btn-outline-info w-100" id="qvCopySerialBtn">
                                            <i class="fas fa-copy me-1"></i> Copy Serial
                                        </button>
                                    </div>
                                    <div class="col-6">
                                        <button class="btn btn-outline-dark w-100" id="qvPrintBtn">
                                            <i class="fas fa-print me-1"></i> Print
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Status Change -->
                            <div class="border-top pt-3 mt-3">
                                <h6 class="fw-bold mb-2">Quick Status Update</h6>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-sm btn-success" data-status="available">
                                        <i class="fas fa-check me-1"></i> Mark Available
                                    </button>
                                    <button class="btn btn-sm btn-primary" data-status="in_use">
                                        <i class="fas fa-wrench me-1"></i> Mark In Use
                                    </button>
                                    <button class="btn btn-sm btn-warning" data-status="maintenance">
                                        <i class="fas fa-tools me-1"></i> Mark Maintenance
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Close
                </button>
                <button type="button" class="btn btn-outline-primary" id="qvRefreshBtn">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Datalist for autocomplete -->
<datalist id="itemSuggestions">
    <?php foreach ($allItems as $item): ?>
        <option value="<?php echo htmlspecialchars($item['item_name']); ?>">
        <?php endforeach; ?>
</datalist>

<?php
// Close database connection
$db->close();

// Include footer
require_once 'views/partials/footer.php';
?>

<!-- Load DataTables JS -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

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

    $(document).ready(function() {
        // Debug: Check table structure
        console.log('=== TABLE DEBUG INFO ===');
        console.log('Table found:', $('#recentItemsTable').length > 0);

        if ($('#recentItemsTable').length > 0) {
            const headerCols = $('#recentItemsTable thead th').length;
            const bodyRows = $('#recentItemsTable tbody tr').length;
            const firstRowCols = $('#recentItemsTable tbody tr:first-child td').length;

            console.log('Header columns:', headerCols);
            console.log('Body rows:', bodyRows);
            console.log('First row columns:', firstRowCols);

            // Only fix if there's a mismatch and it's the "No equipment found" row
            if (bodyRows === 1 && firstRowCols === 1 && headerCols === 13) {
                console.log('⚠️ Fixing table structure...');

                // Get the "No equipment found" message
                const message = $('#recentItemsTable tbody tr td:first').text();

                // Clear the table body
                $('#recentItemsTable tbody').empty();

                // Add a row with 13 cells to match the header
                $('#recentItemsTable tbody').append(`
                <tr>
                    <td>-</td>
                    <td class="text-center">${message}</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                </tr>
            `);

                console.log('✅ Table structure fixed');
            }
        }

        // ========== SIMPLE DATATABLES INITIALIZATION ==========
        function initializeDataTable() {
            // Check if table exists
            if ($('#recentItemsTable').length === 0) {
                console.log('Table not found');
                return;
            }

            // Check if DataTable is already initialized
            if ($.fn.DataTable.isDataTable('#recentItemsTable')) {
                console.log('DataTable already initialized, destroying...');
                $('#recentItemsTable').DataTable().destroy();
            }

            try {
                // Initialize DataTable with DOM data (not AJAX)
                const dataTable = $('#recentItemsTable').DataTable({
                    paging: true,
                    pageLength: 5,
                    lengthChange: true,
                    searching: true,
                    ordering: true,
                    info: true,
                    autoWidth: false,
                    responsive: true,
                    order: [
                        [1, 'desc'] // Sort by Created At (2nd column) descending
                    ],
                    language: {
                        emptyTable: "No equipment found",
                        info: "Showing _START_ to _END_ of _TOTAL_ items",
                        infoEmpty: "Showing 0 to 0 of 0 items",
                        infoFiltered: "(filtered from _MAX_ total items)",
                        lengthMenu: "Show _MENU_ entries",
                        search: "Search in all columns:",
                        paginate: {
                            first: "First",
                            last: "Last",
                            next: "Next",
                            previous: "Previous"
                        }
                    },
                    // Explicitly tell DataTables to use DOM data
                    processing: false,
                    serverSide: false,
                    ajax: null // Disable AJAX
                });

                console.log('✅ DataTable initialized successfully');
                return dataTable;

            } catch (error) {
                console.error('❌ DataTable initialization error:', error);
                // Fallback styling
                $('#recentItemsTable').addClass('table table-bordered table-hover table-striped');
                return null;
            }
        }

        // Initialize DataTable with a small delay
        setTimeout(function() {
            initializeDataTable();
        }, 300);

        // ========== REFRESH BUTTON ==========
        $('#refreshItemsBtn').click(function(e) {
            e.preventDefault();

            const button = $(this);
            const originalHtml = button.html();
            button.html('<i class="fas fa-spinner fa-spin me-1"></i> Refreshing...');
            button.prop('disabled', true);

            toastr.info('Refreshing data...');

            // Check if DataTable uses AJAX
            if ($.fn.DataTable.isDataTable('#recentItemsTable')) {
                const table = $('#recentItemsTable').DataTable();

                // Check if table uses server-side processing
                if (table.settings()[0].oFeatures.bServerSide) {
                    // Reload via AJAX
                    table.ajax.reload(null, false);
                } else {
                    // Destroy and recreate for DOM tables
                    table.destroy();
                    setTimeout(() => {
                        initializeDataTable();
                    }, 300);
                }
            } else {
                // Simple page reload
                setTimeout(() => {
                    location.reload();
                }, 500);
            }

            // Re-enable button
            setTimeout(() => {
                button.html(originalHtml);
                button.prop('disabled', false);
                toastr.success('Data refreshed!');
            }, 1500);
        });

        // Add keyboard shortcut: Ctrl+R or Cmd+R to refresh
        $(document).keydown(function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                $('#refreshItemsBtn').click();
            }
        });

        // ========== IMAGE PREVIEW FUNCTIONALITY ==========
        $('#item_image').on('change', function(e) {
            const file = e.target.files[0];
            const preview = $('#imagePreview');
            const previewImg = preview.find('img');

            if (file) {
                // Check file size (5MB max)
                if (file.size > 5 * 1024 * 1024) {
                    toastr.error('File size must be less than 5MB');
                    $(this).val('');
                    preview.hide();
                    return;
                }

                // Check file type
                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    toastr.error('Please select a valid image file (JPG, PNG, GIF, WebP)');
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

        // ========== ACCESSORIES PREVIEW FUNCTIONALITY ==========
        $('#accessories').on('change', function() {
            updateSelectedAccessories();
        });

        function updateSelectedAccessories() {
            const selectedContainer = $('#selectedAccessories');
            const selected = $('#accessories').find('option:selected');

            if (selected.length === 0 || (selected.length === 1 && !selected.val())) {
                selectedContainer.html('<p class="text-muted mb-0">No accessories selected</p>');
                return;
            }

            let html = '<div class="d-flex flex-wrap gap-1">';
            selected.each(function() {
                if ($(this).val()) {
                    const accessoryName = $(this).text().split(' (')[0];
                    html += `
                    <span class="badge bg-info cursor-pointer me-1 mb-1 accessory-badge" 
                          data-value="${$(this).val()}">
                        ${accessoryName}
                        <i class="fas fa-times ms-1"></i>
                    </span>
                `;
                }
            });
            html += '</div>';

            selectedContainer.html(html);

            // Add click handler to remove accessories
            $(document).off('click', '.accessory-badge').on('click', '.accessory-badge', function(e) {
                e.stopPropagation();
                const value = $(this).data('value');
                $('#accessories option[value="' + value + '"]').prop('selected', false);
                $(this).remove();

                // Update display if all are removed
                if ($('#selectedAccessories .accessory-badge').length === 0) {
                    selectedContainer.html('<p class="text-muted mb-0">No accessories selected</p>');
                }
            });
        }

        // ========== ADD ITEM FORM SUBMISSION ==========
        $('#addItemForm').on('submit', function(e) {
            e.preventDefault();

            // Validate required fields
            const itemName = $('#item_name').val().trim();
            const serialNumber = $('#serial_number').val().trim();
            const category = $('#category').val();

            if (!itemName || !serialNumber || !category) {
                toastr.error('Please fill all required fields (Item Name, Serial Number, Category)');
                return;
            }

            // Get form data
            const formData = new FormData(this);

            // Add accessories data
            const selectedAccessories = [];
            $('#accessories option:selected').each(function() {
                if ($(this).val()) {
                    selectedAccessories.push($(this).val());
                }
            });
            formData.append('accessories_array', JSON.stringify(selectedAccessories));

            // Show loading state
            const submitBtn = $('#submitBtn');
            const originalText = submitBtn.html();
            submitBtn.html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');
            submitBtn.prop('disabled', true);

            // Submit via AJAX
            $.ajax({
                url: 'api/items/create.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    console.log('Server response:', response);

                    if (response.success) {
                        toastr.success(response.message || 'Item added successfully!');

                        // Reset form
                        $('#addItemForm')[0].reset();
                        $('#imagePreview').hide();
                        $('#selectedAccessories').html('<p class="text-muted mb-0">No accessories selected</p>');
                        $('#accessories').val('').trigger('change');

                        // Close modal after 1.5 seconds
                        setTimeout(() => {
                            $('#addItemModal').modal('hide');
                            // Simple page reload to show new data
                            location.reload();
                        }, 1500);
                    } else {
                        toastr.error(response.message || 'Failed to add item');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error Details:');
                    console.error('Status:', status);
                    console.error('Error:', error);
                    console.error('Response:', xhr.responseText);

                    let errorMessage = 'An error occurred while adding the item';

                    if (xhr.responseText) {
                        try {
                            const jsonResponse = JSON.parse(xhr.responseText);
                            if (jsonResponse.message) {
                                errorMessage = jsonResponse.message;
                            }
                        } catch (e) {
                            // If not JSON, show first 100 chars
                            errorMessage = 'Server error: ' + xhr.responseText.substring(0, 100);
                        }
                    }

                    toastr.error(errorMessage);
                },
                complete: function() {
                    submitBtn.html(originalText);
                    submitBtn.prop('disabled', false);
                }
            });
        });

        // ========== GENERATE SERIAL NUMBER ==========
        $('#generateSerialBtn').click(function() {
            const name = $('#item_name').val().trim();
            const prefix = name ? name.substring(0, 3).toUpperCase().replace(/\s/g, '') : 'EQP';
            const timestamp = Date.now().toString().substr(-8);
            const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
            $('#serial_number').val(`${prefix}-${timestamp}-${random}`);
        });

        // ========== QUICK ACTIONS MODAL HANDLING ==========
        $(document).on('click', '.quick-view-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const itemId = $(this).data('item-id');
            const itemName = $(this).data('item-name');

            if (!itemId) {
                toastr.error('Invalid item ID');
                return;
            }

            // Show loading
            toastr.info(`Loading ${itemName}...`, '', {
                timeOut: 3000
            });

            // Use the correct path - try both possibilities
            const apiPaths = [
                'api/get_item.php',
                '../api/get_item.php',
                './api/get_item.php',
                '/api/get_item.php'
            ];

            // Try each path until one works
            function tryApiPath(index) {
                if (index >= apiPaths.length) {
                    toastr.error('Could not find API endpoint');
                    return;
                }

                const apiPath = apiPaths[index];
                console.log('Trying API path:', apiPath);

                $.ajax({
                    url: apiPath,
                    method: 'GET',
                    data: {
                        id: itemId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data) {
                            // Populate modal with data
                            populateQuickViewModal(response.data);

                            // Show modal using Bootstrap 5
                            const quickActionsModal = document.getElementById('quickActionsModal');
                            const modal = new bootstrap.Modal(quickActionsModal);
                            modal.show();

                            toastr.success('Item loaded successfully');
                        } else {
                            toastr.error(response.message || 'Failed to load item details');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(`Error with path ${apiPath}:`, error);

                        if (xhr.status === 404) {
                            // Try next path
                            tryApiPath(index + 1);
                        } else {
                            toastr.error('Error loading item details. Status: ' + xhr.status);
                        }
                    }
                });
            }

            // Start trying paths
            tryApiPath(0);
        });

        // Function to populate quick view modal
        function populateQuickViewModal(data) {
            // Populate all fields
            $('#qvItemId').text(data.id || '');
            $('#qvItemName').text(data.item_name || '');
            $('#qvItemCategory').text(data.category || 'N/A');
            $('#qvItemSerial').text(data.serial_number || 'N/A');
            $('#qvItemQuantity').text(data.quantity || 1);
            $('#qvItemLocation').text(data.stock_location || 'N/A');
            $('#qvItemDepartment').text(data.department || 'N/A');
            $('#qvItemBrand').text(data.brand || 'N/A');
            $('#qvItemModel').text(data.model || 'N/A');
            $('#qvItemDescription').text(data.description || 'No description available');
            $('#qvItemAccessories').text(data.accessories || 'None');

            // Add item name to QR code section
            $('#qrItemName').text(data.item_name || '');

            // Set status badge
            const statusBadge = $('#qvItemStatusBadge');
            statusBadge.removeClass().addClass('badge');
            switch (data.status) {
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

            // Set condition badge
            const conditionBadge = $('#qvItemConditionBadge');
            conditionBadge.removeClass().addClass('badge');
            switch (data.condition) {
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

            // Set URLs
            $('#qvViewBtn').attr('href', 'items/view.php?id=' + data.id);
            $('#qvEditBtn').attr('href', 'items/edit.php?id=' + data.id);

            // Handle QR Code display and actions
            const qrContainer = $('#qvQRCode');
            qrContainer.empty();

            if (data.qr_code && data.qr_code !== '' && data.qr_code !== 'pending') {
                qrContainer.html(`
            <div class="text-center">
                <img src="${data.qr_code}" alt="QR Code" 
                     style="width: 100px; height: 100px;" 
                     class="img-fluid border rounded p-1">
                <div class="mt-2">
                    <button class="btn btn-sm btn-success mb-1 download-qr-btn">
                        <i class="fas fa-download me-1"></i> Download QR
                    </button>
                    <button class="btn btn-sm btn-info view-qr-btn">
                        <i class="fas fa-expand me-1"></i> View Full
                    </button>
                </div>
                <small class="text-muted d-block mt-1">${data.item_name || 'QR Code'}</small>
            </div>
        `);

                // Add QR download functionality
                $('.download-qr-btn').off('click').on('click', function() {
                    downloadSingleQRCode(data.qr_code, data.item_name, data.serial_number);
                });

                $('.view-qr-btn').off('click').on('click', function() {
                    window.open(data.qr_code, '_blank');
                });

            } else if (data.qr_code === 'pending') {
                qrContainer.html(`
            <div class="text-center">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="small mt-2">Generating QR Code...</div>
                <button class="btn btn-sm btn-outline-primary mt-2 generate-qr-btn">
                    <i class="fas fa-bolt me-1"></i> Generate Now
                </button>
            </div>
        `);

                $('.generate-qr-btn').off('click').on('click', function() {
                    generateQRCodeForItem(data.id, data.item_name);
                });

            } else {
                qrContainer.html(`
            <div class="text-center">
                <i class="fas fa-qrcode fa-3x text-muted mb-2"></i>
                <div class="small text-muted mb-2">No QR Code</div>
                <button class="btn btn-sm btn-primary generate-qr-btn">
                    <i class="fas fa-plus-circle me-1"></i> Generate QR
                </button>
            </div>
        `);

                $('.generate-qr-btn').off('click').on('click', function() {
                    generateQRCodeForItem(data.id, data.item_name);
                });
            }

            // Set up action buttons
            $('#qvCopySerialBtn').off('click').on('click', function() {
                navigator.clipboard.writeText(data.serial_number || '');
                toastr.success('Serial number copied to clipboard');
            });

            $('#qvPrintBtn').off('click').on('click', function() {
                window.open('items/print.php?id=' + data.id, '_blank');
            });

            // Set up status change buttons
            $('[data-status]').off('click').on('click', function() {
                const newStatus = $(this).data('status');
                updateItemStatus(data.id, newStatus);
            });
        }

        // Function to update item status
        function updateItemStatus(itemId, newStatus) {
            if (!itemId || !newStatus) return;

            $.ajax({
                url: 'api/update_status.php',
                method: 'POST',
                data: {
                    item_id: itemId,
                    status: newStatus
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success('Status updated successfully');
                        // Refresh the modal
                        $('.quick-view-btn[data-item-id="' + itemId + '"]').click();
                    } else {
                        toastr.error(response.message || 'Failed to update status');
                    }
                },
                error: function() {
                    toastr.error('Error updating status');
                }
            });
        }

        // Refresh modal button
        $('#qvRefreshBtn').click(function() {
            const itemId = $('#qvItemId').text();
            if (itemId) {
                $('.quick-view-btn[data-item-id="' + itemId + '"]').click();
            }
        });

        // ========== LIVE CLOCK AND CALENDAR ==========
        function updateDateTime() {
            const now = new Date();

            // Get timezone name
            const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            const timezoneParts = timezone.split('/');
            const timezoneDisplay = timezoneParts[timezoneParts.length - 1].replace(/_/g, ' ');
            document.getElementById('timezone').textContent = timezoneDisplay;

            // Format day of week: Wednesday
            const dayOfWeek = now.toLocaleDateString('en-US', {
                weekday: 'long'
            });

            // Format date: Jan 22, 2025
            const formattedDate = now.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });

            // Format full date for today display
            const todayDate = now.toLocaleDateString('en-US', {
                month: 'long',
                day: 'numeric',
                year: 'numeric'
            });

            // Format time
            let hours = now.getHours();
            let minutes = now.getMinutes();
            let seconds = now.getSeconds();
            const ampm = hours >= 12 ? 'PM' : 'AM';

            // Convert to 12-hour format
            hours = hours % 12;
            hours = hours ? hours : 12;

            // Add leading zeros
            minutes = minutes < 10 ? '0' + minutes : minutes;
            seconds = seconds < 10 ? '0' + seconds : seconds;

            // Update DOM elements
            document.getElementById('digitalDay').textContent = dayOfWeek;
            document.getElementById('digitalDate').textContent = formattedDate;
            document.getElementById('digitalTime').textContent = `${hours}:${minutes}:${seconds}`;
            document.getElementById('amPm').textContent = ampm;
            document.getElementById('todayDate').textContent = todayDate;

            // Update month calendar view
            updateMonthCalendar(now);
        }

        function updateMonthCalendar(date) {
            const monthNames = ['JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE',
                'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'
            ];
            const dayNames = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];

            const currentMonth = date.getMonth();
            const currentYear = date.getFullYear();
            const today = date.getDate();

            // Update month/year header
            document.getElementById('currentMonth').textContent = `${monthNames[currentMonth]} ${currentYear}`;

            // Get first day of month and number of days
            const firstDay = new Date(currentYear, currentMonth, 1);
            const lastDay = new Date(currentYear, currentMonth + 1, 0);
            const daysInMonth = lastDay.getDate();
            const startingDay = firstDay.getDay();

            // Generate calendar grid
            let calendarHTML = '<table class="table table-sm table-borderless mb-0" style="margin: 0 auto; width: 100%;">';

            // Day headers
            calendarHTML += '<tr>';
            dayNames.forEach(day => {
                calendarHTML += `<th class="text-center small text-muted p-1 pb-2" style="width: 14.28%;">${day}</th>`;
            });
            calendarHTML += '</tr><tr>';

            // Empty cells for days before the 1st
            for (let i = 0; i < startingDay; i++) {
                calendarHTML += '<td class="p-1" style="width: 14.28%;"></td>';
            }

            // Days of the month
            for (let day = 1; day <= daysInMonth; day++) {
                const isToday = (day === today);
                const cellClass = isToday ? 'bg-primary text-white rounded-circle' : '';
                const fontWeight = isToday ? 'fw-bold' : '';

                calendarHTML += `<td class="text-center p-1" style="width: 14.28%;">
                <span class="${cellClass} ${fontWeight}" 
                      style="display: inline-block; width: 24px; height: 24px; line-height: 24px; border-radius: 50%;">
                    ${day}
                </span>
            </td>`;

                // Start new row after Saturday
                if ((day + startingDay) % 7 === 0 && day < daysInMonth) {
                    calendarHTML += '</tr><tr>';
                }
            }

            // Fill remaining empty cells in the last row
            const remainingCells = 7 - ((daysInMonth + startingDay) % 7);
            if (remainingCells < 7) {
                for (let i = 0; i < remainingCells; i++) {
                    calendarHTML += '<td class="p-1" style="width: 14.28%;"></td>';
                }
            }

            calendarHTML += '</tr></table>';
            document.getElementById('monthCalendar').innerHTML = calendarHTML;
        }

        // Initialize date/time and update every second
        updateDateTime();
        setInterval(updateDateTime, 1000);

        // ========== INITIALIZE TOASTR NOTIFICATIONS ==========
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": true,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };

        // ========== FILE DROPZONE FUNCTIONALITY ==========
        const dropzone = document.getElementById('fileDropzone');
        const fileInput = document.getElementById('item_image');

        if (dropzone && fileInput) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, unhighlight, false);
            });

            function highlight() {
                dropzone.classList.add('border-primary', 'bg-light');
            }

            function unhighlight() {
                dropzone.classList.remove('border-primary', 'bg-light');
            }

            dropzone.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;

                if (files.length > 0) {
                    // Check if file is an image
                    if (files[0].type.startsWith('image/')) {
                        // Check file size (5MB max)
                        if (files[0].size > 5 * 1024 * 1024) {
                            toastr.error('File size must be less than 5MB');
                            return;
                        }

                        // Create a DataTransfer object to set files
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(files[0]);
                        fileInput.files = dataTransfer.files;

                        // Trigger change event
                        const event = new Event('change', {
                            bubbles: true
                        });
                        fileInput.dispatchEvent(event);

                        toastr.success('Image uploaded successfully!');
                    } else {
                        toastr.error('Please upload only image files (JPG, PNG, GIF, WebP)');
                    }
                }
            }

            // Click to select file
            dropzone.addEventListener('click', () => {
                fileInput.click();
            });
        }

        // ========== AUTO-RESIZE TEXTAREA FOR DESCRIPTION ==========
        const descriptionTextarea = document.getElementById('description');
        if (descriptionTextarea) {
            descriptionTextarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        }

        // ========== CATEGORY CHANGE EVENT ==========
        $('#category').on('change', function() {
            const selectedCategory = $(this).val();

            // Show/hide electronics-specific fields
            if (selectedCategory === 'electronics' || selectedCategory === 'laptops' ||
                selectedCategory === 'phones' || selectedCategory === 'tablets') {
                $('#electronicsFields').show();
            } else {
                $('#electronicsFields').hide();
            }

            // Show/hide furniture-specific fields
            if (selectedCategory === 'furniture' || selectedCategory === 'office_furniture') {
                $('#furnitureFields').show();
            } else {
                $('#furnitureFields').hide();
            }
        });

        // Trigger change event on page load
        $('#category').trigger('change');

        // ========== CONFIRMATION FOR DELETE ACTIONS ==========
        $(document).on('click', '.delete-item-btn', function(e) {
            e.preventDefault();
            const itemId = $(this).data('item-id');
            const itemName = $(this).data('item-name');

            if (confirm(`Are you sure you want to delete "${itemName}"? This action cannot be undone.`)) {
                $.ajax({
                    url: 'api/items/delete.php',
                    method: 'POST',
                    data: {
                        id: itemId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function() {
                        toastr.error('Error deleting item');
                    }
                });
            }
        });

        // ========== EXPORT FUNCTIONALITY ==========
        $('#exportItemsBtn').click(function() {
            toastr.info('Preparing export...');

            $.ajax({
                url: 'api/export_items.php',
                method: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.download_url) {
                        const link = document.createElement('a');
                        link.href = response.download_url;
                        link.download = response.filename || 'items_export.csv';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);

                        toastr.success('Export downloaded successfully!');
                    } else {
                        toastr.error(response.message || 'Failed to export items');
                    }
                },
                error: function() {
                    toastr.error('Error exporting items');
                }
            });
        });

        // ========== BULK ACTIONS ==========
        let selectedItems = [];

        $(document).on('change', '.item-checkbox', function() {
            const itemId = $(this).val();

            if ($(this).is(':checked')) {
                selectedItems.push(itemId);
            } else {
                selectedItems = selectedItems.filter(id => id !== itemId);
            }

            // Show/hide bulk actions
            if (selectedItems.length > 0) {
                $('#bulkActions').show();
                $('#selectedCount').text(selectedItems.length);
            } else {
                $('#bulkActions').hide();
            }
        });

        $('#selectAllItems').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.item-checkbox').prop('checked', isChecked).trigger('change');
        });

        $('#bulkDeleteBtn').click(function() {
            if (selectedItems.length === 0) {
                toastr.warning('No items selected');
                return;
            }

            if (confirm(`Are you sure you want to delete ${selectedItems.length} selected item(s)?`)) {
                $.ajax({
                    url: 'api/bulk_delete.php',
                    method: 'POST',
                    data: {
                        items: selectedItems
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function() {
                        toastr.error('Error deleting items');
                    }
                });
            }
        });

        // ========== INITIALIZE TOOLTIPS ==========
        $(function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });

        // ========== PERFORMANCE METRICS UPDATE ==========
        function updatePerformanceMetrics() {
            $.ajax({
                url: 'api/get_metrics.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update total items count
                        if (response.total_items !== undefined) {
                            $('#totalItemsCount').text(response.total_items);
                        }

                        // Update available items
                        if (response.available_items !== undefined) {
                            $('#availableItemsCount').text(response.available_items);
                        }

                        // Update in-use items
                        if (response.in_use_items !== undefined) {
                            $('#inUseItemsCount').text(response.in_use_items);
                        }

                        // Update maintenance items
                        if (response.maintenance_items !== undefined) {
                            $('#maintenanceItemsCount').text(response.maintenance_items);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    // Silent fail - don't log to console for missing API
                    if (xhr.status !== 404) {
                        console.error('Failed to update metrics:', error);
                    }
                }
            });
        }

        // ========== STATUS CHART INITIALIZATION ==========
        function initializeStatusChart() {
            console.log('Initializing status chart with real data...');

            const canvas = document.getElementById('statusChart');
            if (!canvas) {
                console.error('Chart canvas (#statusChart) not found');
                return;
            }

            // Destroy existing chart if it exists
            if (window.statusChartInstance) {
                window.statusChartInstance.destroy();
            }

            // Get data from PHP variables
            const statusLabels = <?php echo json_encode($statusLabels); ?>;
            const statusCounts = <?php echo json_encode($statusCounts); ?>;
            const statusColors = <?php echo json_encode($statusColors); ?>;
            const statusPercentages = <?php echo json_encode($statusPercentages); ?>;

            // Create border colors from background colors
            const borderColors = statusColors.map(color => {
                return color.replace('0.8', '1');
            });

            const chartData = {
                labels: statusLabels,
                datasets: [{
                    label: 'Equipment Count',
                    data: statusCounts,
                    backgroundColor: statusColors,
                    borderColor: borderColors,
                    borderWidth: 2,
                    hoverOffset: 15,
                    borderRadius: 8
                }]
            };

            // Add percentage data to dataset
            chartData.datasets[0].percentageData = statusPercentages;

            // Create the chart
            window.statusChartInstance = new Chart(canvas, {
                type: 'doughnut',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                font: {
                                    size: 12
                                },
                                usePointStyle: true,
                                pointStyle: 'circle',
                                generateLabels: function(chart) {
                                    const data = chart.data;
                                    if (data.labels.length && data.datasets.length) {
                                        return data.labels.map(function(label, i) {
                                            const meta = chart.getDatasetMeta(0);
                                            const style = meta.controller.getStyle(i);
                                            const value = data.datasets[0].data[i];
                                            const percentage = data.datasets[0].percentageData?.[i] ||
                                                ((value / data.datasets[0].data.reduce((a, b) => a + b, 0)) * 100).toFixed(1);

                                            return {
                                                text: `${label}: ${value} (${percentage}%)`,
                                                fillStyle: style.backgroundColor,
                                                strokeStyle: style.borderColor,
                                                lineWidth: style.borderWidth,
                                                hidden: isNaN(data.datasets[0].data[i]) || meta.data[i].hidden,
                                                index: i
                                            };
                                        });
                                    }
                                    return [];
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return `${label}: ${value} items (${percentage}%)`;
                                }
                            }
                        }
                    },
                    cutout: '60%',
                    animation: {
                        animateScale: true,
                        animateRotate: true,
                        duration: 1000,
                        easing: 'easeOutQuart'
                    }
                },
                plugins: [{
                    id: 'centerText',
                    afterDraw: function(chart) {
                        const data = chart.data;
                        const dataset = data.datasets[0];

                        if (dataset.data && dataset.data.length > 0) {
                            // Check if we have real data (not just the "No Data" placeholder)
                            const isRealData = data.labels.length > 1 ||
                                (data.labels.length === 1 && data.labels[0] !== 'No Data');

                            if (isRealData) {
                                const width = chart.width;
                                const height = chart.height;
                                const ctx = chart.ctx;
                                const total = dataset.data.reduce((a, b) => a + b, 0);

                                ctx.restore();
                                ctx.font = "bold 16px 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
                                ctx.textBaseline = "middle";
                                ctx.fillStyle = "#233643";

                                const text = total.toString();
                                const textX = Math.round((width - ctx.measureText(text).width) / 2);
                                const textY = height / 2;

                                ctx.fillText(text, textX, textY - 10);

                                ctx.font = "12px 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
                                const subtitle = "Total Items";
                                const subtitleX = Math.round((width - ctx.measureText(subtitle).width) / 2);
                                ctx.fillText(subtitle, subtitleX, textY + 10);

                                ctx.save();
                            }
                        }
                    }
                }]
            });

            console.log('✅ Status chart created with real data');
        }

        // Function to refresh status chart data
        function refreshStatusChart() {
            const refreshBtn = event?.target?.closest('button');
            if (refreshBtn) {
                refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                refreshBtn.disabled = true;
            }

            toastr.info('Refreshing status data...');

            $.ajax({
                url: 'api/get_status_chart_data.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update the chart
                        if (window.statusChartInstance) {
                            window.statusChartInstance.data.labels = response.data.labels;
                            window.statusChartInstance.data.datasets[0].data = response.data.counts;
                            window.statusChartInstance.data.datasets[0].backgroundColor = response.data.colors;
                            window.statusChartInstance.data.datasets[0].borderColor = response.data.colors.map(color =>
                                color.replace('0.8', '1')
                            );
                            window.statusChartInstance.data.datasets[0].percentageData = response.data.percentages;
                            window.statusChartInstance.update();
                        }

                        // Update the table
                        if (response.data.table_html) {
                            $('#statusTableBody').html(response.data.table_html);
                        }

                        // Update total count
                        if (response.data.total) {
                            $('#totalEquipmentCount').text(response.data.total);
                        }

                        toastr.success('Status data refreshed!');
                    } else {
                        toastr.error(response.message || 'Failed to refresh data');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error refreshing chart:', error);
                    toastr.error('Error refreshing status data');
                },
                complete: function() {
                    if (refreshBtn) {
                        refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i>';
                        refreshBtn.disabled = false;
                    }
                }
            });
        }

        // Initialize the chart when page loads
        setTimeout(function() {
            initializeStatusChart();
        }, 500);

    }); // End of $(document).ready()
</script>