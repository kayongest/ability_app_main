<?php
// dashboard.php
$current_page = 'dashboard.php';
require_once 'bootstrap.php';

// Include functions.php
require_once 'includes/functions.php';
require_once 'includes/db_connect.php';

// Get database connection and make it globally available
$conn = getConnection();
$GLOBALS['conn'] = $conn; // CRITICAL: This makes $conn available to functions

// Check for error parameter
if (isset($_GET['error'])) {
    $error_message = urldecode($_GET['error']);
    $_SESSION['toast_message'] = $error_message;
    $_SESSION['toast_type'] = 'error';
}

// Check authentication
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$pageTitle = "Home - aBility";

require_once 'views/partials/header.php';
?>

<style>
    /* Dashboard Styles */
    .dashboard-container {
        padding: 2rem 0;
    }

    .welcome-section {
        background: linear-gradient(135deg, #234c6a 0%, #1b3242 100%);
        color: white;
        padding: 1rem;
        border-radius: 7px;
        margin-bottom: 3rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .welcome-section h1 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .welcome-section p {
        font-size: 13px;
        opacity: 0.9;
    }

    .section-title {
        text-align: center;
        margin-bottom: 3rem;
    }

    .section-title h2 {
        font-size: 1rem;
        color: #234c6a;
        font-weight: 400;
        margin-bottom: 1rem;
    }

    .section-title p {
        color: #6c757d;
        font-size: 1rem;
    }

    /* Icon Box Styles */
    .icon-box {
        padding: 40px 30px;
        border-radius: 15px;
        background: white;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        text-align: center;
        height: 100%;
        border: 1px solid #e9ecef;
        cursor: pointer;
    }

    .icon-box:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(35, 76, 106, 0.1);
        border-color: #234c6a;
    }

    .icon-box .icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 25px;
        background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2.5rem;
        transition: all 0.3s ease;
    }

    .icon-box:hover .icon {
        background: linear-gradient(135deg, #2c5a7a 0%, #234c6a 100%);
        transform: scale(1.1);
    }

    .icon-box .title {
        font-size: 1.3rem;
        font-weight: 600;
        margin-bottom: 15px;
        color: #234c6a;
    }

    .icon-box .title a {
        color: inherit;
        text-decoration: none;
    }

    .icon-box .description {
        color: #6c757d;
        font-size: 0.95rem;
        line-height: 1.6;
        margin-bottom: 0;
    }

    /* Stats Cards */
    .stats-row {
        margin-bottom: 3rem;
    }

    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        gap: 1.5rem;
        height: 100%;
        border: 1px solid #e9ecef;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(35, 76, 106, 0.1);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        background: rgba(35, 76, 106, 0.1);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #234c6a;
        font-size: 1.8rem;
    }

    .stat-content {
        flex: 1;
    }

    .stat-value {
        font-size: 1.8rem;
        font-weight: 700;
        color: #234c6a;
        line-height: 1.2;
    }

    .stat-label {
        color: #6c757d;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .welcome-section {
            padding: 2rem;
        }

        .welcome-section h1 {
            font-size: 2rem;
        }

        .icon-box {
            padding: 30px 20px;
        }

        .stat-card {
            padding: 1rem;
        }
    }

    /* Toast Notifications */
    .toast-container {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 9999;
        pointer-events: none;
    }

    .toast-notification {
        min-width: 300px;
        max-width: 400px;
        background: white;
        border-radius: 12px;
        padding: 1rem 1.5rem;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        display: flex;
        align-items: center;
        gap: 1rem;
        animation: slideIn 0.3s ease;
        border-left: 4px solid;
        pointer-events: auto;
        margin-bottom: 1rem;
    }

    .toast-notification.success {
        border-left-color: #28a745;
    }

    .toast-notification.error {
        border-left-color: #dc3545;
    }

    .toast-notification.warning {
        border-left-color: #ffc107;
    }

    .toast-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .toast-notification.success .toast-icon {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }

    .toast-notification.error .toast-icon {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }

    .toast-notification.warning .toast-icon {
        background: rgba(255, 193, 7, 0.1);
        color: #ffc107;
    }

    .toast-content {
        flex: 1;
    }

    .toast-title {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .toast-message {
        color: #6c757d;
        font-size: 0.9rem;
    }

    .toast-close {
        color: #adb5bd;
        cursor: pointer;
        font-size: 1.2rem;
        transition: color 0.3s ease;
    }

    .toast-close:hover {
        color: #495057;
    }

    @keyframes slideIn {
        from {
            transform: translateY(-20px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateY(0);
            opacity: 1;
        }

        to {
            transform: translateY(-20px);
            opacity: 0;
        }
    }

    /* Toast Module Styles */
    .toast {
        border: none;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        margin-bottom: 1rem;
        border-radius: 0.5rem;
        overflow: hidden;
    }

    .toast:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(35, 76, 106, 0.15);
    }

    .toast-header {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding: 1rem 1.25rem;
    }

    .toast-header i {
        font-size: 1.1rem;
    }

    .toast-header small {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        opacity: 0.8;
    }

    .toast-body {
        padding: 1.25rem;
        background: white;
        color: #495057;
    }

    .toast-body p {
        font-size: 0.9rem;
        line-height: 1.5;
        margin-bottom: 1rem;
        min-height: 60px;
    }

    .toast-body .border-top {
        border-top-color: #e9ecef !important;
        padding-top: 1rem !important;
        margin-top: 0.5rem !important;
    }

    .toast-body .btn {
        padding: 0.4rem 1rem;
        font-weight: 500;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        border: none;
        transition: all 0.3s ease;
    }

    .toast-body .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(35, 76, 106, 0.3);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .toast-body p {
            min-height: auto;
        }

        .toast {
            margin-bottom: 1rem;
        }
    }
</style>

<div class="dashboard-container">
    <!-- Toast Container for Notifications -->
    <div class="toast-container" id="toastContainer"></div>

    <?php
    $access_stats = getUserAccessStats();
    $role_info = getRoleDisplayInfo();
    $user_roles = getUserRoles();
    ?>

    <!-- Welcome Section -->
    <div class="welcome-section aos-init aos-animate" data-aos="fade-up">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>! 👋</h1>
                <small>Manage your inventory, track equipment & monitor activities all in one place.</small>
            </div>
            <div class="col-lg-6 text-lg-end">
                <i class="fas fa-cogs" style="font-size: 5rem; opacity: 0.5;"></i>
            </div>
        </div>
    </div>

    <!-- Section Title with Access Info -->
    <div class="section-title aos-init aos-animate" data-aos="fade-up">

        <?php
        // Define page access by role
        $page_access = [
            'dashboard' => ['admin', 'manager', 'stock_manager', 'stock_controller', 'tech_lead', 'technician', 'user', 'driver'],
            'events' => ['admin', 'manager', 'stock_manager', 'stock_controller', 'tech_lead', 'user', 'driver'],
            'equipment' => ['admin', 'manager', 'stock_manager', 'stock_controller', 'tech_lead', 'user', 'driver'],
            'import' => ['admin', 'manager', 'stock_manager'],
            'single_scan' => ['admin', 'manager', 'stock_manager', 'stock_controller', 'tech_lead', 'technician', 'user', 'driver'],
            'bulk_scan' => ['admin', 'manager', 'stock_manager', 'stock_controller', 'tech_lead', 'technician', 'user', 'driver'],
            'scan_history' => ['admin', 'manager', 'stock_manager', 'stock_controller', 'tech_lead'],
            'reports' => ['admin', 'manager', 'stock_controller'],
            'user_management' => ['admin'],
            'technicians' => ['admin', 'manager', 'tech_lead'],
            'stock_locations' => ['admin', 'manager', 'stock_manager'],
            'batch_history' => ['admin', 'manager', 'stock_controller'],
            'settings' => ['admin'],
            'profile' => ['admin', 'manager', 'stock_manager', 'stock_controller', 'tech_lead', 'technician', 'user', 'driver']
        ];

        $total_modules = count($page_access);
        $accessible_modules = 0;
        $user_roles = getUserRoles(); // Get all user roles

        // Count pages accessible to the user
        foreach ($page_access as $page => $allowed_roles) {
            // Check if user has ANY of the required roles for this page
            foreach ($user_roles as $user_role) {
                if (in_array($user_role, $allowed_roles)) {
                    $accessible_modules++;
                    break; // Count this page once
                }
            }
        }

        // Determine role-specific message
        $role_message = '';
        $role_icon = '';

        if (isAdmin()) {
            $role_message = 'Full Access Granted';
            $role_icon = 'fa-crown';
        } elseif (hasRole('manager')) {
            $role_message = 'Manager Access Granted';
            $role_icon = 'fa-user-tie';
        } elseif (hasRole('stock_manager')) {
            $role_message = 'Stock Manager Access Granted';
            $role_icon = 'fa-warehouse';
        } elseif (hasRole('stock_controller')) {
            $role_message = 'Stock Controller Access Granted';
            $role_icon = 'fa-calculator';
        } elseif (hasRole('tech_lead')) {
            $role_message = 'Tech-Lead Access Granted';
            $role_icon = 'fa-microchip';
        } elseif (hasRole('technician')) {
            $role_message = 'Tech Access Granted';
            $role_icon = 'fa-tools';
        } elseif (hasRole('user')) {
            $role_message = 'Basic Access Granted';
            $role_icon = 'fa-user';
        } elseif (hasRole('driver')) {
            $role_message = 'Driver Access Granted';
            $role_icon = 'fa-truck';
        } else {
            $role_message = 'Access Granted';
            $role_icon = 'fa-shield-alt';
        }
        ?>

        <!-- Alert with access information -->
        <div class="alert alert-primary d-flex align-items-center mt-4" role="alert" style="background: linear-gradient(135deg, #234c6a10 0%, #2c5a7a10 100%); border-left: 4px solid #234c6a;">
            <div class="me-3">
                <i class="fas <?php echo $role_icon; ?> fa-2x" style="color: #234c6a;"></i>
            </div>
            <div class="text-start">
                <strong style="color: #234c6a;"><?php echo $role_message; ?></strong>
                <p class="mb-1 small text-muted">
                    <code style="color: #029d1c;">You have access to </code><strong><?php echo $accessible_modules; ?></strong> <code style="color: #029d1c;">pages.</code>
                    <br><small>User Role:</small> <span class="badge" style="background: #169160; color: white;"><?php echo getRoleDisplayName(getUserRole()); ?></span>
                </p>
            </div>
            <div class="ms-auto">
                <i class="fas fa-check-circle fa-2x" style="color: #28a745;"></i>
            </div>
        </div>

    </div>

    <!-- Dashboard Modules in Toast Design -->
    <div class="row gy-6">
        <!-- Dashboard -->
        <?php if (in_array('dashboard', array_keys(array_filter($page_access, function ($roles) use ($user_roles) {
            return array_intersect($user_roles, $roles);
        })))): ?>
            <div class="col-md-6 col-lg-3">
                <div class="toast show w-100" role="alert" aria-live="assertive" aria-atomic="true" style="max-width: 100%; opacity: 1;">
                    <div class="toast-header" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white; border-radius: 0.375rem 0.375rem 0 0;">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        <strong class="me-auto">Dashboard</strong>
                        <small class="text-white-50">Main</small>
                    </div>
                    <div class="toast-body" onclick="window.location.href='dashboard.php'" style="cursor: pointer;">
                        <p class="mb-2">View your dashboard and access all modules from one central location</p>
                        <div class="mt-2 pt-2 border-top">
                            <a href="dashboard.php" class="btn btn-sm" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white;">Go to Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Events -->
        <?php if (in_array('events', array_keys(array_filter($page_access, function ($roles) use ($user_roles) {
            return array_intersect($user_roles, $roles);
        })))): ?>
            <div class="col-md-6 col-lg-3">
                <div class="toast show w-100" role="alert" aria-live="assertive" aria-atomic="true" style="max-width: 100%; opacity: 1;">
                    <div class="toast-header" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white;">
                        <i class="fas fa-calendar-alt me-2"></i>
                        <strong class="me-auto">Events</strong>
                        <small class="text-white-50">Planning</small>
                    </div>
                    <div class="toast-body" onclick="window.location.href='events.php'" style="cursor: pointer;">
                        <p class="mb-2">Plan and manage events, assign resources, and track schedules</p>
                        <div class="mt-2 pt-2 border-top">
                            <a href="events.php" class="btn btn-sm" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white;">Manage Events</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Equipment -->
        <?php if (in_array('equipment', array_keys(array_filter($page_access, function ($roles) use ($user_roles) {
            return array_intersect($user_roles, $roles);
        })))): ?>
            <div class="col-md-6 col-lg-3">
                <div class="toast show w-100" role="alert" aria-live="assertive" aria-atomic="true" style="max-width: 100%; opacity: 1;">
                    <div class="toast-header" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white;">
                        <i class="fas fa-boxes me-2"></i>
                        <strong class="me-auto">Equipment</strong>
                        <small class="text-white-50">Inventory</small>
                    </div>
                    <div class="toast-body" onclick="window.location.href='items.php'" style="cursor: pointer;">
                        <p class="mb-2">Track inventory, manage equipment, and monitor stock levels</p>
                        <div class="mt-2 pt-2 border-top">
                            <a href="items.php" class="btn btn-sm" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white;">View Equipment</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Import -->
        <?php if (in_array('import', array_keys(array_filter($page_access, function ($roles) use ($user_roles) {
            return array_intersect($user_roles, $roles);
        })))): ?>
            <div class="col-md-6 col-lg-3">
                <div class="toast show w-100" role="alert" aria-live="assertive" aria-atomic="true" style="max-width: 100%; opacity: 1;">
                    <div class="toast-header" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white;">
                        <i class="fas fa-file-import me-2"></i>
                        <strong class="me-auto">Import</strong>
                        <small class="text-white-50">Bulk</small>
                    </div>
                    <div class="toast-body" onclick="window.location.href='import_items.php'" style="cursor: pointer;">
                        <p class="mb-2">Bulk import items from CSV or Excel files</p>
                        <div class="mt-2 pt-2 border-top">
                            <a href="import_items.php" class="btn btn-sm" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white;">Import Items</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Single Scan -->
        <?php if (in_array('single_scan', array_keys(array_filter($page_access, function ($roles) use ($user_roles) {
            return array_intersect($user_roles, $roles);
        })))): ?>
            <div class="col-md-6 col-lg-3">
                <div class="toast show w-100" role="alert" aria-live="assertive" aria-atomic="true" style="max-width: 100%; opacity: 1;">
                    <div class="toast-header" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white;">
                        <i class="fas fa-qrcode me-2"></i>
                        <strong class="me-auto">Single Scan</strong>
                        <small class="text-white-50">QR Codes</small>
                    </div>
                    <div class="toast-body" onclick="window.location.href='scan.php'" style="cursor: pointer;">
                        <p class="mb-2">Scan individual QR codes to access item information</p>
                        <div class="mt-2 pt-2 border-top">
                            <a href="scan.php" class="btn btn-sm" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white;">Scan Now</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Bulk Scan -->
        <?php if (in_array('bulk_scan', array_keys(array_filter($page_access, function ($roles) use ($user_roles) {
            return array_intersect($user_roles, $roles);
        })))): ?>
            <div class="col-md-6 col-lg-3">
                <div class="toast show w-100" role="alert" aria-live="assertive" aria-atomic="true" style="max-width: 100%; opacity: 1;">
                    <div class="toast-header" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white;">
                        <i class="fas fa-expand me-2"></i>
                        <strong class="me-auto">Bulk Scan</strong>
                        <small class="text-white-50">Multi QR</small>
                    </div>
                    <div class="toast-body" onclick="window.location.href='scan_2.php'" style="cursor: pointer;">
                        <p class="mb-2">Scan multiple QR codes in succession for faster processing</p>
                        <div class="mt-2 pt-2 border-top">
                            <a href="scan_2.php" class="btn btn-sm" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white;">Start Bulk Scan</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Scan History -->
        <?php if (in_array('scan_history', array_keys(array_filter($page_access, function ($roles) use ($user_roles) {
            return array_intersect($user_roles, $roles);
        })))): ?>
            <div class="col-md-6 col-lg-3">
                <div class="toast show w-100" role="alert" aria-live="assertive" aria-atomic="true" style="max-width: 100%; opacity: 1;">
                    <div class="toast-header" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white;">
                        <i class="fas fa-history me-2"></i>
                        <strong class="me-auto">Scan History</strong>
                        <small class="text-white-50">Logs</small>
                    </div>
                    <div class="toast-body" onclick="window.location.href='scan_logs.php'" style="cursor: pointer;">
                        <p class="mb-2">View and analyze all scan activities and history</p>
                        <div class="mt-2 pt-2 border-top">
                            <a href="scan_logs.php" class="btn btn-sm" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white;">View History</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Reports -->
        <?php if (in_array('reports', array_keys(array_filter($page_access, function ($roles) use ($user_roles) {
            return array_intersect($user_roles, $roles);
        })))): ?>
            <div class="col-md-6 col-lg-3">
                <div class="toast show w-100" role="alert" aria-live="assertive" aria-atomic="true" style="max-width: 100%; opacity: 1;">
                    <div class="toast-header" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white;">
                        <i class="fas fa-chart-bar me-2"></i>
                        <strong class="me-auto">Reports</strong>
                        <small class="text-white-50">Analytics</small>
                    </div>
                    <div class="toast-body" onclick="window.location.href='reports.php'" style="cursor: pointer;">
                        <p class="mb-2">Generate reports and analyze inventory data</p>
                        <div class="mt-2 pt-2 border-top">
                            <a href="reports.php" class="btn btn-sm" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white;">View Reports</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- User Management -->
        <?php if (in_array('user_management', array_keys(array_filter($page_access, function ($roles) use ($user_roles) {
            return array_intersect($user_roles, $roles);
        })))): ?>
            <div class="col-md-6 col-lg-3">
                <div class="toast show w-100" role="alert" aria-live="assertive" aria-atomic="true" style="max-width: 100%; opacity: 1;">
                    <div class="toast-header" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white;">
                        <i class="fas fa-users me-2"></i>
                        <strong class="me-auto">User Management</strong>
                        <small class="text-white-50">Admin</small>
                    </div>
                    <div class="toast-body" onclick="window.location.href='users.php'" style="cursor: pointer;">
                        <p class="mb-2">Manage user accounts, roles, and permissions</p>
                        <div class="mt-2 pt-2 border-top">
                            <a href="users.php" class="btn btn-sm" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white;">Manage Users</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Technicians -->
        <?php if (in_array('technicians', array_keys(array_filter($page_access, function ($roles) use ($user_roles) {
            return array_intersect($user_roles, $roles);
        })))): ?>
            <div class="col-md-6 col-lg-3">
                <div class="toast show w-100" role="alert" aria-live="assertive" aria-atomic="true" style="max-width: 100%; opacity: 1;">
                    <div class="toast-header" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white;">
                        <i class="fas fa-tools me-2"></i>
                        <strong class="me-auto">Technicians</strong>
                        <small class="text-white-50">Staff</small>
                    </div>
                    <div class="toast-body" onclick="window.location.href='technicians.php'" style="cursor: pointer;">
                        <p class="mb-2">Manage technician profiles, assign roles, and track their activities</p>
                        <div class="mt-2 pt-2 border-top">
                            <a href="technicians.php" class="btn btn-sm" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white;">View Technicians</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Stock Locations -->
        <?php if (in_array('stock_locations', array_keys(array_filter($page_access, function ($roles) use ($user_roles) {
            return array_intersect($user_roles, $roles);
        })))): ?>
            <div class="col-md-6 col-lg-3">
                <div class="toast show w-100" role="alert" aria-live="assertive" aria-atomic="true" style="max-width: 100%; opacity: 1;">
                    <div class="toast-header" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white;">
                        <i class="fas fa-warehouse me-2"></i>
                        <strong class="me-auto">Stock Locations</strong>
                        <small class="text-white-50">Warehouse</small>
                    </div>
                    <div class="toast-body" onclick="window.location.href='stock_locations.php'" style="cursor: pointer;">
                        <p class="mb-2">Manage warehouse and storage locations</p>
                        <div class="mt-2 pt-2 border-top">
                            <a href="stock_locations.php" class="btn btn-sm" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white;">Manage Locations</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Batch History -->
        <?php if (in_array('batch_history', array_keys(array_filter($page_access, function ($roles) use ($user_roles) {
            return array_intersect($user_roles, $roles);
        })))): ?>
            <div class="col-md-6 col-lg-3">
                <div class="toast show w-100" role="alert" aria-live="assertive" aria-atomic="true" style="max-width: 100%; opacity: 1;">
                    <div class="toast-header" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white;">
                        <i class="fas fa-history me-2"></i>
                        <strong class="me-auto">Batch History</strong>
                        <small class="text-white-50">Tracking</small>
                    </div>
                    <div class="toast-body" onclick="window.location.href='batch_history.php'" style="cursor: pointer;">
                        <p class="mb-2">Track batch operations and historical changes</p>
                        <div class="mt-2 pt-2 border-top">
                            <a href="batch_history.php" class="btn btn-sm" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white;">View History</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Settings -->
        <?php if (in_array('settings', array_keys(array_filter($page_access, function ($roles) use ($user_roles) {
            return array_intersect($user_roles, $roles);
        })))): ?>
            <div class="col-md-6 col-lg-3">
                <div class="toast show w-100" role="alert" aria-live="assertive" aria-atomic="true" style="max-width: 100%; opacity: 1;">
                    <div class="toast-header" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white;">
                        <i class="fas fa-cog me-2"></i>
                        <strong class="me-auto">Settings</strong>
                        <small class="text-white-50">Configuration</small>
                    </div>
                    <div class="toast-body" onclick="window.location.href='settings.php'" style="cursor: pointer;">
                        <p class="mb-2">Configure system preferences and options</p>
                        <div class="mt-2 pt-2 border-top">
                            <a href="settings.php" class="btn btn-sm" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white;">System Settings</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Profile -->
        <?php if (in_array('profile', array_keys(array_filter($page_access, function ($roles) use ($user_roles) {
            return array_intersect($user_roles, $roles);
        })))): ?>
            <div class="col-md-6 col-lg-3">
                <div class="toast show w-100" role="alert" aria-live="assertive" aria-atomic="true" style="max-width: 100%; opacity: 1;">
                    <div class="toast-header" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white;">
                        <i class="fas fa-user-circle me-2"></i>
                        <strong class="me-auto">Profile</strong>
                        <small class="text-white-50">Personal</small>
                    </div>
                    <div class="toast-body" onclick="window.location.href='profile.php'" style="cursor: pointer;">
                        <p class="mb-2">View and edit your personal information</p>
                        <div class="mt-2 pt-2 border-top">
                            <a href="profile.php" class="btn btn-sm" style="background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%); color: white;">View Profile</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Toast Notification Template -->
    <template id="toastTemplate">
        <div class="toast-notification">
            <div class="toast-icon">
                <i class="fas"></i>
            </div>
            <div class="toast-content">
                <div class="toast-title"></div>
                <div class="toast-message"></div>
            </div>
            <div class="toast-close">
                <i class="fas fa-times"></i>
            </div>
        </div>
    </template>

    <!-- jQuery (required for Bootstrap and our scripts) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <script>
        $(document).ready(function() {
            // Toast notification function
            function showToast(message, type = 'success', duration = 5000) {
                const template = document.getElementById('toastTemplate');
                const toast = template.content.cloneNode(true).querySelector('.toast-notification');
                const container = document.getElementById('toastContainer');

                // Set type
                toast.classList.add(type);

                // Set icon
                const icon = toast.querySelector('.toast-icon i');
                if (type === 'success') {
                    icon.classList.add('fa-check-circle');
                    toast.querySelector('.toast-title').textContent = 'Success';
                } else if (type === 'error') {
                    icon.classList.add('fa-exclamation-circle');
                    toast.querySelector('.toast-title').textContent = 'Error';
                } else {
                    icon.classList.add('fa-info-circle');
                    toast.querySelector('.toast-title').textContent = 'Info';
                }

                // Set message
                toast.querySelector('.toast-message').innerHTML = message;

                // Add to container
                container.appendChild(toast);

                // Close button
                toast.querySelector('.toast-close').addEventListener('click', () => {
                    toast.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => {
                        if (toast.parentNode) {
                            toast.parentNode.removeChild(toast);
                        }
                    }, 300);
                });

                // Auto remove after duration
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.style.animation = 'slideOut 0.3s ease';
                        setTimeout(() => {
                            if (toast.parentNode) {
                                toast.parentNode.removeChild(toast);
                            }
                        }, 300);
                    }
                }, duration);
            }

            // Check for session messages
            <?php if (isset($_SESSION['toast_message'])): ?>
                showToast('<?php echo addslashes($_SESSION['toast_message']); ?>', '<?php echo $_SESSION['toast_type'] ?? 'success'; ?>');
                <?php
                unset($_SESSION['toast_message']);
                unset($_SESSION['toast_type']);
                ?>
            <?php endif; ?>

            // Initialize AOS animations
            if (typeof AOS !== 'undefined') {
                AOS.init({
                    duration: 800,
                    once: true,
                    offset: 50
                });
            }

            // Add click handler for icon boxes
            $('.icon-box').on('click', function() {
                const link = $(this).find('a').attr('href');
                if (link) {
                    window.location.href = link;
                }
            });
        });
    </script>

    <?php require_once 'views/partials/footer.php'; ?>