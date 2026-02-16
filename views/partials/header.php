<?php
// header.php - Add this at the very top

// Debug current location
echo '<!-- Current file: ' . __FILE__ . ' -->';
echo '<!-- Document root: ' . $_SERVER['DOCUMENT_ROOT'] . ' -->';
echo '<!-- Request URI: ' . $_SERVER['REQUEST_URI'] . ' -->';
echo '<!-- BASE_URL before definition: ' . (defined('BASE_URL') ? BASE_URL : 'not defined') . ' -->';

// If bootstrap.php hasn't set BASE_URL, set it here
if (!defined('BASE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];

    // Get the current script path and normalize it
    $script_name = $_SERVER['SCRIPT_NAME'];
    $script_dir = dirname($script_name);

    // Count directory levels to determine how many times to go up
    $depth = substr_count($script_dir, '/') - 1; // -1 because we start from root

    // Build the base URL - always absolute
    define('BASE_URL', $protocol . '://' . $host . '/ability_app_main/');

    // Also define a relative base for when we need it
    $relative_base = str_repeat('../', $depth) ?: './';
    define('RELATIVE_BASE', $relative_base);

    // Debug
    echo '<script>console.log("HEADER: BASE_URL set to: ' . BASE_URL . '");</script>';
    echo '<script>console.log("HEADER: Current script dir: ' . $script_dir . '");</script>';
    echo '<script>console.log("HEADER: Depth: ' . $depth . '");</script>';
    echo '<script>console.log("HEADER: RELATIVE_BASE: ' . $relative_base . '");</script>';
}

// Get current page name for navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = dirname($_SERVER['PHP_SELF']);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Authentication check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Don't redirect if we're on login or register pages
    $allowed_pages = ['login.php', 'register.php', 'forgot_password.php'];
    $current_page_name = basename($_SERVER['PHP_SELF']);

    if (!in_array($current_page_name, $allowed_pages)) {
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? SITE_NAME); ?></title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>assets/images/warehouse_.png">

    <!-- ========== CSS FILES (LOAD FIRST) ========== -->
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <!-- Your Custom CSS -->
    <!-- <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css"> -->
    <style>
        /* assets/css/styles.css */

        @import url("https://fonts.googleapis.com/css2?family=Titillium+Web:ital,wght@0,200;0,300;0,400;0,600;0,700;0,900;1,200;1,300;1,400;1,600;1,700&display=swap");

        /* CSS Variables for theming */
        :root {
            --primary: #324e8e;
            --secondary: #6c757d;
            --success: #28a745;
            --danger: ;
            font-size: 0.875em;
            margin-top: 0;
            --warning: #ffc107;
            --info: #17a2b8;
            --light: #f8f9fa;
            --dark: #343a40;
            --sidebar-width: 280px;
            --header-height: 56px;
        }

        /* Global Styles */
        body {
            background-color: #f5f7fb;
            font-family: "Titillium Web", sans-serif;
        }

        .btn-group-sm {
            background-color: #0c3c78;
            border-color: white;
        }

        /* All fonts for all H1-H6 and normal text */
        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        p,
        span,
        div,
        a,
        li,
        td,
        th,
        button,
        input,
        select,
        textarea {
            font-family: "Titillium Web", sans-serif;
        }

        .titillium-web-extralight {
            font-family: "Titillium Web", sans-serif;
            font-weight: 200;
            font-style: normal;
        }

        .titillium-web-light {
            font-family: "Titillium Web", sans-serif;
            font-weight: 300;
            font-style: normal;
        }

        .titillium-web-regular {
            font-family: "Titillium Web", sans-serif;
            font-weight: 400;
            font-style: normal;
        }

        .titillium-web-semibold {
            font-family: "Titillium Web", sans-serif;
            font-weight: 600;
            font-style: normal;
        }

        .titillium-web-bold {
            font-family: "Titillium Web", sans-serif;
            font-weight: 700;
            font-style: normal;
        }

        .titillium-web-black {
            font-family: "Titillium Web", sans-serif;
            font-weight: 900;
            font-style: normal;
        }

        .titillium-web-extralight-italic {
            font-family: "Titillium Web", sans-serif;
            font-weight: 200;
            font-style: italic;
        }

        .titillium-web-light-italic {
            font-family: "Titillium Web", sans-serif;
            font-weight: 300;
            font-style: italic;
        }

        .titillium-web-regular-italic {
            font-family: "Titillium Web", sans-serif;
            font-weight: 400;
            font-style: italic;
        }

        .titillium-web-semibold-italic {
            font-family: "Titillium Web", sans-serif;
            font-weight: 600;
            font-style: italic;
        }

        .titillium-web-bold-italic {
            font-family: "Titillium Web", sans-serif;
            font-weight: 700;
            font-style: italic;
        }

        /* Dashboard Stats Cards */
        .stat-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 20px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-card .card-body {
            padding: 1.5rem;
        }

        .stat-card .icon-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            opacity: 0.9;
        }

        /* Sort indicators */
        th[data-sort]:hover {
            background-color: #e9ecef;
        }

        .sort-asc::after {
            content: " ↑";
            color: #1e76d3;
        }

        .sort-desc::after {
            content: " ↓";
            color: #2066b1;
        }

        /* Export buttons */
        .export-btn-group {
            display: flex;
            gap: 15px;
        }

        .export-btn-group .btn {
            padding: 6px 12px;
            font-size: 0.875rem;
        }

        .breadcrumb {
            text-decoration: none;
        }

        /* Add these optimizations */
        .alert {
            transition: opacity 0.3s ease-in-out;
            will-change: opacity;
            /* Hint to browser for optimization */
        }

        /* Optimize animations */
        .fade {
            transition: opacity 0.15s linear !important;
        }

        /* Optimize DataTables */
        .dataTables_wrapper .dataTables_processing {
            transition: opacity 0.3s;
        }

        /* Disable animations for users who prefer reduced motion */
        @media (prefers-reduced-motion: reduce) {

            .alert,
            .fade,
            .modal.fade .modal-dialog {
                transition: none !important;
            }

            .spinner-border {
                animation-duration: 0.5s !important;
            }
        }

        /* Status Badges */
        .status-available {
            background-color: #d1f7c4 !important;
            color: #0d4629 !important;
        }

        .status-in_use {
            background-color: #d0e6ff !important;
            color: #0c3c78 !important;
        }

        .status-maintenance {
            background-color: #fff2d1 !important;
            color: #7a5900 !important;
        }

        .status-reserved {
            background-color: #e8d7ff !important;
            color: #4c1d95 !important;
        }

        .status-disposed {
            background-color: #ffd7d7 !important;
            color: #7f1d1d !important;
        }

        /* Category Badges */
        .badge-category-audio {
            background-color: #4361ee;
            color: white;
        }

        .badge-category-video {
            background-color: #3a0ca3;
            color: white;
        }

        .badge-category-lighting {
            background-color: #f72585;
            color: white;
        }

        .badge-category-translation {
            background-color: #4cc9f0;
            color: #333;
        }

        .badge-category-it {
            background-color: #7209b7;
            color: white;
        }

        /* Sidebar Styling */
        .sidebar {
            background: linear-gradient(180deg, var(--primary) 0%, #3a56d4 100%);
            color: white;
            min-height: calc(100vh - var(--header-height));
            padding-top: 20px;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 10px 20px;
            margin: 5px 0;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-weight: 600;
        }

        .sidebar .nav-link i {
            width: 24px;
            text-align: center;
            margin-right: 10px;
        }

        /* Main Content Area */
        main {
            padding-top: 20px;
        }

        /* Table Styling */
        .table-hover tbody tr:hover {
            background-color: rgba(var(--primary), 0.05);
        }

        .table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            color: var(--secondary);
            border-bottom: 2px solid #dee2e6;
        }

        /* Quick Action Cards */
        .quick-action-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            height: 100%;
        }

        .quick-action-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .quick-action-card .card-body {
            padding: 2rem 1.5rem;
        }

        .quick-action-card .fa-3x {
            margin-bottom: 1.5rem;
        }

        /* Button Groups */
        .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
        }

        /* Loading Spinner */
        .spinner-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            display: none;
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* QR Code Styling */
        .qr-container {
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .qr-code-img {
            max-width: 200px;
            height: auto;
            margin: 0 auto;
        }

        /* Print Styles */
        @media print {
            .no-print {
                display: none !important;
            }

            .sidebar,
            .btn-toolbar {
                display: none !important;
            }

            .card {
                border: 1px solid #ddd !important;
                box-shadow: none !important;
            }
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                z-index: 1000;
                width: var(--sidebar-width);
                height: 100vh;
                transform: translateX(-100%);
                transition: transform 0.3s;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .stat-card {
                margin-bottom: 15px;
            }

            .quick-action-card {
                margin-bottom: 20px;
            }
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .custom-toast {
            min-width: 320px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12), 0 2px 6px rgba(0, 0, 0, 0.08);
            border: none;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border-left: 4px solid;
            font-family: "Titillium Web", sans-serif;
            font-weight: 500;
            animation: slideInRight 0.3s ease-out;
        }

        .custom-toast.toast-success {
            border-left-color: #10b981;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(255, 255, 255, 0.95));
        }

        .custom-toast.toast-error {
            border-left-color: #ef4444;
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(255, 255, 255, 0.95));
        }

        .custom-toast.toast-warning {
            border-left-color: #f59e0b;
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(255, 255, 255, 0.95));
        }

        .custom-toast.toast-info {
            border-left-color: #3b82f6;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(255, 255, 255, 0.95));
        }

        .custom-toast .toast-message {
            font-size: 14px;
            line-height: 1.5;
            color: #374151;
        }

        .custom-toast .toast-title {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 4px;
            color: #1f2937;
        }

        .custom-toast .toast-close-button {
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .custom-toast .toast-close-button:hover {
            opacity: 1;
        }

        .custom-toast .toast-progress {
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            height: 3px;
            border-radius: 0 0 12px 12px;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .toast-container .toast {
            margin-bottom: 12px;
        }

        /* Form Controls */
        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }

        /* Action Buttons in Table */
        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .action-buttons .btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        /* Search Box */
        .search-box {
            position: relative;
        }

        .search-box .form-control {
            padding-left: 40px;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary);
        }

        /* Form validation styles */
        .required:after {
            content: " *";
            color: #912a16;
        }

        .form-control.is-invalid,
        .form-select.is-invalid {
            border-color: #912a16;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        .invalid-feedback {
            display: block;
            color: #912a16;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }

        /* Modal customization */
        #addItemModal .modal-dialog {
            max-width: 800px;
        }

        #addItemModal .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }

        /* Image preview */
        .img-thumbnail {
            max-width: 100%;
            height: auto;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.25rem;
        }

        .img-preview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
        }

        .qr-info {
            background: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
            font-size: 12px;
            text-align: left;
        }

        .qr-info div {
            margin-bottom: 5px;
        }

        .qr-info strong {
            min-width: 70px;
            display: inline-block;
            color: #666;
        }
    </style>


</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm sticky-top" style="background: #234c6a;">
        <div class="container-fluid px-3">
            <a class="navbar-brand d-flex align-items-center" href="<?php echo BASE_URL; ?>">
                <i class="fas fa-sync me-2"></i>
                <span class="fw-bold">aBility</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav me-auto">
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'index.php' || $current_page == 'dashboard.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>index.php">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>

                    <!-- Events -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'events.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>events.php">
                            <i class="fas fa-list me-1"></i> Events
                        </a>
                    </li>

                    <!-- Equipment -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($current_page, 'items') !== false || $current_page == 'index.php?view=items') ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>views/items/index.php">
                            <i class="fas fa-boxes me-1"></i> Equipment
                        </a>
                    </li>

                    <!-- Accessories -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'accessories.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>accessories.php">
                            <i class="fas fa-plug me-1"></i> Accessories
                        </a>
                    </li>

                    <!-- Import -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'import_items.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>import_items.php">
                            <i class="fas fa-upload me-1"></i> Import
                        </a>
                    </li>

                    <!-- Single Scan -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'scan.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>scan.php">
                            <i class="fas fa-expand me-1"></i> Single
                        </a>
                    </li>

                    <!-- Bulk Scan -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'scan_2.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>scan_2.php">
                            <i class="fas fa-qrcode me-1"></i> Bulk
                        </a>
                    </li>

                    <!-- Scan History -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'scan_logs.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>scan_logs.php">
                            <i class="fas fa-history me-1"></i> History
                        </a>
                    </li>

                    <!-- Reports -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>reports.php">
                            <i class="fas fa-chart-bar me-1"></i> Reports
                        </a>
                    </li>

                    <!-- Stock Locations -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'stock_locations.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>stock_locations.php">
                            <i class="fas fa-warehouse me-1"></i> Stock Locations
                        </a>
                    </li>

                    <!-- Batch History -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'batch_history.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>batch_history.php">
                            <i class="fas fa-history me-1"></i> Batch History
                        </a>
                    </li>

                    <!-- User Management (Admin only) -->
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>"
                                href="<?php echo BASE_URL; ?>users.php">
                                <i class="fas fa-users me-1"></i> User Management
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Settings Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo (in_array($current_page, ['categories.php', 'departments.php', 'locations.php'])) ? 'active' : ''; ?>"
                            href="#" id="settingsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cog me-1"></i> Settings
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="settingsDropdown">
                            <li><a class="dropdown-item <?php echo ($current_page == 'categories.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>categories.php">
                                    <i class="fas fa-list me-2"></i> Categories
                                </a></li>
                            <li><a class="dropdown-item <?php echo ($current_page == 'departments.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>departments.php">
                                    <i class="fas fa-building me-2"></i> Departments
                                </a></li>
                            <li><a class="dropdown-item <?php echo ($current_page == 'locations.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>locations.php">
                                    <i class="fas fa-map-marker-alt me-2"></i> Locations
                                </a></li>
                        </ul>
                    </li>
                </ul>

                <!-- User Dropdown -->
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown"
                                role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i>
                                <span class="d-none d-md-inline me-2"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                                <!-- <i class="fas fa-chevron-down ms-1" style="font-size: 0.8em;"></i> -->
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                                <li>
                                    <h6 class="dropdown-header"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></h6>
                                </li>
                                <li><a class="dropdown-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>profile.php">
                                        <i class="fas fa-user me-2"></i> Profile
                                    </a></li>
                                <li><a class="dropdown-item <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>settings.php">
                                        <i class="fas fa-cog me-2"></i> Account Settings
                                    </a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                                    </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'login.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>login.php">
                                <i class="fas fa-sign-in-alt me-1"></i> Login
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <!-- Main Content Container -->
    <div class="container-fluid mt-3">
        <!-- Breadcrumb -->
        <!-- <?php if (isset($showBreadcrumb) && $showBreadcrumb): ?>
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb bg-light p-2 rounded">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>index.php"><i class="fas fa-home"></i></a></li>
                    <?php
                    if (isset($breadcrumbItems)) {
                        foreach ($breadcrumbItems as $text => $link) {
                            if ($link) {
                                echo '<li class="breadcrumb-item"><a href="' . BASE_URL . $link . '">' . htmlspecialchars($text) . '</a></li>';
                            } else {
                                echo '<li class="breadcrumb-item active" aria-current="page">' . htmlspecialchars($text) . '</li>';
                            }
                        }
                    }
                    ?>
                </ol>
            </nav>
        <?php endif; ?> -->

        <!-- Content will be inserted here by individual pages -->
        <div id="main-content">