<?php
// dashboard_sections.php
$current_page = 'dashboard_sections.php';
require_once 'bootstrap.php';

// Include functions.php
require_once 'includes/functions.php';

// Check for error parameter - NOW session is available
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

$pageTitle = "Dashboard - aBility";
$showBreadcrumb = true;
$breadcrumbItems = [
    'Dashboard' => ''
];

require_once 'views/partials/header.php';
?>

<style>
    /* Dashboard Styles */
    .dashboard-container {
        padding: 2rem 0;
    }

    .welcome-section {
        background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%);
        color: white;
        padding: 3rem;
        border-radius: 20px;
        margin-bottom: 3rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .welcome-section h1 {
        font-size: 2.5rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .welcome-section p {
        font-size: 1.1rem;
        opacity: 0.9;
    }

    .section-title {
        text-align: center;
        margin-bottom: 3rem;
    }

    .section-title h2 {
        font-size: 2rem;
        color: #234c6a;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .section-title p {
        color: #6c757d;
        font-size: 1.1rem;
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
</style>

<div class="dashboard-container">
    <!-- Toast Container for Notifications -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Welcome Section -->
    <div class="welcome-section aos-init aos-animate" data-aos="fade-up">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>! 👋</h1>
                <p>Manage your inventory, track equipment, and monitor activities all in one place. Choose a module below to get started.</p>
            </div>

            <div class="col-lg-4 text-lg-end">
                <i class="fas fa-cogs" style="font-size: 5rem; opacity: 0.5;"></i>
            </div>
        </div>
    </div>

    <!-- Section Title -->
    <div class="section-title aos-init aos-animate" data-aos="fade-up">
        <h2>Choose Your Module</h2>
        <p>Select the section you want to manage</p>
    </div>

    <!-- Icon Boxes Grid -->
    <div class="row gy-4">
        <!-- Dashboard -->
        <div class="col-md-6 col-lg-3 aos-init aos-animate" data-aos="zoom-out" data-aos-delay="100">
            <div class="icon-box" onclick="window.location.href='dashboard_sections.php'">
                <div class="icon"><i class="fas fa-tachometer-alt"></i></div>
                <h4 class="title"><a href="dashboard_sections.php">Dashboard</a></h4>
                <p class="description">View your dashboard and access all modules from one central location</p>
            </div>
        </div>

        <!-- Events -->
        <div class="col-md-6 col-lg-3 aos-init aos-animate" data-aos="zoom-out" data-aos-delay="400">
            <div class="icon-box" onclick="window.location.href='events.php'">
                <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                <h4 class="title"><a href="events.php">Events</a></h4>
                <p class="description">Plan and manage events, assign resources, and track schedules</p>
            </div>
        </div>

        <!-- Equipment -->
        <div class="col-md-6 col-lg-3 aos-init aos-animate" data-aos="zoom-out" data-aos-delay="300">
            <div class="icon-box" onclick="window.location.href='items.php'">
                <div class="icon"><i class="fas fa-boxes"></i></div>
                <h4 class="title"><a href="items.php">Equipment</a></h4>
                <p class="description">Track inventory, manage equipment, and monitor stock levels</p>
            </div>
        </div>

        <!-- Import -->
        <div class="col-md-6 col-lg-3 aos-init aos-animate" data-aos="zoom-out" data-aos-delay="100">
            <div class="icon-box" onclick="window.location.href='import_items.php'">
                <div class="icon"><i class="fas fa-file-import"></i></div>
                <h4 class="title"><a href="import_items.php">Import</a></h4>
                <p class="description">Bulk import items from CSV or Excel files</p>
            </div>
        </div>

        <!-- Scan -->
        <div class="col-md-6 col-lg-3 aos-init aos-animate" data-aos="zoom-out" data-aos-delay="200">
            <div class="icon-box" onclick="window.location.href='scan.php'">
                <div class="icon"><i class="fas fa-qrcode"></i></div>
                <h4 class="title"><a href="scan.php">Scan</a></h4>
                <p class="description">Scan QR codes to quickly access item information</p>
            </div>
        </div>

        <!-- Reports -->
        <div class="col-md-6 col-lg-3 aos-init aos-animate" data-aos="zoom-out" data-aos-delay="300">
            <div class="icon-box" onclick="window.location.href='reports.php'">
                <div class="icon"><i class="fas fa-chart-bar"></i></div>
                <h4 class="title"><a href="reports.php">Reports</a></h4>
                <p class="description">Generate reports and analyze inventory data</p>
            </div>
        </div>

        <!-- Show User Management only to admin -->
        <?php if (isAdmin()): ?>
            <div class="col-md-6 col-lg-3">
                <div class="icon-box" onclick="window.location.href='users.php'">
                    <div class="icon"><i class="fas fa-users"></i></div>
                    <h4 class="title"><a href="users.php">User Management</a></h4>
                    <p class="description">Manage user accounts, roles, and permissions</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Technicians -->
        <div class="col-md-6 col-lg-3 aos-init aos-animate" data-aos="zoom-out" data-aos-delay="200">
            <div class="icon-box" onclick="window.location.href='technicians.php'">
                <div class="icon"><i class="fas fa-tools"></i></div>
                <h4 class="title"><a href="technicians.php">Technicians</a></h4>
                <p class="description">Manage technician profiles, assign roles, and track their activities</p>
            </div>
        </div>

        <!-- Stock Locations -->
        <div class="col-md-6 col-lg-3 aos-init aos-animate" data-aos="zoom-out" data-aos-delay="100">
            <div class="icon-box" onclick="window.location.href='stock_locations.php'">
                <div class="icon"><i class="fas fa-warehouse"></i></div>
                <h4 class="title"><a href="stock_locations.php">Stock Locations</a></h4>
                <p class="description">Manage warehouse and storage locations</p>
            </div>
        </div>

        <!-- Batch History -->
        <div class="col-md-6 col-lg-3 aos-init aos-animate" data-aos="zoom-out" data-aos-delay="200">
            <div class="icon-box" onclick="window.location.href='batch_history.php'">
                <div class="icon"><i class="fas fa-history"></i></div>
                <h4 class="title"><a href="batch_history.php">Batch History</a></h4>
                <p class="description">Track batch operations and historical changes</p>
            </div>
        </div>

        <!-- Settings -->
        <div class="col-md-6 col-lg-3 aos-init aos-animate" data-aos="zoom-out" data-aos-delay="300">
            <div class="icon-box" onclick="window.location.href='settings.php'">
                <div class="icon"><i class="fas fa-cog"></i></div>
                <h4 class="title"><a href="settings.php">Settings</a></h4>
                <p class="description">Configure system preferences and options</p>
            </div>
        </div>

        <!-- Profile -->
        <div class="col-md-6 col-lg-3 aos-init aos-animate" data-aos="zoom-out" data-aos-delay="400">
            <div class="icon-box" onclick="window.location.href='profile.php'">
                <div class="icon"><i class="fas fa-user-circle"></i></div>
                <h4 class="title"><a href="profile.php">Profile</a></h4>
                <p class="description">View and edit your personal information</p>
            </div>
        </div>
    </div>

    <!-- Recent Activity Section (Optional) -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Recent Activity</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex align-items-center">
                            <div class="me-3">
                                <span class="badge bg-success p-2"><i class="fas fa-plus"></i></span>
                            </div>
                            <div class="flex-grow-1">
                                <strong>New item added:</strong> Projector X100 (SN: PRJ-001)
                            </div>
                            <small class="text-muted">5 minutes ago</small>
                        </div>
                        <div class="list-group-item d-flex align-items-center">
                            <div class="me-3">
                                <span class="badge bg-warning p-2"><i class="fas fa-edit"></i></span>
                            </div>
                            <div class="flex-grow-1">
                                <strong>Item updated:</strong> Laptop Dell XPS (SN: LAP-002)
                            </div>
                            <small class="text-muted">1 hour ago</small>
                        </div>
                        <div class="list-group-item d-flex align-items-center">
                            <div class="me-3">
                                <span class="badge bg-danger p-2"><i class="fas fa-trash"></i></span>
                            </div>
                            <div class="flex-grow-1">
                                <strong>Item removed:</strong> Broken microphone (SN: MIC-003)
                            </div>
                            <small class="text-muted">3 hours ago</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
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