<?php
if (!defined('BASE_URL')) {
    // Try different methods to get base URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];

    // Method 1: Just use host with project folder
    $base_url = $protocol . '://' . $host . '/ability_app-master/';

    // OR Method 2: Check current URL
    $current_url = $protocol . '://' . $host . $_SERVER['REQUEST_URI'];
    if (strpos($current_url, '/ability_app-master/') !== false) {
        $base_url = $protocol . '://' . $host . '/ability_app-master/';
    } else {
        $base_url = $protocol . '://' . $host . '/';
    }

    define('BASE_URL', $base_url);

    // Debug: echo to see what BASE_URL is
    // echo '<script>console.log("BASE_URL: ' . $base_url . '");</script>';
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

    <!-- DataTables CSS (Must be before jQuery) -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <!-- Your Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
    <!-- <link rel="stylesheet" href="<?php echo rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/assets/css/styles.css'; ?>"> -->

    <!-- ========== JAVASCRIPT FILES (LOAD AFTER CSS) ========== -->
    <!-- jQuery MUST BE FIRST -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables JS (AFTER jQuery) -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

    <!-- Add these after your existing CSS links -->
    <!-- SheetJS for Excel export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <!-- jsPDF for PDF export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <!-- jsPDF AutoTable plugin -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

    <!-- Initialize dropdowns -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Ensure Bootstrap dropdowns work
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'))
            var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl)
            });
        });
    </script>

</head>

<body>
    <!-- Simplified Version without Dropdowns -->
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm sticky-top" style="background-color: rgb(27 50 84); color: white;">
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
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'index.php' || $current_page == 'dashboard.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>index.php">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($current_page, 'items') !== false) ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>views/items/index.php">
                            <i class="fas fa-boxes me-1"></i> Equipment
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'accessories.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>accessories.php">
                            <i class="fas fa-plug me-1"></i> Accessories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'import_items.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>import_items.php">
                            <i class="fas fa-upload me-1"></i> Import
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'scan.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>scan.php">
                            <i class="fas fa-expand me-1"></i> Single
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'scan_2.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>scan_2.php">
                            <i class="fas fa-qrcode me-1"></i> Bulk
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'scan_logs.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>scan_logs.php">
                            <i class="fas fa-history me-1"></i> History
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>reports.php">
                            <i class="fas fa-chart-bar me-1"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'batch_history.php') ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>batch_history.php">
                            <i class="fas fa-chart-bar me-1"></i> Batch History
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

                    <!-- Keep settings dropdown as it has multiple items -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo (in_array($current_page, ['categories.php', 'departments.php', 'locations.php'])) ? 'active' : ''; ?>"
                            href="#" id="settingsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cog me-1"></i> Settings
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="settingsDropdown">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>categories.php">
                                    <i class="fas fa-list me-2"></i> Categories
                                </a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>departments.php">
                                    <i class="fas fa-building me-2"></i> Departments
                                </a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>locations.php">
                                    <i class="fas fa-map-marker-alt me-2"></i> Locations
                                </a></li>
                        </ul>
                    </li>
                </ul>

                <!-- User menu stays the same -->
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown"
                                role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i>
                                <span class="d-none d-md-inline me-2"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                                <i class="fas fa-chevron-down ms-1" style="font-size: 0.8em;"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                                <li><h6 class="dropdown-header"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></h6></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>profile.php">
                                        <i class="fas fa-user me-2"></i> Profile
                                    </a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>settings.php">
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
                            <a class="nav-link" href="<?php echo BASE_URL; ?>login.php">
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
        <?php if (isset($showBreadcrumb) && $showBreadcrumb): ?>
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
        <?php endif; ?>