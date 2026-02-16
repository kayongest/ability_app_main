<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// reports.php - Reports Management
require_once 'bootstrap.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

require_once 'includes/functions.php';

// Get database connection
function getDBConnection()
{
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $host = 'localhost';
            $dbname = 'ability_db';
            $username = 'root';
            $password = '';
            
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

// Initialize database connection
$pdo = getDBConnection();

$pageTitle = "Reports - aBility";
$showBreadcrumb = true;
$breadcrumbItems = [
    'Dashboard' => 'dashboard.php',
    'Reports' => ''
];

// Default date range (last 30 days)
$start_date = date('Y-m-d', strtotime('-30 days'));
$end_date = date('Y-m-d');

// Handle filter form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'] ?? $start_date;
    $end_date = $_POST['end_date'] ?? $end_date;
    $report_type = $_POST['report_type'] ?? 'summary';
}

// Get statistics - FIXED COLUMN NAMES
$statistics = [];
try {
    // Total items count
    $stmt = $pdo->query("SELECT COUNT(*) as total_items FROM items");
    $statistics['total_items'] = $stmt->fetchColumn();
    
    // Active items count (assuming status not equal to 'retired' or 'damaged')
    $stmt = $pdo->query("SELECT COUNT(*) as active_items FROM items WHERE status NOT IN ('retired', 'damaged', 'lost')");
    $statistics['active_items'] = $stmt->fetchColumn();
    
    // Total categories count
    $stmt = $pdo->query("SELECT COUNT(*) as total_categories FROM categories WHERE is_active = 1");
    $statistics['total_categories'] = $stmt->fetchColumn();
    
    // Total departments count
    $stmt = $pdo->query("SELECT COUNT(*) as total_departments FROM departments WHERE is_active = 1");
    $statistics['total_departments'] = $stmt->fetchColumn();
    
    // Items by status
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM items WHERE status IS NOT NULL AND status != '' GROUP BY status");
    $statistics['items_by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Items by category - IMPROVED VERSION
    $stmt = $pdo->query("SELECT 
                         CASE 
                             WHEN c.name IS NOT NULL THEN c.name
                             ELSE i.category
                         END as category_name,
                         COUNT(*) as count,
                         SUM(CASE WHEN i.status = 'available' THEN 1 ELSE 0 END) as available_count,
                         SUM(CASE WHEN i.status = 'in_use' THEN 1 ELSE 0 END) as in_use_count,
                         SUM(CASE WHEN i.status = 'checked_out' THEN 1 ELSE 0 END) as checked_out_count
                         FROM items i 
                         LEFT JOIN categories c ON i.category = c.code OR i.category = c.name
                         GROUP BY 
                         CASE 
                             WHEN c.name IS NOT NULL THEN c.name
                             ELSE i.category
                         END
                         ORDER BY count DESC 
                         LIMIT 10");
    $statistics['items_by_category'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get complete category statistics for detailed table
    $stmt = $pdo->query("SELECT 
                         c.code as category_code,
                         c.name as category_name,
                         COUNT(i.id) as item_count,
                         SUM(CASE WHEN i.status = 'available' THEN 1 ELSE 0 END) as available_count,
                         SUM(CASE WHEN i.status = 'in_use' THEN 1 ELSE 0 END) as in_use_count,
                         SUM(CASE WHEN i.status = 'checked_out' THEN 1 ELSE 0 END) as checked_out_count,
                         d.name as department_name
                         FROM categories c
                         LEFT JOIN items i ON i.category = c.code OR i.category = c.name
                         LEFT JOIN departments d ON c.department_code = d.code
                         WHERE c.is_active = 1
                         GROUP BY c.id, c.name
                         ORDER BY item_count DESC, c.name");
    $statistics['category_details'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Items by department - FIXED: items.department (not department_code)
    $stmt = $pdo->query("SELECT d.name as department_name, COUNT(*) as count 
                         FROM items i 
                         LEFT JOIN departments d ON i.department = d.code 
                         WHERE d.name IS NOT NULL
                         GROUP BY i.department 
                         ORDER BY count DESC 
                         LIMIT 10");
    $statistics['items_by_department'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent scans (last 30 days)
    $stmt = $pdo->prepare("SELECT COUNT(*) as recent_scans FROM scan_logs WHERE scan_timestamp >= :start_date");
    $stmt->execute([':start_date' => $start_date]);
    $statistics['recent_scans'] = $stmt->fetchColumn();
    
    // Daily scan trend
    $stmt = $pdo->prepare("SELECT DATE(scan_timestamp) as scan_day, COUNT(*) as scan_count 
                           FROM scan_logs 
                           WHERE scan_timestamp >= :start_date 
                           GROUP BY DATE(scan_timestamp) 
                           ORDER BY scan_day DESC 
                           LIMIT 30");
    $stmt->execute([':start_date' => date('Y-m-d', strtotime('-30 days'))]);
    $statistics['daily_scans'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top scanned items - FIXED: scan_logs.scan_timestamp (not scan_date)
    $stmt = $pdo->prepare("SELECT i.item_name, i.serial_number as code, COUNT(sl.id) as scan_count 
                           FROM scan_logs sl 
                           JOIN items i ON sl.item_id = i.id 
                           WHERE sl.scan_timestamp >= :start_date 
                           GROUP BY sl.item_id 
                           ORDER BY scan_count DESC 
                           LIMIT 10");
    $stmt->execute([':start_date' => $start_date]);
    $statistics['top_scanned_items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Items added in date range
    $stmt = $pdo->prepare("SELECT COUNT(*) as items_added FROM items WHERE created_at BETWEEN :start_date AND :end_date");
    $stmt->execute([':start_date' => $start_date . ' 00:00:00', ':end_date' => $end_date . ' 23:59:59']);
    $statistics['items_added'] = $stmt->fetchColumn();
    
    // Items by location - using stock_location from items table
    $stmt = $pdo->query("SELECT stock_location as location, COUNT(*) as count FROM items WHERE stock_location IS NOT NULL AND stock_location != '' GROUP BY stock_location ORDER BY count DESC LIMIT 10");
    $statistics['items_by_location'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Error loading statistics: " . $e->getMessage();
    error_log($error);
}

require_once 'views/partials/header.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-chart-bar me-2"></i>Reports & Analytics
        </h1>
        <div>
            <button class="btn btn-primary me-2" onclick="exportToExcel()">
                <i class="fas fa-file-excel me-2"></i>Export to Excel
            </button>
            <button class="btn btn-danger" onclick="exportToPDF()">
                <i class="fas fa-file-pdf me-2"></i>Export to PDF
            </button>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter me-2"></i>Report Filters
            </h6>
        </div>
        <div class="card-body">
            <form method="POST" action="" class="row g-3">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="<?php echo htmlspecialchars($start_date); ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="<?php echo htmlspecialchars($end_date); ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-3">
                    <label for="report_type" class="form-label">Report Type</label>
                    <select class="form-control" id="report_type" name="report_type">
                        <option value="summary" <?php echo ($_POST['report_type'] ?? 'summary') === 'summary' ? 'selected' : ''; ?>>Summary Report</option>
                        <option value="detailed" <?php echo ($_POST['report_type'] ?? '') === 'detailed' ? 'selected' : ''; ?>>Detailed Report</option>
                        <option value="scans" <?php echo ($_POST['report_type'] ?? '') === 'scans' ? 'selected' : ''; ?>>Scan Report</option>
                        <option value="inventory" <?php echo ($_POST['report_type'] ?? '') === 'inventory' ? 'selected' : ''; ?>>Inventory Report</option>
                        <option value="categories" <?php echo ($_POST['report_type'] ?? '') === 'categories' ? 'selected' : ''; ?>>Category Report</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i>Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Show different reports based on selection -->
    <?php if (($_POST['report_type'] ?? 'summary') === 'categories'): ?>
        <!-- CATEGORY REPORT SECTION -->
        <?php
        $totalItems = $statistics['total_items'] ?? 0;
        $categoryStats = $statistics['category_details'] ?? [];
        ?>
        
        <!-- Summary Cards for Category Report -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Categories</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($categoryStats); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-list fa-2x text-gray-300"></i>
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
                                    Total Items</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalItems; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-boxes fa-2x text-gray-300"></i>
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
                                    Avg Items per Category</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo count($categoryStats) > 0 ? number_format($totalItems / count($categoryStats), 1) : 0; ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chart-line fa-2x text-gray-300"></i>
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
                                    Categories with Items</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php 
                                    $categoriesWithItems = 0;
                                    foreach ($categoryStats as $cat) {
                                        if (($cat['item_count'] ?? 0) > 0) $categoriesWithItems++;
                                    }
                                    echo $categoriesWithItems;
                                    ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Charts -->
        <div class="row mb-4">
            <div class="col-xl-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-bar me-2"></i>Top Categories by Item Count
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-bar" style="height: 400px;">
                            <canvas id="categoryBarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-pie me-2"></i>Category Distribution
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-pie" style="height: 400px;">
                            <canvas id="categoryPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Category Table -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-table me-2"></i>Category-wise Item Details
                </h6>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" 
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="filterCategoryTable('all')">All Categories</a></li>
                        <li><a class="dropdown-item" href="#" onclick="filterCategoryTable('with_items')">With Items Only</a></li>
                        <li><a class="dropdown-item" href="#" onclick="filterCategoryTable('no_items')">No Items</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="categoryTable" width="100%" cellspacing="0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Category</th>
                                <th>Code</th>
                                <th>Department</th>
                                <th>Total Items</th>
                                <th>Available</th>
                                <th>In Use</th>
                                <th>Checked Out</th>
                                <th>% of Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($categoryStats)): ?>
                                <?php foreach ($categoryStats as $index => $category): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($category['category_name'] ?? 'Unnamed'); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($category['category_code'] ?? $category['category_name'] ?? 'N/A'); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($category['department_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo $category['item_count'] ?? 0; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success"><?php echo $category['available_count'] ?? 0; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $category['in_use_count'] ?? 0; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning"><?php echo $category['checked_out_count'] ?? 0; ?></span>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: <?php echo $totalItems > 0 ? (($category['item_count'] ?? 0) / $totalItems * 100) : 0; ?>%" 
                                                     aria-valuenow="<?php echo $category['item_count'] ?? 0; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="<?php echo $totalItems; ?>">
                                                    <?php echo $totalItems > 0 ? number_format(($category['item_count'] ?? 0) / $totalItems * 100, 1) : 0; ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (($category['item_count'] ?? 0) > 0): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No Items</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center">No category data available</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- DEFAULT SUMMARY REPORT SECTION -->
        
        <!-- Summary Statistics -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Items</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($statistics['total_items'] ?? 0); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-boxes fa-2x text-gray-300"></i>
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
                                    Active Items</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($statistics['active_items'] ?? 0); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                    Total Categories</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($statistics['total_categories'] ?? 0); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-list fa-2x text-gray-300"></i>
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
                                    Recent Scans (30 days)</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($statistics['recent_scans'] ?? 0); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-qrcode fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Detailed Reports -->
        <div class="row">
            <!-- Items by Status Chart -->
            <div class="col-xl-6 col-lg-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-pie me-2"></i>Items by Status
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-pie pt-4">
                            <canvas id="statusChart"></canvas>
                        </div>
                        <div class="mt-4 text-center small">
                            <?php if (!empty($statistics['items_by_status'])): ?>
                                <?php foreach ($statistics['items_by_status'] as $status): ?>
                                    <span class="mr-3">
                                        <i class="fas fa-circle" style="color: <?php echo getStatusColor($status['status'] ?? 'Unknown'); ?>"></i>
                                        <?php echo htmlspecialchars($status['status'] ?? 'Unknown'); ?> (<?php echo $status['count']; ?>)
                                    </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">No status data available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items by Category Chart -->
            <div class="col-xl-6 col-lg-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-bar me-2"></i>Top Categories by Item Count
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-bar">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Tables -->
        <div class="row">
            <!-- Top Scanned Items -->
            <div class="col-xl-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-star me-2"></i>Top 10 Most Scanned Items
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="topScannedTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Item Name</th>
                                        <th>Serial Number</th>
                                        <th>Scan Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($statistics['top_scanned_items'])): ?>
                                        <?php foreach ($statistics['top_scanned_items'] as $index => $item): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($item['item_name'] ?? 'Unknown'); ?></td>
                                                <td><?php echo htmlspecialchars($item['code'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo $item['scan_count']; ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td>1</td>
                                            <td>No scan data available</td>
                                            <td>N/A</td>
                                            <td>0</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items by Department -->
            <div class="col-xl-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-building me-2"></i>Items by Department
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="deptTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Department</th>
                                        <th>Item Count</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($statistics['items_by_department'])): ?>
                                        <?php $totalDeptItems = array_sum(array_column($statistics['items_by_department'], 'count')); ?>
                                        <?php foreach ($statistics['items_by_department'] as $dept): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($dept['department_name'] ?? 'Unassigned'); ?></td>
                                                <td><?php echo $dept['count']; ?></td>
                                                <td>
                                                    <div class="progress">
                                                        <div class="progress-bar" role="progressbar" 
                                                             style="width: <?php echo $totalDeptItems > 0 ? ($dept['count'] / $totalDeptItems * 100) : 0; ?>%" 
                                                             aria-valuenow="<?php echo $dept['count']; ?>" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="<?php echo $totalDeptItems; ?>">
                                                            <?php echo $totalDeptItems > 0 ? number_format($dept['count'] / $totalDeptItems * 100, 1) : 0; ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td>No departments</td>
                                            <td>0</td>
                                            <td>0%</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scan Activity -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-history me-2"></i>Recent Scan Activity (Last 30 Days)
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="scanTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Item Name</th>
                                        <th>Serial Number</th>
                                        <th>Department</th>
                                        <th>Category</th>
                                        <th>Location</th>
                                        <th>Scanned By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $scanData = [];
                                    try {
                                        // First, check if scan_logs table exists
                                        $tableCheck = $pdo->query("SHOW TABLES LIKE 'scan_logs'");
                                        if ($tableCheck->rowCount() > 0) {
                                            // Check if columns exist
                                            $columnCheck = $pdo->query("SHOW COLUMNS FROM scan_logs");
                                            $columns = $columnCheck->fetchAll(PDO::FETCH_COLUMN);
                                            
                                            $hasUserId = in_array('user_id', $columns);
                                            
                                            // Get user data if user_id exists
                                            $userData = [];
                                            if ($hasUserId) {
                                                $userStmt = $pdo->query("SELECT id, username FROM users");
                                                $userData = $userStmt->fetchAll(PDO::FETCH_KEY_PAIR);
                                            }
                                            
                                            $query = "SELECT sl.scan_timestamp as scan_date, i.item_name, i.serial_number, 
                                                     i.department, i.category, i.stock_location,
                                                     " . ($hasUserId ? "sl.user_id" : "NULL") . " as user_id
                                                     FROM scan_logs sl 
                                                     JOIN items i ON sl.item_id = i.id 
                                                     WHERE sl.scan_timestamp >= :start_date 
                                                     ORDER BY sl.scan_timestamp DESC 
                                                     LIMIT 50";
                                            
                                            $stmt = $pdo->prepare($query);
                                            $stmt->execute([':start_date' => date('Y-m-d', strtotime('-30 days'))]);
                                            $scanData = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            // Add username to scan data
                                            foreach ($scanData as &$scan) {
                                                if ($hasUserId && !empty($scan['user_id']) && isset($userData[$scan['user_id']])) {
                                                    $scan['username'] = $userData[$scan['user_id']];
                                                } else {
                                                    $scan['username'] = 'System';
                                                }
                                            }
                                            unset($scan);
                                        }
                                    } catch (Exception $e) {
                                        // Error will be handled below
                                    }
                                    
                                    if (!empty($scanData)):
                                        foreach ($scanData as $scan):
                                    ?>
                                        <tr>
                                            <td><?php echo date('Y-m-d H:i', strtotime($scan['scan_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($scan['item_name'] ?? 'Unknown'); ?></td>
                                            <td><?php echo htmlspecialchars($scan['serial_number'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($scan['department'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($scan['category'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($scan['stock_location'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($scan['username'] ?? 'System'); ?></td>
                                        </tr>
                                    <?php 
                                        endforeach;
                                    else:
                                    ?>
                                        <!-- IMPORTANT: Each error row must have exactly 7 columns to match thead -->
                                        <tr>
                                            <td><?php echo date('Y-m-d'); ?></td>
                                            <td>No scan data available</td>
                                            <td>N/A</td>
                                            <td>N/A</td>
                                            <td>N/A</td>
                                            <td>N/A</td>
                                            <td>System</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-download me-2"></i>Export Report
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Your report is being prepared. This may take a moment...
                </div>
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Function to get status color
function getStatusColor(status) {
    const colors = {
        'available': '#1c805c',
        'checked_out': '#364e95',
        'in_use': '#1ecfd5',
        'maintenance': '#e8b41a',
        'damaged': '#b51b1b',
        'retired': '#73747a',
        'lost': '#6c757d'
    };
    return colors[status] || '#858796';
}

// Generate colors for charts
function generateColors(count) {
    const colors = [
        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
        '#858796', '#6f42c1', '#20c9a6', '#fd7e14', '#e83e8c',
        '#17a2b8', '#ffc107', '#dc3545', '#6c757d', '#343a40',
        '#007bff', '#28a745', '#6610f2', '#6f42c1', '#d63384'
    ];
    return colors.slice(0, count);
}

document.addEventListener('DOMContentLoaded', function() {
    <?php if (($_POST['report_type'] ?? 'summary') === 'categories'): ?>
        // CATEGORY REPORT CHARTS
        const categoryStats = <?php echo json_encode($categoryStats); ?>;
        const totalItems = <?php echo $totalItems; ?>;
        
        // Bar Chart - Top Categories
        const categoryBarCtx = document.getElementById('categoryBarChart');
        if (categoryBarCtx && categoryStats.length > 0) {
            // Sort data for top 10 categories
            const sortedCategories = [...categoryStats].sort((a, b) => (b.item_count || 0) - (a.item_count || 0)).slice(0, 10);
            
            new Chart(categoryBarCtx, {
                type: 'bar',
                data: {
                    labels: sortedCategories.map(cat => cat.category_name || 'Unnamed'),
                    datasets: [{
                        label: "Item Count",
                        backgroundColor: "#4e73df",
                        hoverBackgroundColor: "#2e59d9",
                        borderColor: "#4e73df",
                        data: sortedCategories.map(cat => cat.item_count || 0),
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    scales: {
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                maxTicksLimit: 10
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            },
                            grid: {
                                color: "rgb(234, 236, 244)",
                                zeroLineColor: "rgb(234, 236, 244)",
                                drawBorder: false,
                                borderDash: [2],
                                zeroLineBorderDash: [2]
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Items: ${context.raw}`;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Pie Chart - Category Distribution
        const categoryPieCtx = document.getElementById('categoryPieChart');
        if (categoryPieCtx && categoryStats.length > 0) {
            // Filter out categories with no items
            const pieData = categoryStats.filter(cat => (cat.item_count || 0) > 0);
            
            // Generate colors
            const colors = generateColors(pieData.length);
            
            new Chart(categoryPieCtx, {
                type: 'pie',
                data: {
                    labels: pieData.map(cat => `${cat.category_name || 'Unnamed'} (${cat.item_count || 0})`),
                    datasets: [{
                        data: pieData.map(cat => cat.item_count || 0),
                        backgroundColor: colors,
                        hoverBackgroundColor: colors.map(color => color + 'CC'),
                        hoverBorderColor: "rgba(234, 236, 244, 1)",
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = pieData.reduce((sum, cat) => sum + (cat.item_count || 0), 0);
                                    const percentage = total > 0 ? ((context.raw / total) * 100).toFixed(1) : 0;
                                    return `${context.label}: ${percentage}%`;
                                }
                            }
                        }
                    }
                },
            });
        }
        
        // Initialize Category DataTable
        if (typeof $.fn.DataTable !== 'undefined') {
            $('#categoryTable').DataTable({
                pageLength: 25,
                responsive: true,
                order: [[4, 'desc']],
                language: {
                    emptyTable: "No category data available",
                    info: "Showing _START_ to _END_ of _TOTAL_ categories",
                    infoEmpty: "Showing 0 to 0 of 0 categories",
                    infoFiltered: "(filtered from _MAX_ total categories)",
                    lengthMenu: "Show _MENU_ categories",
                    loadingRecords: "Loading...",
                    processing: "Processing...",
                    search: "Search categories:",
                    zeroRecords: "No matching categories found"
                }
            });
        }
        
        // Category table filter function
        window.filterCategoryTable = function(type) {
            const categoryDataTable = $('#categoryTable').DataTable();
            
            switch(type) {
                case 'with_items':
                    categoryDataTable.column(4).search('^[1-9]', true, false).draw();
                    break;
                case 'no_items':
                    categoryDataTable.column(4).search('^0$', true, false).draw();
                    break;
                default:
                    categoryDataTable.search('').columns().search('').draw();
            }
        };
        
    <?php else: ?>
        // DEFAULT REPORT CHARTS
        
        // Pie Chart - Items by Status
        const statusCtx = document.getElementById('statusChart');
        <?php if (!empty($statistics['items_by_status'])): ?>
            const statusLabels = <?php echo json_encode(array_column($statistics['items_by_status'], 'status')); ?>;
            const statusData = <?php echo json_encode(array_column($statistics['items_by_status'], 'count')); ?>;
            const statusColors = statusLabels.map(label => getStatusColor(label));
            
            if (statusCtx) {
                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: statusLabels,
                        datasets: [{
                            data: statusData,
                            backgroundColor: statusColors,
                            hoverBackgroundColor: statusColors.map(color => color + 'CC'),
                            hoverBorderColor: "rgba(234, 236, 244, 1)",
                        }],
                    },
                    options: {
                        maintainAspectRatio: false,
                        tooltips: {
                            backgroundColor: "rgb(255,255,255)",
                            bodyFontColor: "#858796",
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            xPadding: 15,
                            yPadding: 15,
                            displayColors: false,
                            caretPadding: 10,
                        },
                        legend: {
                            display: false
                        },
                        cutoutPercentage: 80,
                    },
                });
            }
        <?php endif; ?>

        // Bar Chart - Items by Category
        const categoryCtx = document.getElementById('categoryChart');
        <?php if (!empty($statistics['items_by_category'])): ?>
            const categoryLabels = <?php echo json_encode(array_column($statistics['items_by_category'], 'category_name')); ?>;
            const categoryData = <?php echo json_encode(array_column($statistics['items_by_category'], 'count')); ?>;
            
            if (categoryCtx) {
                new Chart(categoryCtx, {
                    type: 'bar',
                    data: {
                        labels: categoryLabels,
                        datasets: [{
                            label: "Item Count",
                            backgroundColor: "#4e73df",
                            hoverBackgroundColor: "#2e59d9",
                            borderColor: "#4e73df",
                            data: categoryData,
                        }],
                    },
                    options: {
                        maintainAspectRatio: false,
                        layout: {
                            padding: {
                                left: 10,
                                right: 25,
                                top: 25,
                                bottom: 0
                            }
                        },
                        scales: {
                            xAxes: [{
                                gridLines: {
                                    display: false,
                                    drawBorder: false
                                },
                                ticks: {
                                    maxTicksLimit: 6
                                },
                                maxBarThickness: 25,
                            }],
                            yAxes: [{
                                ticks: {
                                    min: 0,
                                    maxTicksLimit: 5,
                                    padding: 10,
                                },
                                gridLines: {
                                    color: "rgb(234, 236, 244)",
                                    zeroLineColor: "rgb(234, 236, 244)",
                                    drawBorder: false,
                                    borderDash: [2],
                                    zeroLineBorderDash: [2]
                                }
                            }],
                        },
                        legend: {
                            display: false
                        },
                        tooltips: {
                            titleMarginBottom: 10,
                            titleFontColor: '#6e707e',
                            titleFontSize: 14,
                            backgroundColor: "rgb(255,255,255)",
                            bodyFontColor: "#858796",
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            xPadding: 15,
                            yPadding: 15,
                            displayColors: false,
                            caretPadding: 10,
                        },
                    }
                });
            }
        <?php endif; ?>

        // Initialize DataTable for scan table only
        if (typeof $.fn.DataTable !== 'undefined') {
            $('#scanTable').DataTable({
                pageLength: 10,
                responsive: true,
                order: [[0, 'desc']],
                language: {
                    emptyTable: "No data available in table",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    lengthMenu: "Show _MENU_ entries",
                    loadingRecords: "Loading...",
                    processing: "Processing...",
                    search: "Search:",
                    zeroRecords: "No matching records found"
                }
            });
            
            // Also initialize other tables if needed
            $('#topScannedTable').DataTable({
                pageLength: 10,
                responsive: true,
                searching: false,
                paging: false,
                info: false
            });
            
            $('#deptTable').DataTable({
                pageLength: 10,
                responsive: true,
                searching: false,
                paging: false,
                info: false
            });
        }
    <?php endif; ?>
});

// Export Functions
function exportToExcel() {
    const modal = new bootstrap.Modal(document.getElementById('exportModal'));
    modal.show();
    
    // Simple table export using HTML
    setTimeout(() => {
        let exportTable;
        <?php if (($_POST['report_type'] ?? 'summary') === 'categories'): ?>
            exportTable = document.getElementById('categoryTable');
        <?php else: ?>
            exportTable = document.getElementById('scanTable');
        <?php endif; ?>
        
        const html = exportTable.outerHTML;
        const blob = new Blob([html], {type: 'application/vnd.ms-excel'});
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'ability_report_' + new Date().toISOString().slice(0,10) + '.xls';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        modal.hide();
    }, 1000);
}

function exportToPDF() {
    const modal = new bootstrap.Modal(document.getElementById('exportModal'));
    modal.show();
    
    setTimeout(() => {
        alert('For PDF export, please implement a server-side solution using libraries like TCPDF, Dompdf, or MPDF.');
        modal.hide();
    }, 1000);
}
</script>

<?php
// Helper function for status colors
function getStatusColor($status) {
    $colors = [
        'available' => '#1cc88a',
        'checked_out' => '#4e73df',
        'in_use' => '#36b9cc',
        'maintenance' => '#f6c23e',
        'damaged' => '#e74a3b',
        'retired' => '#858796',
        'lost' => '#6c757d'
    ];
    return $colors[$status] ?? '#858796';
}
?>

<style>
    .card {
        border-radius: 10px;
    }
    
    .card-header {
        border-radius: 10px 10px 0 0 !important;
    }
    
    .progress {
        height: 20px;
        margin-bottom: 0;
    }
    
    .progress-bar {
        background-color: #4e73df;
    }
    
    .chart-pie, .chart-bar {
        position: relative;
    }
    
    .border-left-primary {
        border-left: 0.25rem solid #4e73df !important;
    }
    
    .border-left-success {
        border-left: 0.25rem solid #1cc88a !important;
    }
    
    .border-left-info {
        border-left: 0.25rem solid #36b9cc !important;
    }
    
    .border-left-warning {
        border-left: 0.25rem solid #f6c23e !important;
    }
    
    .badge {
        font-size: 0.85em;
        padding: 4px 8px;
    }
    
    .table th {
        font-weight: 600;
        background-color: #f8f9fa;
    }
</style>

<?php require_once 'views/partials/footer.php'; ?>