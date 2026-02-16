<?php
// dashboard.php - CLEAN FIXED VERSION

// ========== SESSION AND AUTHENTICATION ==========
// DO NOT start session here - let bootstrap.php handle it
// session_start(); // REMOVE THIS LINE

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ========== INCLUDES AND CONFIGURATION ==========
// Include bootstrap FIRST to handle session and authentication
if (file_exists('includes/bootstrap.php')) {
    require_once 'includes/bootstrap.php';
} else {
    // If bootstrap.php doesn't exist, start session manually
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once 'includes/database_fix.php';
    require_once 'includes/functions.php';
}

// Simple authentication check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit();
}

// Debug: Confirm user is logged in
error_log("Dashboard accessed by: " . ($_SESSION['username'] ?? 'Unknown') . " (ID: " . ($_SESSION['user_id'] ?? 'None') . ")");

// Set page variables
$current_page = basename(__FILE__);
$pageTitle = "Dashboard - aBility";
$showBreadcrumb = true;
$breadcrumbItems = [
    'Dashboard' => ''
];

// ========== DATABASE CONNECTION ==========
// Include database connection fix
require_once 'includes/database_fix.php';
require_once 'includes/functions.php';

// Get database connection
try {
    $db = new DatabaseFix();
    $conn = $db->getConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ========== HELPER FUNCTIONS ==========
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

// ========== DATA FETCHING ==========
// Get all unique item names for datalist
$allItems = [];
try {
    $itemsResult = $conn->query("
        SELECT DISTINCT item_name 
        FROM items 
        WHERE status NOT IN ('disposed', 'lost')
        ORDER BY item_name
        LIMIT 1000
    ");

    if ($itemsResult) {
        $allItems = $itemsResult->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    error_log("Error getting items for datalist: " . $e->getMessage());
}

// Get specific item count
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

// Get recent items
// Get recent items - show ALL items (remove the limit parameter)
$recentItems = [];
try {
    // Remove the limit or increase it significantly
    $recentItems = getRecentItems($conn, 500); // Increase to 500
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

// ========== INCLUDE HEADER AND DISPLAY PAGE ==========
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
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
    <p>You are logged in as: <?php echo htmlspecialchars($_SESSION['role']); ?></p>

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
        <div class="col-md-3">
            <div class="card mb-5 shadow border-0 overflow-hidden" style="border-radius: 16px; background: linear-gradient(135deg, #233643 0%, #2c4a5e 100%);">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center mb-3 mb-md-0">
                                
                                <div class="flex-grow-1">
                                    <h4 class="text-white mb-1 fw-bold">Quick Inventory Search</h4>
                                    <p class="text-white-50 mb-0" style="opacity: 0.85;">
                                        <i class="fas fa-boxes me-2"></i>Find any item in seconds
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <button type="button" class="btn btn-primary btn-sm text-white shadow-sm"
                                data-bs-toggle="modal" data-bs-target="#quickSearchModal"
                                style="border-radius: 5px; font-weight: 300; color: #233643;">
                                Search Now
                            </button>
                        </div>
                    </div>

                    <!-- Quick stats chips -->
                    <div class="mt-3 pt-2">
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-white bg-opacity-15 text-dark py-2 px-3 rounded-pill">
                                <i class="fas fa-tag me-1"></i> 500+ Items
                            </span>
                            <span class="badge bg-white bg-opacity-15 text-dark py-2 px-3 rounded-pill">
                                <i class="fas fa-check-circle me-1"></i> Real-time Stock
                            </span>
                            <span class="badge bg-white bg-opacity-15 text-dark py-2 px-3 rounded-pill">
                                <i class="fas fa-qrcode me-1"></i> Scan QR
                            </span>
                        </div>
                    </div>
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

            <!-- Quick Search Modal -->
            <div class="modal fade" id="quickSearchModal" tabindex="-1" aria-labelledby="quickSearchModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header text-white" style="background-color: #1f5e4f;">
                            <h5 class="modal-title" id="quickSearchModalLabel">
                                <i class="fas fa-search me-2"></i>Quick Inventory Search
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Search Input Section -->
                            <div class="row mb-4">
                                <div class="col-md-10 offset-md-1">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="fas fa-search text-muted"></i>
                                        </span>
                                        <input type="text" class="form-control border-start-0 border-end-0" id="quickSearchInput"
                                            placeholder="Search by name, serial number, brand, model..." autofocus>
                                        <button class="btn btn-primary" type="button" id="quickSearchBtn">
                                            <i class="fas fa-search me-1"></i> Search
                                        </button>
                                    </div>
                                    <div class="form-text text-center mt-2">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Type at least 2 characters to search
                                    </div>
                                </div>
                            </div>

                            <!-- Search Results Section (hidden by default) -->
                            <div id="quickSearchResults" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold mb-0">
                                        <i class="fas fa-boxes me-2"></i>
                                        Search Results (<span id="searchResultCount">0</span> found)
                                    </h6>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="clearQuickSearch()">
                                        <i class="fas fa-times"></i> Clear
                                    </button>
                                </div>
                                <div id="searchResultsContainer" style="max-height: 400px; overflow-y: auto;"></div>
                            </div>

                            <!-- Quick Stats Section (shown by default) -->
                            <div id="quickStatsSection">
                                <!-- Status Distribution Summary -->
                                <div class="row g-2 mb-4">
                                    <?php foreach ($statusData as $status): ?>
                                        <div class="col-md-4 col-6">
                                            <div class="card bg-light border-0">
                                                <div class="card-body py-2 px-3">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <span class="badge status-<?php echo strtolower($status['status']); ?>">
                                                                <?php echo ucfirst($status['status']); ?>
                                                            </span>
                                                        </div>
                                                        <div class="text-end">
                                                            <strong><?php echo $status['count']; ?></strong>
                                                            <small class="text-muted ms-1">(<?php echo $status['percentage']; ?>%)</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Quick Stats Overview -->
                                <div class="row g-3 mb-4">
                                    <div class="col-md-3 col-6">
                                        <div class="card text-center py-2 border-0 bg-light">
                                            <div class="small text-muted">Total Items</div>
                                            <div class="h4 mb-0 fw-bold" style="color: #233643;">
                                                <?php echo array_sum($statusCounts); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <div class="card text-center py-2 border-0 bg-light">
                                            <div class="small text-muted">Available</div>
                                            <div class="h4 mb-0 fw-bold text-success">
                                                <?php
                                                $available = 0;
                                                foreach ($statusData as $s) {
                                                    if (strtolower($s['status']) === 'available') $available = $s['count'];
                                                }
                                                echo $available;
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <div class="card text-center py-2 border-0 bg-light">
                                            <div class="small text-muted">In Use</div>
                                            <div class="h4 mb-0 fw-bold text-primary">
                                                <?php
                                                $inUse = 0;
                                                foreach ($statusData as $s) {
                                                    if (strtolower($s['status']) === 'in_use') $inUse = $s['count'];
                                                }
                                                echo $inUse;
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <div class="card text-center py-2 border-0 bg-light">
                                            <div class="small text-muted">Maintenance</div>
                                            <div class="h4 mb-0 fw-bold text-warning">
                                                <?php
                                                $maintenance = 0;
                                                foreach ($statusData as $s) {
                                                    if (strtolower($s['status']) === 'maintenance') $maintenance = $s['count'];
                                                }
                                                echo $maintenance;
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Popular Categories -->
                                <h6 class="fw-bold mb-2"><i class="fas fa-tags me-2"></i>Popular Categories</h6>
                                <div class="row g-2" id="popularCategories">
                                    <?php
                                    // Get top 6 categories
                                    $catQuery = $conn->query("
                            SELECT category, COUNT(*) as count 
                            FROM items 
                            WHERE category IS NOT NULL 
                            GROUP BY category 
                            ORDER BY count DESC 
                            LIMIT 6
                        ");
                                    if ($catQuery) {
                                        while ($cat = $catQuery->fetch_assoc()):
                                    ?>
                                            <div class="col-md-4 col-6">
                                                <div class="card bg-light border-0">
                                                    <div class="card-body py-2 px-3">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <small class="text-truncate"><?php echo htmlspecialchars($cat['category']); ?></small>
                                                            <span class="badge bg-secondary"><?php echo $cat['count']; ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                    <?php
                                        endwhile;
                                    }
                                    ?>
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

            <!-- <div class="card shadow">
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
            </div> -->
        </div>
        <div class="col-md-5">
            <style>
                /* Card flip specific styles - scoped to this column */
                .flip-card-wrapper {
                    display: flex;
                    flex-direction: row;
                    flex-wrap: wrap;
                    gap: 1rem;
                    margin-bottom: 1rem;
                    /* min-height: 420px; */
                }

                /* Make cards exactly 2 per row side by side */
                .flip-card-wrapper .card-flip {
                    width: calc(50% - 0.5rem);
                    height: 200px;
                    margin: 0;
                    perspective: 1500px;
                }

                .flip-card-wrapper .card-flip .content {
                    position: relative;
                    width: 100%;
                    height: 100%;
                    transform-style: preserve-3d;
                    transition: transform 0.6s cubic-bezier(0.75, 0, 0.85, 1);
                }

                .flip-card-wrapper .more {
                    display: none;
                }

                .flip-card-wrapper .more:checked~.content {
                    transform: rotateY(180deg);
                }

                .flip-card-wrapper .front,
                .flip-card-wrapper .back {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    backface-visibility: hidden;
                    transform-style: preserve-3d;
                    border-radius: 6px;
                }

                .flip-card-wrapper .front .inner,
                .flip-card-wrapper .back .inner {
                    height: 100%;
                    display: grid;
                    padding: 0.8em;
                    transform: translateZ(40px) scale(0.94);
                }

                .flip-card-wrapper .front {
                    background-color: #fff;
                    background-size: cover;
                    background-position: center center;
                }

                .flip-card-wrapper .front:after {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    display: block;
                    border-radius: 6px;
                    backface-visibility: hidden;
                    background: linear-gradient(40deg, rgba(67, 138, 243, 0.7), rgba(255, 242, 166, 0.7));
                }

                .flip-card-wrapper .front .inner {
                    grid-template-rows: 4fr 1fr 1fr 1fr;
                    justify-items: center;
                    align-items: center;
                }

                .flip-card-wrapper .front h2 {
                    grid-row: 2;
                    margin-bottom: 0.2em;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                    color: #fff;
                    font-weight: 500;
                    text-shadow: 0 0 4px rgba(0, 0, 0, 0.1);
                    font-size: 0.9rem;
                    text-align: center;
                    line-height: 1.2;
                    padding: 0 5px;
                }

                .flip-card-wrapper .front .event-date {
                    grid-row: 3;
                    color: rgba(255, 255, 255, 0.9);
                    font-size: 0.7rem;
                    display: flex;
                    flex-flow: row nowrap;
                    gap: 5px;
                    background: rgba(0, 0, 0, 0.3);
                    padding: 3px 8px;
                    border-radius: 12px;
                }

                .flip-card-wrapper .front .event-date i {
                    margin-right: 3px;
                }

                .flip-card-wrapper .back {
                    transform: rotateY(180deg);
                    background-color: #fff;
                    border: 2px solid rgb(240, 240, 240);
                }

                .flip-card-wrapper .back .inner {
                    grid-template-rows: 1fr 1fr 2fr 1fr;
                    grid-template-columns: repeat(2, 1fr);
                    grid-column-gap: 0.5em;
                    justify-items: center;
                    align-items: start;
                    padding: 0.6em;
                }

                .flip-card-wrapper .back .event-detail {
                    grid-row: 1;
                    grid-column: 1/-1;
                    display: flex;
                    justify-content: space-around;
                    width: 100%;
                    color: #355cc9;
                    font-size: 0.7rem;
                    font-weight: 600;
                }

                .flip-card-wrapper .back .event-detail span {
                    display: flex;
                    align-items: center;
                    gap: 4px;
                }

                .flip-card-wrapper .back .event-detail i {
                    color: #ff9f43;
                }

                .flip-card-wrapper .back .event-location {
                    grid-row: 2;
                    grid-column: 1/-1;
                    color: #355cc9;
                    font-size: 0.7rem;
                    font-weight: 600;
                    text-align: center;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    max-width: 100%;
                    padding: 0 5px;
                }

                .flip-card-wrapper .back .event-location i {
                    margin-right: 4px;
                    color: #ff6b6b;
                }

                .flip-card-wrapper .back .description {
                    grid-row: 3;
                    grid-column: 1/-1;
                    font-size: 0.65rem;
                    border-radius: 4px;
                    font-weight: 500;
                    line-height: 1.2;
                    overflow: auto;
                    color: #355cc9;
                    padding-right: 4px;
                    margin: 0;
                    max-height: 70px;
                    width: 100%;
                }

                .flip-card-wrapper .back .description p {
                    margin: 0 0 4px 0;
                }

                .flip-card-wrapper .back .event-price {
                    grid-row: 4;
                    grid-column: 1/2;
                    color: #28a745;
                    font-size: 0.75rem;
                    font-weight: 700;
                    justify-self: left;
                    padding-left: 5px;
                }

                .flip-card-wrapper .back .event-capacity {
                    grid-row: 4;
                    grid-column: 2/3;
                    color: #dc3545;
                    font-size: 0.7rem;
                    font-weight: 600;
                    justify-self: right;
                    padding-right: 5px;
                }

                .flip-card-wrapper .back .button {
                    grid-column: 1/-1;
                    justify-self: center;
                }

                .flip-card-wrapper .button {
                    grid-row: 4;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    font-weight: 600;
                    cursor: pointer;
                    display: block;
                    padding: 0 0.8em;
                    height: 1.8em;
                    line-height: 1.7em;
                    min-width: 2.5em;
                    background-color: transparent;
                    border: solid 1.5px #fff;
                    color: #fff;
                    border-radius: 3px;
                    text-align: center;
                    left: 50%;
                    backface-visibility: hidden;
                    transition: 0.2s ease-in-out;
                    text-shadow: 0 0 4px rgba(0, 0, 0, 0.3);
                    font-size: 0.7rem;
                }

                .flip-card-wrapper .button:hover {
                    background-color: #fff;
                    box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
                    text-shadow: none;
                    color: #355cc9;
                }

                .flip-card-wrapper .button.return {
                    line-height: 1.7em;
                    color: #355cc9;
                    border-color: #355cc9;
                    text-shadow: none;
                    font-size: 0.7rem;
                    padding: 0 0.6em;
                }

                .flip-card-wrapper .button.return i {
                    font-size: 0.7rem;
                }

                .flip-card-wrapper .button.return:hover {
                    background-color: #355cc9;
                    color: #fff;
                    box-shadow: none;
                }

                /* Pagination styles */
                .events-pagination {
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    gap: 15px;
                    margin-top: 25px;
                    padding: 10px 0;
                }

                .events-pagination .pagination-btn {
                    padding: 8px 16px;
                    background: #2e7ca7;
                    color: white;
                    border: none;
                    border-radius: 15px;
                    cursor: pointer;
                    font-size: 0.9rem;
                    font-weight: 500;
                    transition: all 0.3s ease;
                    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
                    text-decoration: none;
                    display: inline-block;
                }

                .events-pagination .pagination-btn:hover:not(.disabled) {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 10px rgba(53, 92, 201, 0.4);
                }

                .events-pagination .pagination-btn.disabled {
                    background: linear-gradient(135deg, #ccc, #999);
                    cursor: not-allowed;
                    transform: none;
                    box-shadow: none;
                    pointer-events: none;
                    opacity: 0.6;
                }

                .events-pagination .page-numbers {
                    display: flex;
                    gap: 8px;
                    align-items: center;
                }

                .events-pagination .page-number {
                    width: 35px;
                    height: 35px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    cursor: pointer;
                    font-weight: 600;
                    transition: all 0.3s ease;
                    border: 2px solid transparent;
                    text-decoration: none;
                    color: #355cc9;
                }

                .events-pagination .page-number:hover {
                    background: #e0e7ff;
                    color: #355cc9;
                }

                .events-pagination .page-number.active {
                    background: #2e7ca7;
                    color: white;
                    border-color: #2e7ca7;
                }

                .events-pagination .page-dots {
                    color: #666;
                    font-weight: 600;
                    padding: 0 2px;
                }

                /* Scrollbar styles - smaller for thumbnail */
                .flip-card-wrapper .description::-webkit-scrollbar {
                    width: 3px;
                }

                .flip-card-wrapper .description::-webkit-scrollbar-track {
                    background: #f1f1f1;
                }

                .flip-card-wrapper .description::-webkit-scrollbar-thumb {
                    background: #87a3e1;
                }

                .flip-card-wrapper .description::-webkit-scrollbar-thumb:hover {
                    background: #468cb6;
                }

                /* Loading spinner */
                .events-loading {
                    text-align: center;
                    padding: 80px 40px;
                    color: #355cc9;
                    width: 100%;
                }

                .events-loading i {
                    font-size: 2.5rem;
                    animation: spin 1s linear infinite;
                }

                .events-loading p {
                    margin-top: 10px;
                    color: #666;
                }

                @keyframes spin {
                    0% {
                        transform: rotate(0deg);
                    }

                    100% {
                        transform: rotate(360deg);
                    }
                }

                .no-events {
                    text-align: center;
                    padding: 80px 40px;
                    color: #999;
                    font-style: italic;
                    width: 100%;
                }

                .error-message {
                    text-align: center;
                    padding: 20px;
                    color: #dc3545;
                    background: #f8d7da;
                    border: 1px solid #f5c6cb;
                    border-radius: 4px;
                    margin: 10px 0;
                    width: 100%;
                }
            </style>
            <h1 class="titillium">Events</h1>
            <?php
            // Use the existing $conn (MySQLi) instead of PDO
            // $conn is already defined at the top of dashboard.php

            // Get parameters
            $page = isset($_GET['events_page']) ? (int)$_GET['events_page'] : 1;
            $per_page = 2; // Show 2 events per page
            $offset = ($page - 1) * $per_page;

            // First, let's check what columns exist in the events table
            $columns_query = "SHOW COLUMNS FROM events";
            $columns_result = $conn->query($columns_query);

            if (!$columns_result) {
                echo '<div class="error-message">Error checking columns: ' . $conn->error . '</div>';
            } else {
                $existing_columns = [];
                while ($column = $columns_result->fetch_assoc()) {
                    $existing_columns[] = $column['Field'];
                }

                // Debug: Show what columns exist (optional - remove after testing)
                echo '<!-- Existing columns: ' . implode(', ', $existing_columns) . ' -->';

                // Build the WHERE clause based on existing columns
                $where_clauses = [];

                // Check if status column exists
                if (in_array('status', $existing_columns)) {
                    $where_clauses[] = "status = 'active'";
                }

                // Check what date column exists
                $date_column = null;
                if (in_array('event_date', $existing_columns)) {
                    $date_column = 'event_date';
                } elseif (in_array('date', $existing_columns)) {
                    $date_column = 'date';
                } elseif (in_array('start_date', $existing_columns)) {
                    $date_column = 'start_date';
                }

                if ($date_column) {
                    $where_clauses[] = "$date_column >= CURDATE()";
                }

                // Build the WHERE part of the query
                $where_sql = '';
                if (!empty($where_clauses)) {
                    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
                }

                // Get total count of events
                $total_query = "SELECT COUNT(*) as total FROM events $where_sql";
                $total_result = $conn->query($total_query);

                if (!$total_result) {
                    echo '<div class="error-message">Error counting events: ' . $conn->error . '</div>';
                } else {
                    $total_row = $total_result->fetch_assoc();
                    $total_events = $total_row['total'];
                    $total_pages = ceil($total_events / $per_page);

                    // Ensure current page is valid
                    if ($page < 1) $page = 1;
                    if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
                    $offset = ($page - 1) * $per_page;

                    // Build the SELECT query dynamically based on existing columns
                    $select_fields = [];

                    // Map common column names to our expected field names
                    $field_mappings = [
                        'event_id' => ['event_id', 'id'],
                        'event_name' => ['event_name', 'name', 'title'],
                        'event_description' => ['event_description', 'description', 'details'],
                        'event_date' => ['event_date', 'date', 'start_date'],
                        'event_time' => ['event_time', 'time', 'start_time'],
                        'location' => ['location', 'place', 'venue_location'],
                        'venue' => ['venue', 'location_name'],
                        'price' => ['price', 'cost', 'fee'],
                        'capacity' => ['capacity', 'max_attendees', 'seats'],
                        'available_seats' => ['available_seats', 'seats_available', 'remaining'],
                        'event_image' => ['event_image', 'image', 'picture', 'photo'],
                        'category' => ['category', 'type', 'event_type']
                    ];

                    foreach ($field_mappings as $field => $possible_names) {
                        foreach ($possible_names as $name) {
                            if (in_array($name, $existing_columns)) {
                                if ($name == $field) {
                                    $select_fields[] = $field;
                                } else {
                                    $select_fields[] = "$name as $field";
                                }
                                break;
                            }
                        }
                    }

                    // If no fields found, use a simple SELECT *
                    if (empty($select_fields)) {
                        $select_clause = "*";
                    } else {
                        $select_clause = implode(", ", $select_fields);
                    }

                    // Fetch events with pagination
                    $events_query = "SELECT $select_clause FROM events $where_sql";

                    // Add ORDER BY if date column exists
                    if ($date_column) {
                        $events_query .= " ORDER BY $date_column ASC";
                        if (in_array('event_time', $existing_columns) || in_array('time', $existing_columns)) {
                            $time_column = in_array('event_time', $existing_columns) ? 'event_time' : 'time';
                            $events_query .= ", $time_column ASC";
                        }
                    }

                    // Add LIMIT
                    $events_query .= " LIMIT $per_page OFFSET $offset";

                    $events_result = $conn->query($events_query);

                    if (!$events_result) {
                        echo '<div class="error-message">Error fetching events: ' . $conn->error . '</div>';
                        echo '<div class="error-message" style="background-color: #e2e3e5; color: #383d41;">';
                        echo 'Query: ' . htmlspecialchars($events_query);
                        echo '</div>';
                    } else {
                        $events = [];
                        while ($row = $events_result->fetch_assoc()) {
                            $events[] = $row;
                        }
            ?>

                        <div id="eventsContainer">
                            <div class="flip-card-wrapper">
                                <?php if (count($events) > 0): ?>
                                    <?php
                                    $card_id = ($page - 1) * $per_page + 1;
                                    foreach ($events as $event):
                                        // Set default values for missing fields
                                        $event_name = $event['event_name'] ?? $event['name'] ?? $event['title'] ?? 'Unnamed Event';
                                        $event_description = $event['event_description'] ?? $event['description'] ?? $event['details'] ?? 'No description available';
                                        $event_date = $event['event_date'] ?? $event['date'] ?? $event['start_date'] ?? date('Y-m-d');
                                        $event_time = $event['event_time'] ?? $event['time'] ?? $event['start_time'] ?? '00:00:00';
                                        $location = $event['location'] ?? $event['place'] ?? $event['venue_location'] ?? 'Location TBD';
                                        $venue = $event['venue'] ?? $event['location_name'] ?? '';
                                        $price = $event['price'] ?? $event['cost'] ?? $event['fee'] ?? 0;
                                        $capacity = $event['capacity'] ?? $event['max_attendees'] ?? $event['seats'] ?? 0;
                                        $available_seats = $event['available_seats'] ?? $event['seats_available'] ?? $event['remaining'] ?? $capacity;
                                        $event_image = $event['event_image'] ?? $event['image'] ?? $event['picture'] ?? $event['photo'] ?? '';
                                        $category = $event['category'] ?? $event['type'] ?? $event['event_type'] ?? 'General';

                                        // Format event data
                                        $event_date_obj = new DateTime($event_date);
                                        $formatted_date = $event_date_obj->format('M d, Y');
                                        $day = $event_date_obj->format('d');
                                        $month = $event_date_obj->format('M');

                                        // Use default image if none provided
                                        if (empty($event_image)) {
                                            $event_image = 'https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?ixlib=rb-0.3.5&auto=format&fit=crop&w=1350&q=80';
                                        } else {
                                            $event_image = htmlspecialchars($event_image);
                                        }

                                        // Truncate description for front card
                                        $short_description = strlen($event_description) > 60
                                            ? substr($event_description, 0, 57) . '...'
                                            : $event_description;
                                    ?>
                                        <div class="card-flip">
                                            <input type="checkbox" id="event_card_<?php echo $card_id; ?>" class="more" aria-hidden="true">
                                            <div class="content">
                                                <!-- Front of card -->
                                                <div class="front" style="background-image: url('<?php echo $event_image; ?>')">
                                                    <div class="inner">
                                                        <h2><?php echo htmlspecialchars(substr($event_name, 0, 20) . (strlen($event_name) > 20 ? '...' : '')); ?></h2>
                                                        <div class="event-date">
                                                            <span><i class="far fa-calendar-alt"></i> <?php echo $day . ' ' . $month; ?></span>
                                                            <span><i class="far fa-clock"></i> <?php echo date('g:i A', strtotime($event_time)); ?></span>
                                                        </div>
                                                        <label for="event_card_<?php echo $card_id; ?>" class="button" aria-hidden="true">
                                                            Details
                                                        </label>
                                                    </div>
                                                </div>

                                                <!-- Back of card -->
                                                <div class="back">
                                                    <div class="inner">
                                                        <div class="event-detail">
                                                            <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($category); ?></span>
                                                            <span><i class="fas fa-users"></i> <?php echo $available_seats; ?>/<?php echo $capacity; ?></span>
                                                        </div>

                                                        <div class="event-location">
                                                            <i class="fas fa-map-marker-alt"></i>
                                                            <?php
                                                            $full_location = $location;
                                                            if (!empty($venue)) {
                                                                $full_location .= ', ' . $venue;
                                                            }
                                                            echo htmlspecialchars(substr($full_location, 0, 30));
                                                            ?>
                                                        </div>

                                                        <div class="description">
                                                            <p><?php echo htmlspecialchars($short_description); ?></p>
                                                        </div>

                                                        <div class="event-price">
                                                            <?php if ($price > 0): ?>
                                                                <i class="fas fa-ticket-alt"></i> $<?php echo number_format($price, 2); ?>
                                                            <?php else: ?>
                                                                <i class="fas fa-ticket-alt"></i> Free
                                                            <?php endif; ?>
                                                        </div>

                                                        <div class="event-capacity">
                                                            <?php
                                                            if ($capacity > 0) {
                                                                $availability_percent = ($available_seats / $capacity) * 100;
                                                                if ($availability_percent > 50): ?>
                                                                    <span style="color: #28a745;"><i class="fas fa-check-circle"></i> Available</span>
                                                                <?php elseif ($availability_percent > 20): ?>
                                                                    <span style="color: #ffc107;"><i class="fas fa-exclamation-circle"></i> Limited</span>
                                                                <?php else: ?>
                                                                    <span style="color: #dc3545;"><i class="fas fa-times-circle"></i> Almost Full</span>
                                                                <?php endif;
                                                            } else { ?>
                                                                <span style="color: #6c757d;"><i class="fas fa-info-circle"></i> No limit</span>
                                                            <?php } ?>
                                                        </div>

                                                        <label for="event_card_<?php echo $card_id; ?>" class="button return" aria-hidden="true">
                                                            <i class="fas fa-arrow-left"></i> Back
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php
                                        $card_id++;
                                    endforeach;
                                    ?>
                                <?php else: ?>
                                    <div class="no-events">
                                        <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                        <p>No upcoming events found</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <div class="events-pagination">
                                    <?php if ($page <= 1): ?>
                                        <span class="pagination-btn disabled"><i class="fas fa-chevron-left"></i> Prev</span>
                                    <?php else: ?>
                                        <a href="?events_page=<?php echo $page - 1; ?>" class="pagination-btn">
                                            <i class="fas fa-chevron-left"></i> Prev
                                        </a>
                                    <?php endif; ?>

                                    <div class="page-numbers">
                                        <?php
                                        // Show page numbers
                                        $start_page = max(1, $page - 2);
                                        $end_page = min($total_pages, $page + 2);

                                        if ($start_page > 1) {
                                            echo '<a href="?events_page=1" class="page-number">1</a>';
                                            if ($start_page > 2) {
                                                echo '<span class="page-dots">...</span>';
                                            }
                                        }

                                        for ($i = $start_page; $i <= $end_page; $i++) {
                                            $active_class = ($i == $page) ? 'active' : '';
                                            echo "<a href='?events_page=$i' class='page-number $active_class'>$i</a>";
                                        }

                                        if ($end_page < $total_pages) {
                                            if ($end_page < $total_pages - 1) {
                                                echo '<span class="page-dots">...</span>';
                                            }
                                            echo "<a href='?events_page=$total_pages' class='page-number'>$total_pages</a>";
                                        }
                                        ?>
                                    </div>

                                    <?php if ($page >= $total_pages): ?>
                                        <span class="pagination-btn disabled">Next <i class="fas fa-chevron-right"></i></span>
                                    <?php else: ?>
                                        <a href="?events_page=<?php echo $page + 1; ?>" class="pagination-btn">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <div style="text-align: center; margin-top: 5px; color: #666; font-size: 0.85rem;">
                                    Showing page <?php echo $page; ?> of <?php echo $total_pages; ?>
                                    (<?php echo count($events); ?> events)
                                </div>
                            <?php endif; ?>
                        </div>

            <?php
                    } // Close the else for events_result
                } // Close the else for total_result
            } // Close the else for columns_result
            ?>
        </div>
    </div>
    <!-- <div class="col-md-4">
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
        </div> -->

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
            <div class="modal-header bg- text-white" style="background-color: #234C6A; color: #fff;">
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
                                    <!-- Add this to your Quick Actions Modal, probably in the QR Code section -->
                                    <span id="qvItemId" style="display: none;"></span>
                                    <span id="qvItemCreatedAt" style="display: none;"></span>
                                    <span id="qvItemUpdatedAt" style="display: none;"></span>
                                    <span id="qvItemStorageLocation" style="display: none;"></span>
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

                            <!-- Details Table - FIXED: Change all view-* IDs to qv-* IDs -->
                            <table class="table table-sm table-borderless">
                                <tbody>
                                    <tr>
                                        <th width="35%" class="text-muted">Serial Number:</th>
                                        <td id="qvItemSerial" class="fw-bold"></td> <!-- CHANGED from viewSerialNumber -->
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Brand:</th>
                                        <td id="qvItemBrand"></td> <!-- CHANGED from viewBrand -->
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Model:</th>
                                        <td id="qvItemModel"></td> <!-- CHANGED from viewModel -->
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Department:</th>
                                        <td id="qvItemDepartment"></td> <!-- CHANGED from viewDepartment -->
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Stock Location:</th>
                                        <td id="qvItemLocation"></td> <!-- CHANGED from viewStockLocation -->
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Storage Location:</th>
                                        <td id="qvItemStorageLocation"></td> <!-- ADD THIS -->
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Accessories:</th>
                                        <td id="qvItemAccessories"></td> <!-- CHANGED from viewAccessoriesList -->
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Created:</th>
                                        <td id="qvItemCreatedAt"></td> <!-- CHANGED from viewCreatedAt -->
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Last Updated:</th>
                                        <td id="qvItemUpdatedAt"></td> <!-- CHANGED from viewUpdatedAt -->
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Right Column: Actions -->
                    <div class="col-md-4 bg-light">
                        <div class="p-4 h-100">
                            <h6 class="fw-bold mb-4">Actions</h6>

                            <!-- View Details Button -->
                            <div class="mb-3">
                                <a href="#" id="qvViewBtn" class="btn btn- w-100 py-3" target="_blank" style="background-color: #234C6A; color: #fff;">
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
                                <a href="#" id="qvEditBtn" class="btn btn- w-100 py-3" target="_blank" style="background-color: #697565; color: #fff;">
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
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Close
                </button>
                <button type="button" class="btn btn-outline-success" id="qvRefreshBtn">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
            </div>
        </div>
    </div>
</div>


<!-- View Item Details Modal -->
<div class="modal fade" id="viewItemModal" tabindex="-1" aria-labelledby="viewItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="viewItemModalLabel">
                    <i class="fas fa-eye me-2"></i>Item Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Left Column: Image and Basic Info -->
                    <div class="col-md-5">
                        <!-- Item Image -->
                        <div id="viewItemImage" class="text-center mb-4" style="display: none;">
                            <img src="" alt="Item Image" class="img-fluid rounded-3" style="max-height: 300px;">
                        </div>
                        <div id="viewNoImage" class="text-center mb-4">
                            <div class="image-placeholder bg-light rounded-3 p-5">
                                <i class="fas fa-box fa-4x text-muted"></i>
                                <div class="mt-2 text-muted">No image available</div>
                            </div>
                        </div>

                        <!-- QR Code -->
                        <div class="card border-info mb-3">
                            <div class="card-header bg-info text-white py-2">
                                <i class="fas fa-qrcode me-2"></i>QR Code
                            </div>
                            <div class="card-body text-center p-3" id="viewQRCode">
                                <!-- QR code will be populated here -->
                            </div>
                            <div class="card-footer bg-light d-flex justify-content-between">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="viewDownloadQRBtn">
                                    <i class="fas fa-download me-1"></i> Download
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="viewPrintQRBtn">
                                    <i class="fas fa-print me-1"></i> Print
                                </button>
                            </div>
                        </div>

                        <!-- Quick Stats -->
                        <div class="row g-2 mb-4">
                            <div class="col-6">
                                <div class="border rounded p-2 text-center">
                                    <div class="text-muted small">Quantity</div>
                                    <div class="fw-bold mt-1" id="viewQuantity">1</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2 text-center">
                                    <div class="text-muted small">Condition</div>
                                    <div class="fw-bold mt-1" id="viewCondition">Good</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Detailed Information -->
                    <div class="col-md-7">
                        <!-- Item Header -->
                        <div class="mb-4">
                            <h3 id="viewItemName" class="fw-bold mb-2" style="color: #233643;"></h3>
                            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                                <span class="badge bg-secondary" id="viewCategory"></span>
                                <span class="badge" id="viewItemStatusBadge"></span>
                                <span class="text-muted small">
                                    <i class="fas fa-hashtag me-1"></i>ID: <span id="viewItemId"></span>
                                </span>
                            </div>
                        </div>

                        <!-- Details Table -->
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless">
                                <tbody>
                                    <tr>
                                        <th width="35%" class="text-muted">Serial Number:</th>
                                        <td id="viewSerialNumber" class="fw-bold"></td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Brand:</th>
                                        <td id="viewBrand"></td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Model:</th>
                                        <td id="viewModel"></td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Department:</th>
                                        <td id="viewDepartment"></td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Stock Location:</th>
                                        <td id="viewStockLocation"></td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Storage Location:</th>
                                        <td id="viewStorageLocation"></td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Accessories:</th>
                                        <td id="viewAccessoriesList"></td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Created:</th>
                                        <td id="viewCreatedAt"></td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Last Updated:</th>
                                        <td id="viewUpdatedAt"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Specifications Section -->
                        <div class="card border-light mt-3" id="viewSpecificationsSection" style="display: none;">
                            <div class="card-header bg-light py-2">
                                <i class="fas fa-microchip me-2"></i>Specifications
                            </div>
                            <div class="card-body p-3">
                                <div id="viewSpecifications" class="text-muted"></div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="card border-light mt-3">
                            <div class="card-header bg-light py-2">
                                <i class="fas fa-align-left me-2"></i>Description
                            </div>
                            <div class="card-body p-3">
                                <div id="viewDescription" class="text-muted">
                                    No description available
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="card border-warning mt-3" id="viewNotesSection" style="display: none;">
                            <div class="card-header bg-warning text-dark py-2">
                                <i class="fas fa-sticky-note me-2"></i>Notes
                            </div>
                            <div class="card-body p-3">
                                <div id="viewNotes" class="text-muted"></div>
                            </div>
                        </div>

                        <!-- Accessories Section -->
                        <div class="card border-info mt-3" id="viewAccessoriesSection" style="display: none;">
                            <div class="card-header bg-info text-white py-2">
                                <i class="fas fa-puzzle-piece me-2"></i>Accessories
                            </div>
                            <div class="card-body p-3">
                                <div id="viewAccessoriesList" class="d-flex flex-wrap gap-1"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Close
                </button>
                <button type="button" class="btn btn-outline-primary" id="viewRefreshBtn">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
                <button type="button" class="btn btn-primary" id="viewEditBtn">
                    <i class="fas fa-edit me-1"></i> Edit Item
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Item Modal - COMPLETELY NEW -->
<div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: #1f5e4f;">
                <h5 class="modal-title" id="editItemModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Equipment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="editItemForm" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="editItemId" name="id">

                    <!-- Alert -->
                    <div class="alert alert-info py-2 mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>Fields marked with <span class="fw-bold text-danger">*</span> are required</small>
                    </div>

                    <!-- MAIN CONTENT ROW -->
                    <div class="row">
                        <!-- ========== LEFT COLUMN - ALL FORM FIELDS (SIMPLE mb-3) ========== -->
                        <div class="col-md-7">

                            <!-- SECTION 1: Basic Info -->
                            <h6 class="fw-bold mb-2" style="color: #1f5e4f;">BASIC INFORMATION</h6>
                            <hr class="mt-0 mb-3" style="border-color: #1f5e4f; opacity: 0.3;">

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="editItemName" class="form-label small fw-bold">Equipment Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm" id="editItemName" name="item_name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="editSerialNumber" class="form-label small fw-bold">Serial Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm" id="editSerialNumber" name="serial_number" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="editCategory" class="form-label small fw-bold">Category <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" id="editCategory" name="category" required>
                                            <option value="">Select Category</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="editStatus" class="form-label small fw-bold">Status</label>
                                        <select class="form-select form-select-sm" id="editStatus" name="status">
                                            <option value="available">Available</option>
                                            <option value="in_use">In Use</option>
                                            <option value="maintenance">Maintenance</option>
                                            <option value="reserved">Reserved</option>
                                            <option value="disposed">Disposed</option>
                                            <option value="lost">Lost</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="editCondition" class="form-label small fw-bold">Condition</label>
                                        <select class="form-select form-select-sm" id="editCondition" name="condition">
                                            <option value="new">New</option>
                                            <option value="excellent">Excellent</option>
                                            <option value="good">Good</option>
                                            <option value="fair">Fair</option>
                                            <option value="poor">Poor</option>
                                            <option value="damaged">Damaged</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- SECTION 2: Brand & Location -->
                            <h6 class="fw-bold mt-4 mb-2" style="color: #1f5e4f;">BRAND & LOCATION</h6>
                            <hr class="mt-0 mb-3" style="border-color: #1f5e4f; opacity: 0.3;">

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="editBrand" class="form-label small fw-bold">Brand</label>
                                        <input type="text" class="form-control form-control-sm" id="editBrand" name="brand">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="editModel" class="form-label small fw-bold">Model</label>
                                        <input type="text" class="form-control form-control-sm" id="editModel" name="model">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="editStockLocation" class="form-label small fw-bold">Stock Location</label>
                                        <input type="text" class="form-control form-control-sm" id="editStockLocation" name="stock_location">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="editStorageLocation" class="form-label small fw-bold">Storage Location</label>
                                        <input type="text" class="form-control form-control-sm" id="editStorageLocation" name="storage_location">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="editDepartment" class="form-label small fw-bold">Department</label>
                                        <select class="form-select form-select-sm" id="editDepartment" name="department">
                                            <option value="">Select Department</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- SECTION 3: Image Upload -->
                            <h6 class="fw-bold mt-4 mb-2" style="color: #1f5e4f;">EQUIPMENT IMAGE</h6>
                            <hr class="mt-0 mb-3" style="border-color: #1f5e4f; opacity: 0.3;">

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <div id="editCurrentImage" class="border rounded p-1 bg-light text-center" style="height: 70px; width: 70px;">
                                            <img src="" alt="Current" style="max-height: 60px; max-width: 60px;">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="editChangeImage" name="change_image">
                                        <label class="form-check-label small" for="editChangeImage">Change image</label>
                                    </div>

                                    <div id="editImageUploadSection" style="display: none;">
                                        <input type="file" class="form-control form-control-sm" id="editItemImage" name="item_image" accept="image/*">
                                        <small class="text-muted">Max 5MB</small>
                                        <div id="editImagePreview" class="mt-2" style="display: none;">
                                            <img src="" alt="Preview" style="max-height: 50px;">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SECTION 4: Notes & Tags -->
                            <h6 class="fw-bold mt-4 mb-2" style="color: #1f5e4f;">NOTES & TAGS</h6>
                            <hr class="mt-0 mb-3" style="border-color: #1f5e4f; opacity: 0.3;">

                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="editNotes" class="form-label small fw-bold">Notes</label>
                                        <textarea class="form-control form-control-sm" id="editNotes" name="notes" rows="2"></textarea>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="editTags" class="form-label small fw-bold">Tags</label>
                                        <input type="text" class="form-control form-control-sm" id="editTags" name="tags" placeholder="comma separated">
                                    </div>
                                </div>
                            </div>

                            <!-- SECTION 5: Accessories (THIS IS A CARD - as requested) -->
                            <h6 class="fw-bold mt-4 mb-2" style="color: #1f5e4f;">ACCESSORIES</h6>
                            <hr class="mt-0 mb-3" style="border-color: #1f5e4f; opacity: 0.3;">

                            <!-- This is the CARD for accessories -->
                            <div class="card border-info">
                                <div class="card-header bg-info text-white py-1">
                                    <small><i class="fas fa-puzzle-piece me-1"></i> Accessories</small>
                                </div>
                                <div class="card-body py-2">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <select class="form-select form-select-sm" id="editAccessories" name="accessories[]" multiple size="4">
                                                <option value="">-- No Accessories --</option>
                                            </select>
                                            <small class="text-muted">Hold Ctrl to select multiple</small>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="fw-bold">Selected:</small>
                                            <div id="editSelectedAccessories" class="border rounded p-1 bg-light" style="min-height: 80px; max-height: 80px; overflow-y: auto; font-size: 0.8rem;">
                                                <p class="text-muted mb-0">None</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Spacer -->
                            <div class="mb-3"></div>
                        </div>

                        <!-- ========== RIGHT COLUMN - QR CODE CARD ========== -->
                        <div class="col-md-5">
                            <!-- QR Code Card -->
                            <div class="card border-info">
                                <div class="card-header bg-info text-white text-center py-2">
                                    <i class="fas fa-qrcode me-1"></i> QR CODE
                                </div>
                                <div class="card-body text-center py-3">
                                    <div id="editQRCode" class="mb-2">
                                        <div class="text-center text-muted">
                                            <i class="fas fa-qrcode fa-4x mb-2"></i>
                                            <p class="small">QR code will appear here</p>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary w-100" id="editRegenerateQRBtn">
                                        <i class="fas fa-sync-alt me-1"></i> Regenerate
                                    </button>
                                </div>
                                <div class="card-footer bg-light text-center small py-1">
                                    <div>Created: <span id="editCreatedAt">N/A</span></div>
                                    <div>Updated: <span id="editUpdatedAt">N/A</span></div>
                                </div>
                            </div>

                            <!-- Item ID Card -->
                            <div class="card border-secondary mt-2">
                                <div class="card-body py-1">
                                    <div class="d-flex justify-content-between small">
                                        <span>Item ID:</span>
                                        <span class="fw-bold" id="editItemIdDisplay">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-danger" id="editDeleteBtn">
                        <i class="fas fa-trash me-1"></i> Delete
                    </button>
                    <button type="reset" class="btn btn-sm btn-outline-secondary" id="editResetBtn">
                        <i class="fas fa-undo me-1"></i> Reset
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-sm btn-success" id="editSubmitBtn">
                        <i class="fas fa-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
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
    // ========== GLOBAL VARIABLES ==========
    let originalEditModalHtml = '';
    let selectedItems = [];
    let currentFilters = {};
    let currentPage = 1;
    let totalPages = 1;
    let totalItems = 0;
    let itemsPerPage = 5;
    // ========== QUICK SEARCH FUNCTIONS ==========

    let searchTimeout = null;

    // Initialize quick search when modal opens
    $('#quickSearchModal').on('show.bs.modal', function() {
        clearQuickSearch();
        setTimeout(() => $('#quickSearchInput').focus(), 300);
    });

    // Search input handler with debounce
    $('#quickSearchInput').on('input', function() {
        const term = $(this).val().trim();

        if (searchTimeout) clearTimeout(searchTimeout);

        if (term.length < 2) {
            $('#quickSearchResults').hide();
            $('#quickStatsSection').show();
            return;
        }

        searchTimeout = setTimeout(performQuickSearch, 300);
    });

    // Search button click
    $('#quickSearchBtn').on('click', performQuickSearch);

    // Enter key handler
    $('#quickSearchInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            performQuickSearch();
        }
    });

    // Perform search
    function performQuickSearch() {
        const searchTerm = $('#quickSearchInput').val().trim();

        if (searchTerm.length < 2) return;

        // Show results section with loading
        $('#quickStatsSection').hide();
        $('#quickSearchResults').show();
        $('#searchResultsContainer').html(`
        <div class="text-center py-5">
            <div class="spinner-border text-primary mb-3" role="status"></div>
            <p class="text-muted">Searching...</p>
        </div>
    `);

        // Make AJAX call
        $.ajax({
            url: 'api/quick_search.php',
            method: 'GET',
            data: {
                q: searchTerm
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.items && response.items.length > 0) {
                    displaySearchResults(response.items);
                    $('#searchResultCount').text(response.items.length);
                } else {
                    $('#searchResultCount').text('0');
                    $('#searchResultsContainer').html(`
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-search fa-3x mb-3"></i>
                        <h6>No items found</h6>
                        <p class="small">Try different keywords</p>
                    </div>
                `);
                }
            },
            error: function() {
                $('#searchResultsContainer').html(`
                <div class="text-center py-5 text-danger">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <p>Search failed. Please try again.</p>
                </div>
            `);
            }
        });
    }

    // Display search results
    function displaySearchResults(items) {
        let html = '<div class="list-group">';

        items.forEach(item => {
            const statusClass = `status-${item.status.toLowerCase()}`;

            html += `
            <div class="list-group-item list-group-item-action" onclick="quickViewItem(${item.id})" style="cursor: pointer;">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center">
                            ${item.image ? 
                                `<img src="${item.image}" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">` : 
                                `<div class="bg-light rounded me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="fas fa-box text-muted"></i>
                                </div>`
                            }
                            <div>
                                <strong class="d-block">${escapeHtml(item.item_name)}</strong>
                                <small class="text-muted">
                                    <i class="fas fa-barcode me-1"></i>${escapeHtml(item.serial_number || 'N/A')}
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex justify-content-end align-items-center">
                            <div class="text-end me-3">
                                <span class="status-badge ${statusClass}">${escapeHtml(item.status)}</span>
                                <br>
                                <small class="text-muted">
                                    <i class="fas fa-map-marker-alt me-1"></i>${escapeHtml(item.stock_location || 'N/A')}
                                </small>
                            </div>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </div>
                    </div>
                </div>
                ${item.description ? `<small class="text-muted d-block mt-2">${escapeHtml(item.description.substring(0, 100))}...</small>` : ''}
            </div>
        `;
        });

        html += '</div>';
        $('#searchResultsContainer').html(html);
    }

    // Quick view item from search
    function quickViewItem(itemId) {
        $('#quickSearchModal').modal('hide');

        setTimeout(() => {
            const quickBtn = $(`.quick-view-btn[data-item-id="${itemId}"]`);
            if (quickBtn.length) {
                quickBtn.click();
            } else {
                openViewItemModal(itemId);
            }
        }, 300);
    }

    // Clear search
    function clearQuickSearch() {
        $('#quickSearchInput').val('');
        $('#quickSearchResults').hide();
        $('#quickStatsSection').show();
        if (searchTimeout) clearTimeout(searchTimeout);
    }

    // Escape HTML helper
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    // ========== HELPER FUNCTIONS (Defined first) ==========

    function formatDateTime(dt) {
        if (!dt) return 'N/A';
        try {
            const date = new Date(dt);
            if (isNaN(date.getTime())) return dt;
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch {
            return dt;
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ========== FETCH DATA FUNCTIONS ==========

    function fetchItemData(itemId) {
        return new Promise(function(resolve, reject) {
            console.log('Fetching item data for ID:', itemId);

            $.ajax({
                url: 'api/get_item.php',
                method: 'GET',
                data: {
                    id: itemId
                },
                dataType: 'json',
                timeout: 10000,
                success: function(response) {
                    console.log('API response received:', response);
                    if (response.success && response.data) {
                        resolve(response.data);
                    } else {
                        reject(new Error(response.message || 'Failed to load item'));
                    }
                },
                error: function(xhr, status, error) {
                    reject(new Error('Network error: ' + error));
                }
            });
        });
    }

    // ========== DROPDOWN LOADING FUNCTIONS ==========

    function loadCategoriesDropdown() {
        return new Promise(resolve => {
            $.ajax({
                url: 'api/get_categories.php',
                method: 'GET',
                success: function(response) {
                    const select = $('#editCategory');
                    select.empty().append('<option value="">Select Category</option>');
                    if (response.success && response.categories) {
                        response.categories.forEach(c => select.append(`<option value="${c.id}">${c.name}</option>`));
                    }
                    resolve();
                },
                error: () => resolve()
            });
        });
    }

    function loadDepartmentsDropdown() {
        return new Promise(resolve => {
            $.ajax({
                url: 'api/get_departments.php',
                method: 'GET',
                success: function(response) {
                    const select = $('#editDepartment');
                    select.empty().append('<option value="">Select Department</option>');
                    if (response.success && response.departments) {
                        response.departments.forEach(d => select.append(`<option value="${d.id}">${d.name}</option>`));
                    }
                    resolve();
                },
                error: () => resolve()
            });
        });
    }

    function loadAccessoriesDropdown() {
        return new Promise(resolve => {
            $.ajax({
                url: 'api/get_accessories.php',
                method: 'GET',
                success: function(response) {
                    const select = $('#editAccessories');
                    select.empty().append('<option value="">-- No Accessories --</option>');
                    if (response.success && response.accessories) {
                        response.accessories.forEach(a => select.append(`<option value="${a.id}">${a.name}</option>`));
                    }
                    resolve();
                },
                error: () => resolve()
            });
        });
    }

    // ========== ACCESSORY DISPLAY FUNCTIONS ==========

    function updateEditSelectedAccessories() {
        const selected = $('#editAccessories option:selected');
        const container = $('#editSelectedAccessories');

        if (selected.length === 0 || !selected.val()) {
            container.html('<p class="text-muted small mb-0">None selected</p>');
            return;
        }

        let html = '';
        selected.each(function() {
            if ($(this).val()) {
                html += `<span class="badge bg-info me-1 mb-1">${$(this).text()}</span>`;
            }
        });
        container.html(html || '<p class="text-muted small mb-0">None selected</p>');
    }

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
    }

    // ========== QR CODE FUNCTIONS ==========

    function updateEditQRCode(qrCode) {
        const container = $('#editQRCode');
        if (qrCode && qrCode !== '') {
            container.html(`<img src="${qrCode}" alt="QR Code" class="img-fluid" style="max-width: 150px;">`);
        } else {
            container.html('<p class="text-muted mb-0">No QR Code</p>');
        }
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

    function downloadSingleQRCode(qrUrl, itemName, serial) {
        if (!qrUrl) {
            toastr.error('No QR code available to download');
            return;
        }

        const link = document.createElement('a');
        link.href = qrUrl;

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

    // ========== CHART FUNCTIONS ==========

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

        // Get data from PHP variables (these need to be passed from PHP)
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

                    if (response.data.table_html) {
                        $('#statusTableBody').html(response.data.table_html);
                    }

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

    // ========== CLOCK & CALENDAR FUNCTIONS ==========

    function updateDateTime() {
        const now = new Date();

        // Get timezone name
        const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        const timezoneParts = timezone.split('/');
        const timezoneDisplay = timezoneParts[timezoneParts.length - 1].replace(/_/g, ' ');

        const timezoneEl = document.getElementById('timezone');
        if (timezoneEl) timezoneEl.textContent = timezoneDisplay;

        // Format day of week
        const dayOfWeek = now.toLocaleDateString('en-US', {
            weekday: 'long'
        });
        const dayEl = document.getElementById('digitalDay');
        if (dayEl) dayEl.textContent = dayOfWeek;

        // Format date
        const formattedDate = now.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
        const dateEl = document.getElementById('digitalDate');
        if (dateEl) dateEl.textContent = formattedDate;

        // Format full date for today display
        const todayDate = now.toLocaleDateString('en-US', {
            month: 'long',
            day: 'numeric',
            year: 'numeric'
        });
        const todayEl = document.getElementById('todayDate');
        if (todayEl) todayEl.textContent = todayDate;

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

        const timeEl = document.getElementById('digitalTime');
        if (timeEl) timeEl.textContent = `${hours}:${minutes}:${seconds}`;

        const ampmEl = document.getElementById('amPm');
        if (ampmEl) ampmEl.textContent = ampm;

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
        const monthEl = document.getElementById('currentMonth');
        if (monthEl) monthEl.textContent = `${monthNames[currentMonth]} ${currentYear}`;

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

        const calendarEl = document.getElementById('monthCalendar');
        if (calendarEl) calendarEl.innerHTML = calendarHTML;
    }

    // Initialize and start the clock
    function startClock() {
        updateDateTime();
        setInterval(updateDateTime, 1000);
    }


    function downloadAllQRCodes() {
        const rows = document.querySelectorAll('#recentItemsTable tbody tr');

        if (rows.length === 0) {
            toastr.error('No items found in the table');
            return;
        }

        toastr.info(`Found ${rows.length} items. Checking for QR codes...`);

        let qrCount = 0;
        const promises = [];

        rows.forEach((row, index) => {
            const quickBtn = row.querySelector('.quick-view-btn');
            if (!quickBtn) return;

            const itemName = quickBtn.dataset.itemName || `Item_${index + 1}`;
            const serial = quickBtn.dataset.itemSerial || '';
            const qrCode = quickBtn.dataset.qrCode || '';

            if (!qrCode || qrCode === '' || qrCode === 'pending') {
                console.log(`No QR code for ${itemName}`);
                return;
            }

            qrCount++;

            promises.push(new Promise((resolve) => {
                setTimeout(() => {
                    try {
                        const safeName = itemName
                            .replace(/[<>:"/\\|?*]/g, '')
                            .replace(/\s+/g, '_')
                            .substring(0, 50);

                        const safeSerial = serial.replace(/[^a-z0-9]/gi, '_');
                        const filename = `QR_${safeName}_${safeSerial || 'item'}.png`;

                        const link = document.createElement('a');
                        link.href = qrCode;
                        link.download = filename;

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

        Promise.all(promises).then(() => {
            setTimeout(() => {
                toastr.success(`Successfully downloaded ${qrCount} QR codes!`);
            }, 500);
        });
    }

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
                        setTimeout(() => location.reload(), 1500);
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

    function generateQRCodeForItem(itemId, itemName) {
        generateQRCodeFromQuickView(itemId, itemName);
    }

    function regenerateQRCode(itemId, itemName) {
        if (!itemId) return;

        if (!confirm(`Regenerate QR code for "${itemName}"? This will replace the existing QR code.`)) {
            return;
        }

        $('#editRegenerateQRBtn').html('<span class="spinner-border spinner-border-sm me-1"></span> Generating...');
        $('#editRegenerateQRBtn').prop('disabled', true);

        $.ajax({
            url: 'api/generate_qr.php',
            method: 'POST',
            data: {
                id: itemId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success('QR code regenerated successfully');
                    updateEditQRCode(response.qr_code);
                } else {
                    toastr.error(response.message || 'Failed to regenerate QR code');
                }
            },
            error: function() {
                toastr.error('Error regenerating QR code');
            },
            complete: function() {
                $('#editRegenerateQRBtn').html('<i class="fas fa-sync-alt me-1"></i> Regenerate QR Code');
                $('#editRegenerateQRBtn').prop('disabled', false);
            }
        });
    }

    // ========== EDIT MODAL FUNCTIONS ==========

    function saveOriginalEditModalHtml() {
        originalEditModalHtml = $('#editItemModal .modal-body').html();
        console.log('Original edit modal HTML saved');
    }

    function resetEditModal() {
        delete window.currentEditItemData;
        $('#editItemForm')[0].reset();
        $('#editCurrentImage').hide();
        $('#editImagePreview').hide();
        $('#editImageUploadSection').hide();
        $('#editChangeImage').prop('checked', false);
        $('#editSelectedAccessories').html('<p class="text-muted mb-0">No accessories selected</p>');
    }

    function setupEditFormHandlers(data) {
        $('#editChangeImage').off('change').on('change', function() {
            $('#editImageUploadSection').toggle($(this).is(':checked'));
        });

        $('#editItemImage').off('change').on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => {
                    $('#editImagePreview').show().find('img').attr('src', e.target.result);
                };
                reader.readAsDataURL(file);
            }
        });

        $('#editAccessories').off('change').on('change', updateEditSelectedAccessories);

        $('#editResetBtn').off('click').on('click', function() {
            if (confirm('Reset all changes?')) {
                populateEditItemModal(data);
            }
        });

        $('#editDeleteBtn').off('click').on('click', function() {
            if (confirm(`Delete "${data.item_name}"?`)) {
                deleteItem(data.id);
            }
        });

        $('#editRegenerateQRBtn').off('click').on('click', function() {
            regenerateQRCode(data.id, data.item_name);
        });
    }

    function populateEditItemModal(data) {
        console.log('Populating edit modal with:', data);

        $('#editItemModalLabel').html(`<i class="fas fa-edit me-2"></i>Edit: ${data.item_name || 'Item'}`);
        $('#editItemId').val(data.id || '');
        $('#editItemIdDisplay').text(data.id || '-');

        // Basic Information
        $('#editItemName').val(data.item_name || '');
        $('#editSerialNumber').val(data.serial_number || '');
        $('#editStatus').val(data.status || 'available');
        $('#editCondition').val(data.condition || 'good');

        // Brand & Model
        $('#editBrand').val(data.brand || '');
        $('#editModel').val(data.model || '');

        // Location
        $('#editStockLocation').val(data.stock_location || '');
        $('#editStorageLocation').val(data.storage_location || '');

        // Quantity
        $('#editQuantity').val(data.quantity || 1);

        // Description, Specifications, Notes
        $('#editDescription').val(data.description || '');
        $('#editSpecifications').val(data.specifications || '');
        $('#editNotes').val(data.notes || '');

        // Tags
        $('#editTags').val(data.tags || '');

        // System Info
        $('#editCreatedAt').text(formatDateTime(data.created_at));
        $('#editUpdatedAt').text(formatDateTime(data.updated_at));
        $('#editLastScanned').text(data.last_scanned ? formatDateTime(data.last_scanned) : 'Never');

        // Load dropdowns and set values
        loadCategoriesDropdown().then(function() {
            if (data.category) $('#editCategory').val(data.category);
        });

        loadDepartmentsDropdown().then(function() {
            if (data.department) $('#editDepartment').val(data.department);
        });

        loadAccessoriesDropdown().then(function() {
            if (data.accessory_ids && data.accessory_ids.length) {
                $('#editAccessories').val(data.accessory_ids);
            }
            updateEditSelectedAccessories();
        });

        // Image
        if (data.image && data.image !== '') {
            $('#editCurrentImage img').attr('src', data.image);
        } else {
            $('#editCurrentImage').html('<p class="text-muted small mb-0">No image</p>');
        }

        // QR Code
        updateEditQRCode(data.qr_code);

        // Setup event handlers
        setupEditFormHandlers(data);
    }

    function openEditItemModal(itemId) {
        console.log('openEditItemModal called with ID:', itemId);

        if (!itemId) {
            toastr.error('Invalid item ID');
            return;
        }

        const modalBody = $('#editItemModal .modal-body');

        // Show loading
        modalBody.html(`
            <div class="text-center py-5">
                <div class="spinner-border text-warning mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-muted">Loading item details...</p>
            </div>
        `);

        // Show modal
        const editModal = new bootstrap.Modal(document.getElementById('editItemModal'));
        editModal.show();

        // Fetch item data
        fetchItemData(itemId).then(function(data) {
            console.log('Edit data received:', data);
            modalBody.html(originalEditModalHtml);
            populateEditItemModal(data);
        }).catch(function(error) {
            console.error('Failed to load item:', error);
            modalBody.html(`
                <div class="text-center py-5">
                    <i class="fas fa-exclamation-triangle text-danger fa-3x mb-3"></i>
                    <h5>Error Loading Item</h5>
                    <p class="text-muted">${error.message}</p>
                    <button class="btn btn-primary" onclick="openEditItemModal(${itemId})">
                        <i class="fas fa-sync-alt me-1"></i> Retry
                    </button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            `);
        });
    }

    // ========== VIEW MODAL FUNCTIONS ==========

    function updateViewStatusBadges(data) {
        // Condition badge
        const conditionBadge = $('#viewCondition');
        conditionBadge.removeClass().addClass('badge');
        const condition = (data.condition || 'good').toLowerCase();
        switch (condition) {
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
        const status = (data.status || 'available').toLowerCase();
        switch (status) {
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
    }

    function populateViewItemModal(data) {
        console.log('Populating view modal with:', data);

        $('#viewItemModalLabel').html(`<i class="fas fa-eye me-2"></i>View: ${data.item_name || 'Item'}`);
        $('#viewItemId').text(data.id || '');

        // Item Image
        if (data.image && data.image !== '' && data.image !== null) {
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
        $('#viewBrandModel').text(data.brand_model || 'N/A');

        // Status & Condition
        updateViewStatusBadges(data);

        // Location
        $('#viewStockLocation').text(data.stock_location || 'Not Set');
        $('#viewStorageLocation').text(data.storage_location || 'Not Set');
        $('#viewCurrentLocation').text(data.current_location || 'Not Set');

        // Department
        $('#viewDepartment').text(data.department || 'Not Set');

        // Quantity
        $('#viewQuantity').text(data.quantity || 1);

        // Dates
        $('#viewCreatedAt').text(formatDateTime(data.created_at) || 'N/A');
        $('#viewUpdatedAt').text(formatDateTime(data.updated_at) || 'N/A');
        $('#viewLastScanned').text(data.last_scanned ? formatDateTime(data.last_scanned) : 'Never');

        // Description & Notes
        $('#viewDescription').text(data.description || 'No description available');
        $('#viewSpecifications').text(data.specifications || 'No specifications available');
        $('#viewNotes').text(data.notes || 'No notes available');

        // Tags
        if (data.tags && data.tags.trim() !== '') {
            const tags = data.tags.split(',').map(tag => tag.trim());
            let tagsHtml = '';
            tags.forEach(tag => {
                tagsHtml += `<span class="badge bg-secondary me-1 mb-1">${escapeHtml(tag)}</span>`;
            });
            $('#viewTags').html(tagsHtml);
            $('#viewTagsSection').show();
        } else {
            $('#viewTagsSection').hide();
        }

        // Accessories
        if (data.accessories && data.accessories.length > 0) {
            let accessoriesHtml = '<div class="d-flex flex-wrap gap-1">';
            data.accessories.forEach(acc => {
                accessoriesHtml += `<span class="badge bg-info p-2 m-1">${escapeHtml(acc.name)}</span>`;
            });
            accessoriesHtml += '</div>';
            $('#viewAccessoriesList').html(accessoriesHtml);
            $('#viewAccessoriesSection').show();
        } else {
            $('#viewAccessoriesSection').hide();
            $('#viewAccessoriesList').html('<p class="text-muted mb-0">No accessories</p>');
        }

        window.currentViewItemData = data;
    }

    function showViewItemError(itemId, error) {
        $('#viewItemName').text('Error Loading Item');
        $('#viewSerialNumber').text('N/A');
        $('#viewCategory').text('N/A');
        $('#viewBrand').text('N/A');
        $('#viewModel').text('N/A');
        $('#viewQuantity').text('-');
        $('#viewStockLocation').text('N/A');
        $('#viewStorageLocation').text('N/A');
        $('#viewDepartment').text('N/A');
        $('#viewCreatedAt').text('N/A');
        $('#viewUpdatedAt').text('N/A');
        $('#viewDescription').text('Failed to load item details. Please try again.');
        $('#viewCondition').text('Unknown');
        $('#viewItemStatusBadge').text('Unknown');
        $('#viewQRCode').html(`
            <div class="text-center py-3">
                <i class="fas fa-exclamation-triangle text-danger fa-3x mb-2"></i>
                <div class="text-danger">Failed to load QR code</div>
                <button class="btn btn-sm btn-primary mt-2" onclick="openViewItemModal(${itemId})">
                    <i class="fas fa-sync-alt me-1"></i> Retry
                </button>
            </div>
        `);
        toastr.error('Failed to load item details for viewing');
    }

    function openViewItemModal(itemId) {
        console.log('Opening view modal for item ID:', itemId);

        const quickModal = bootstrap.Modal.getInstance(document.getElementById('quickActionsModal'));
        if (quickModal) quickModal.hide();

        const viewModal = new bootstrap.Modal(document.getElementById('viewItemModal'));

        $('#viewItemImage').hide();
        $('#viewNoImage').hide();
        $('#viewQRCode').html(`
            <div class="text-center py-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="mt-2">Loading QR code...</div>
            </div>
        `);

        $('#viewItemName').text('Loading...');
        $('#viewSerialNumber').text('Loading...');
        $('#viewCategory').text('Loading...');
        $('#viewBrand').text('Loading...');
        $('#viewModel').text('Loading...');
        $('#viewQuantity').text('-');
        $('#viewStockLocation').text('Loading...');
        $('#viewStorageLocation').text('Loading...');
        $('#viewDepartment').text('Loading...');
        $('#viewCreatedAt').text('Loading...');
        $('#viewUpdatedAt').text('Loading...');
        $('#viewDescription').text('Loading...');
        $('#viewCondition').text('Loading...');
        $('#viewItemStatusBadge').text('Loading...');

        viewModal.show();

        fetchItemData(itemId).then(function(data) {
            console.log('Item data received:', data);
            populateViewItemModal(data);
        }).catch(function(error) {
            console.error('Failed to load item:', error);
            showViewItemError(itemId, error);
        });
    }

    // ========== QUICK ACTIONS MODAL FUNCTIONS ==========

    function populateQuickViewModal(data) {
        $('#qvItemId').text(data.id || '');
        $('#qvItemName').text(data.item_name || '');
        $('#qvItemCategory').text(data.category || 'N/A');
        $('#qvItemSerial').text(data.serial_number || 'N/A');
        $('#qvItemQuantity').text(data.quantity || 1);
        $('#qvItemLocation').text(data.stock_location || 'N/A');
        $('#qvItemDepartment').text(data.department || 'N/A');
        $('#qvItemBrand').text(data.brand || 'N/A');
        $('#qvItemModel').text(data.model || 'N/A');
        $('#qvItemStorageLocation').text(data.storage_location || 'N/A');
        $('#qvItemAccessories').text(data.accessories || 'None');
        $('#qvItemCreatedAt').text(formatDateTime(data.created_at) || 'N/A');
        $('#qvItemUpdatedAt').text(formatDateTime(data.updated_at) || 'N/A');

        $('#qrItemName').text(data.item_name || '');

        // Status badge
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

        // Condition badge
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

        // QR Code
        const qrContainer = $('#qvQRCode');
        qrContainer.empty();

        if (data.qr_code && data.qr_code !== '' && data.qr_code !== 'pending') {
            qrContainer.html(`
                <div class="text-center">
                    <img src="${data.qr_code}" alt="QR Code" style="width: 100px; height: 100px;" class="img-fluid border rounded p-1">
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

            $('.download-qr-btn').off('click').on('click', function() {
                downloadSingleQRCode(data.qr_code, data.item_name, data.serial_number);
            });

            $('.view-qr-btn').off('click').on('click', function() {
                window.open(data.qr_code, '_blank');
            });

        } else if (data.qr_code === 'pending') {
            qrContainer.html(`
                <div class="text-center">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
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

        // Action buttons
        $('#qvCopySerialBtn').off('click').on('click', function() {
            navigator.clipboard.writeText(data.serial_number || '');
            toastr.success('Serial number copied to clipboard');
        });

        $('#qvPrintBtn').off('click').on('click', function() {
            window.open('items/print.php?id=' + data.id, '_blank');
        });

        $('#qvViewBtn').off('click').on('click', function(e) {
            e.preventDefault();
            const quickModal = bootstrap.Modal.getInstance(document.getElementById('quickActionsModal'));
            if (quickModal) quickModal.hide();
            setTimeout(() => openViewItemModal(data.id), 300);
        });

        $('#qvEditBtn').off('click').on('click', function(e) {
            e.preventDefault();
            const quickModal = bootstrap.Modal.getInstance(document.getElementById('quickActionsModal'));
            if (quickModal) quickModal.hide();
            setTimeout(() => openEditItemModal(data.id), 300);
        });

        $('[data-status]').off('click').on('click', function() {
            const newStatus = $(this).data('status');
            updateItemStatus(data.id, newStatus);
        });

        window.currentItemData = data;
    }

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

    // ========== CRUD OPERATIONS ==========

    function submitEditItemForm() {
        // Validate required fields
        const itemName = $('#editItemName').val().trim();
        const serialNumber = $('#editSerialNumber').val().trim();
        const category = $('#editCategory').val();

        if (!itemName || !serialNumber || !category) {
            toastr.error('Please fill all required fields (Item Name, Serial Number, Category)');
            return;
        }

        const formData = new FormData();
        formData.append('id', $('#editItemId').val());
        formData.append('item_name', itemName);
        formData.append('serial_number', serialNumber);
        formData.append('category', category);
        formData.append('status', $('#editStatus').val());
        formData.append('condition', $('#editCondition').val());
        formData.append('stock_location', $('#editStockLocation').val());
        formData.append('storage_location', $('#editStorageLocation').val());
        formData.append('department', $('#editDepartment').val());
        formData.append('brand', $('#editBrand').val());
        formData.append('model', $('#editModel').val());
        formData.append('quantity', $('#editQuantity').val());
        formData.append('description', $('#editDescription').val());
        formData.append('specifications', $('#editSpecifications').val());
        formData.append('notes', $('#editNotes').val());
        formData.append('tags', $('#editTags').val());

        const accessories = $('#editAccessories').val();
        if (accessories && accessories.length > 0) {
            formData.append('accessories', JSON.stringify(accessories));
        }

        const changeImage = $('#editChangeImage').is(':checked');
        if (changeImage) {
            const imageFile = $('#editItemImage')[0].files[0];
            if (imageFile) {
                formData.append('image', imageFile);
            } else {
                formData.append('remove_image', '1');
            }
        }

        const submitBtn = $('#editSubmitBtn');
        const originalText = submitBtn.html();
        submitBtn.html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');
        submitBtn.prop('disabled', true);

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
                    const editModal = bootstrap.Modal.getInstance(document.getElementById('editItemModal'));
                    if (editModal) editModal.hide();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toastr.error(response.message || 'Failed to update item');
                }
            },
            error: function(xhr, status, error) {
                toastr.error('Error updating item: ' + error);
            },
            complete: function() {
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            }
        });
    }

    function deleteItem(itemId) {
        if (!itemId) return;

        $('#editDeleteBtn').html('<span class="spinner-border spinner-border-sm me-1"></span> Deleting...');
        $('#editDeleteBtn').prop('disabled', true);

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
                    const editModal = bootstrap.Modal.getInstance(document.getElementById('editItemModal'));
                    if (editModal) editModal.hide();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toastr.error(response.message || 'Failed to delete item');
                }
            },
            error: function() {
                toastr.error('Error deleting item');
            },
            complete: function() {
                $('#editDeleteBtn').html('<i class="fas fa-trash me-1"></i> Delete Item');
                $('#editDeleteBtn').prop('disabled', false);
            }
        });
    }

    // ========== DATATABLE FUNCTIONS ==========

    function initializeDataTable() {
        if ($('#recentItemsTable').length === 0) return;

        if ($.fn.DataTable.isDataTable('#recentItemsTable')) {
            $('#recentItemsTable').DataTable().destroy();
        }

        try {
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
                    [1, 'asc']
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
                processing: false,
                serverSide: false,
                ajax: null
            });

            console.log('✅ DataTable initialized successfully');
            return dataTable;

        } catch (error) {
            console.error('❌ DataTable initialization error:', error);
            $('#recentItemsTable').addClass('table table-bordered table-hover table-striped');
            return null;
        }
    }

    function refreshDataTable() {
        if ($.fn.DataTable.isDataTable('#recentItemsTable')) {
            const table = $('#recentItemsTable').DataTable();
            if (table.settings()[0].oFeatures.bServerSide) {
                table.ajax.reload(null, false);
            } else {
                table.destroy();
                setTimeout(initializeDataTable, 300);
            }
        } else {
            setTimeout(() => location.reload(), 500);
        }
    }

    // ========== FORM HANDLER FUNCTIONS ==========

    function handleImagePreview(e) {
        const file = e.target.files[0];
        const preview = $('#imagePreview');
        const previewImg = preview.find('img');

        if (!file) {
            preview.hide();
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            toastr.error('File size must be less than 5MB');
            $(this).val('');
            preview.hide();
            return;
        }

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
    }

    function generateSerialNumber() {
        const name = $('#item_name').val().trim();
        const prefix = name ? name.substring(0, 3).toUpperCase().replace(/\s/g, '') : 'EQP';
        const timestamp = Date.now().toString().substr(-8);
        const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
        $('#serial_number').val(`${prefix}-${timestamp}-${random}`);
    }

    function handleAddItemSubmit(e) {
        e.preventDefault();

        const itemName = $('#item_name').val().trim();
        const serialNumber = $('#serial_number').val().trim();
        const category = $('#category').val();

        if (!itemName || !serialNumber || !category) {
            toastr.error('Please fill all required fields (Item Name, Serial Number, Category)');
            return;
        }

        const formData = new FormData(this);
        const selectedAccessories = [];
        $('#accessories option:selected').each(function() {
            if ($(this).val()) selectedAccessories.push($(this).val());
        });
        formData.append('accessories_array', JSON.stringify(selectedAccessories));

        const submitBtn = $('#submitBtn');
        const originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');
        submitBtn.prop('disabled', true);

        $.ajax({
            url: 'api/items/create.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Item added successfully!');
                    $('#addItemForm')[0].reset();
                    $('#imagePreview').hide();
                    $('#selectedAccessories').html('<p class="text-muted mb-0">No accessories selected</p>');
                    $('#accessories').val('').trigger('change');
                    setTimeout(() => {
                        $('#addItemModal').modal('hide');
                        location.reload();
                    }, 1500);
                } else {
                    toastr.error(response.message || 'Failed to add item');
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'An error occurred while adding the item';
                if (xhr.responseText) {
                    try {
                        const jsonResponse = JSON.parse(xhr.responseText);
                        errorMessage = jsonResponse.message || errorMessage;
                    } catch (e) {
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
    }

    function handleDeleteItemClick(e) {
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
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function() {
                    toastr.error('Error deleting item');
                }
            });
        }
    }

    function handleExport() {
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
    }

    function handleSelectAll() {
        const isChecked = $(this).is(':checked');
        $('.item-checkbox').prop('checked', isChecked).trigger('change');
    }

    function handleItemCheckbox() {
        const itemId = $(this).val();
        if ($(this).is(':checked')) {
            selectedItems.push(itemId);
        } else {
            selectedItems = selectedItems.filter(id => id !== itemId);
        }
        $('#bulkActions').toggle(selectedItems.length > 0);
        $('#selectedCount').text(selectedItems.length);
    }

    function handleBulkDelete() {
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
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function() {
                    toastr.error('Error deleting items');
                }
            });
        }
    }

    // ========== CHART FUNCTIONS ==========

    function initializeStatusChart() {
        console.log('Initializing status chart with real data...');
        // ... chart initialization code (keep your existing chart code)
    }

    function refreshStatusChart() {
        // ... chart refresh code (keep your existing chart refresh code)
    }

    // ========== DOCUMENT READY ==========
    $(document).ready(function() {
        saveOriginalEditModalHtml();

        // Start the clock
        startClock();

        // Edit button click handler
        $(document).on('click', '.edit-item-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const itemId = $(this).data('item-id');
            if (itemId) openEditItemModal(itemId);
            else toastr.error('Invalid item ID');
        });

        // Quick view button click handler
        $(document).on('click', '.quick-view-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const itemId = $(this).data('item-id');
            const itemName = $(this).data('item-name');

            if (!itemId) {
                toastr.error('Invalid item ID');
                return;
            }

            toastr.info(`Loading ${itemName}...`, '', {
                timeOut: 3000
            });

            fetchItemData(itemId).then(function(data) {
                if (data) {
                    populateQuickViewModal(data);
                    const quickActionsModal = document.getElementById('quickActionsModal');
                    const modal = new bootstrap.Modal(quickActionsModal);
                    modal.show();
                    toastr.success('Item loaded successfully');
                } else {
                    toastr.error('Failed to load item details');
                }
            }).catch(function(error) {
                toastr.error('Error loading item details');
            });
        });

        // Initialize DataTable
        setTimeout(initializeDataTable, 300);

        // Refresh button
        $('#refreshItemsBtn').click(function(e) {
            e.preventDefault();
            const button = $(this);
            const originalHtml = button.html();
            button.html('<i class="fas fa-spinner fa-spin me-1"></i> Refreshing...');
            button.prop('disabled', true);
            toastr.info('Refreshing data...');
            refreshDataTable();
            setTimeout(() => {
                button.html(originalHtml);
                button.prop('disabled', false);
                toastr.success('Data refreshed!');
            }, 1500);
        });

        // Image preview
        $('#item_image').on('change', handleImagePreview);

        // Accessories change
        $('#accessories').on('change', updateSelectedAccessories);

        // Generate serial number
        $('#generateSerialBtn').click(generateSerialNumber);

        // Add item form submission
        $('#addItemForm').on('submit', handleAddItemSubmit);

        // Delete item button
        $(document).on('click', '.delete-item-btn', handleDeleteItemClick);

        // Export button
        $('#exportItemsBtn').click(handleExport);

        // Bulk actions
        $('#selectAllItems').on('change', handleSelectAll);
        $(document).on('change', '.item-checkbox', handleItemCheckbox);
        $('#bulkDeleteBtn').click(handleBulkDelete);

        // Initialize chart
        setTimeout(initializeStatusChart, 500);

        // Modal event handlers
        $('#quickActionsModal').on('hidden.bs.modal', () => delete window.currentItemData);
        $('#viewItemModal').on('hidden.bs.modal', () => delete window.currentViewItemData);
        $('#editItemModal').on('hidden.bs.modal', resetEditModal);

        // Remove accessory badge click handler (delegated)
        $(document).on('click', '.accessory-badge', function(e) {
            e.stopPropagation();
            const value = $(this).data('value');
            $('#accessories option[value="' + value + '"]').prop('selected', false);
            $(this).remove();
            if ($('#selectedAccessories .accessory-badge').length === 0) {
                $('#selectedAccessories').html('<p class="text-muted mb-0">No accessories selected</p>');
            }
        });
    });
</script>