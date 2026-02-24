<?php
// users.php - User Management
$current_page = 'users.php';
require_once 'bootstrap.php';


// users.php - Add this right after require_once 'bootstrap.php' (around line 5)
error_log("=== DEBUG SESSION ===");
error_log("User ID: " . ($_SESSION['user_id'] ?? 'NOT SET'));
error_log("Username: " . ($_SESSION['username'] ?? 'NOT SET'));
error_log("Role: " . ($_SESSION['role'] ?? 'NOT SET'));
error_log("User Role: " . ($_SESSION['user_role'] ?? 'NOT SET'));
error_log("===================");


// Check authentication
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Check if user is admin (only admins can manage users)
if (!isAdmin()) {
    $_SESSION['toast_message'] = 'You do not have permission to access user management';
    $_SESSION['toast_type'] = 'error';
    header('Location: dashboard.php');
    exit();
}

require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Get database connection
$conn = getConnection();

$pageTitle = "User Management - aBility";
$showBreadcrumb = true;
$breadcrumbItems = [
    'Dashboard' => 'dashboard.php',
    'User Management' => ''
];

require_once 'views/partials/header.php';

// Handle actions
$message = '';
$messageType = '';

// Delete/Deactivate user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = $_GET['delete'];

    // Don't allow deleting yourself
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['toast_message'] = 'You cannot deactivate your own account!';
        $_SESSION['toast_type'] = 'error';
    } else {
        try {
            $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
            $stmt->bind_param("i", $user_id);

            if ($stmt->execute()) {
                $_SESSION['toast_message'] = 'User deactivated successfully!';
                $_SESSION['toast_type'] = 'success';
            }
            $stmt->close();
        } catch (Exception $e) {
            $_SESSION['toast_message'] = 'Error deactivating user: ' . $e->getMessage();
            $_SESSION['toast_type'] = 'error';
        }
    }
    header('Location: users.php');
    exit();
}

// Activate user
if (isset($_GET['activate']) && is_numeric($_GET['activate'])) {
    $user_id = $_GET['activate'];

    try {
        $stmt = $conn->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            $_SESSION['toast_message'] = 'User activated successfully!';
            $_SESSION['toast_type'] = 'success';
        }
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['toast_message'] = 'Error activating user: ' . $e->getMessage();
        $_SESSION['toast_type'] = 'error';
    }
    header('Location: users.php');
    exit();
}

// Handle form submission for add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_user' || $action === 'edit_user') {
        $user_id = $_POST['user_id'] ?? null;
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validate
        $errors = [];
        if (empty($username)) $errors[] = 'Username is required';
        if (empty($email)) $errors[] = 'Email is required';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format';

        if ($action === 'add_user' && empty($password)) {
            $errors[] = 'Password is required for new users';
        }

        if (!empty($password) && $password !== $confirm_password) {
            $errors[] = 'Passwords do not match';
        }

        if (!empty($password) && strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }

        if (empty($errors)) {
            try {
                // Check if username/email exists
                $check_sql = "SELECT id FROM users WHERE (username = ? OR email = ?)";
                $check_params = [$username, $email];
                $check_types = "ss";

                if ($user_id) {
                    $check_sql .= " AND id != ?";
                    $check_params[] = $user_id;
                    $check_types .= "i";
                }

                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param($check_types, ...$check_params);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows > 0) {
                    $_SESSION['toast_message'] = 'Username or email already exists!';
                    $_SESSION['toast_type'] = 'error';
                } else {
                    if ($action === 'add_user') {
                        // Insert new user - match columns with values
                        $insert_sql = "INSERT INTO users (username, email, password, full_name, phone, department, role, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                        // Columns:       1        2       3        4        5       6         7        8
                        // Values:        ?        ?       ?        ?        ?       ?         ?      NOW()

                        $insert_stmt = $conn->prepare($insert_sql);

                        if (!$insert_stmt) {
                            die("Error preparing statement: " . $conn->error . "<br>SQL: " . $insert_sql);
                        }

                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                        // Now bind 7 parameters for the 7 placeholders
                        $insert_stmt->bind_param(
                            "sssssss",
                            $username,      // 1st ?
                            $email,         // 2nd ?
                            $hashed_password, // 3rd ?
                            $full_name,     // 4th ?
                            $phone,         // 5th ?
                            $department,    // 6th ?
                            $role           // 7th ?
                        );
                        // created_at uses NOW() so no binding needed

                        if ($insert_stmt->execute()) {
                            $new_user_id = $insert_stmt->insert_id;
                            $_SESSION['toast_message'] = 'User added successfully!';
                            $_SESSION['toast_type'] = 'success';
                        } else {
                            die("Error executing statement: " . $insert_stmt->error);
                        }
                        $insert_stmt->close();
                    }
                }
                $check_stmt->close();
            } catch (Exception $e) {
                $_SESSION['toast_message'] = 'Error: ' . $e->getMessage();
                $_SESSION['toast_type'] = 'error';
            }
        } else {
            $_SESSION['toast_message'] = implode('<br>', $errors);
            $_SESSION['toast_type'] = 'error';
        }

        header('Location: users.php');
        exit();
    }
}

// Get all users
$users = [];
try {
    $sql = "SELECT * FROM users ORDER BY created_at DESC";
    $result = $conn->query($sql);
    if ($result) {
        $users = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    error_log("Error fetching users: " . $e->getMessage());
}

// Get user for editing
$edit_user = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_GET['edit']);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_user = $result->fetch_assoc();
    $stmt->close();
}

// Define roles
$roles = [
    'admin' => 'Administrator',
    'manager' => 'Manager',
    'user' => 'User',
    'stock_manager' => 'Stock Manager',
    'stock_controller' => 'Stock Controller',
    'tech_lead' => 'Tech Lead',
    'technician' => 'Technician',
    'driver' => 'Driver'
];

$departments = [
    'audio' => 'Audio',
    'video' => 'Video',
    'lighting' => 'Lighting',
    'translation' => 'Translation',
    'it' => 'IT',
    'rigging' => 'Rigging',
    'electrical' => 'Electrical',
    'furniture' => 'Furniture',
    'warehouse' => 'Warehouse',
    'admin' => 'Administration'
];
?>



<style>
    :root {
        --primary-color: #234c6a;
        --primary-light: #2c5a7a;
        --primary-dark: #1a3a4f;
        --success-color: #28a745;
        --danger-color: #dc3545;
        --warning-color: #ffc107;
    }

    .users-container {
        padding: 2rem 1.5rem;
    }

    .page-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .page-header h1 {
        margin: 0;
        font-size: 2rem;
    }

    .btn-add {
        background: white;
        color: var(--primary-color);
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-add:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        color: var(--primary-color);
    }

    .user-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        border: 1px solid #e9ecef;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }

    .user-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(35, 76, 106, 0.1);
        border-color: var(--primary-color);
    }

    .user-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: var(--primary-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1.2rem;
    }

    .user-info h4 {
        margin: 0 0 0.25rem;
        font-size: 1.1rem;
    }

    .user-info p {
        margin: 0;
        color: #6c757d;
        font-size: 0.9rem;
    }

    .role-badge {
        background: rgba(35, 76, 106, 0.1);
        color: var(--primary-color);
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 500;
        display: inline-block;
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .status-active {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }

    .status-inactive {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
        justify-content: flex-end;
    }

    .btn-icon {
        width: 35px;
        height: 35px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        border: 1px solid #e9ecef;
        color: #6c757d;
        background: white;
        text-decoration: none;
    }

    .btn-icon:hover {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
        transform: translateY(-2px);
    }

    .btn-icon.delete:hover {
        background: var(--danger-color);
        border-color: var(--danger-color);
        color: white;
    }

    .btn-icon.activate:hover {
        background: var(--success-color);
        border-color: var(--success-color);
        color: white;
    }

    /* Modal Styles */
    .modal-content {
        border-radius: 15px;
        border: none;
    }

    .modal-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        color: white;
        border-radius: 15px 15px 0 0;
        padding: 1.5rem;
    }

    .modal-header .btn-close {
        filter: brightness(0) invert(1);
    }

    .modal-body {
        padding: 2rem;
    }

    .modal-footer {
        border-top: 1px solid #e9ecef;
        padding: 1.5rem;
    }

    .form-label {
        font-weight: 500;
        color: #495057;
        margin-bottom: 0.5rem;
    }

    .form-control,
    .form-select {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 0.6rem 1rem;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(35, 76, 106, 0.25);
    }

    /* Search and Filter */
    .search-section {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
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
        border-left-color: var(--success-color);
    }

    .toast-notification.error {
        border-left-color: var(--danger-color);
    }

    .toast-notification.warning {
        border-left-color: var(--warning-color);
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
        color: var(--success-color);
    }

    .toast-notification.error .toast-icon {
        background: rgba(220, 53, 69, 0.1);
        color: var(--danger-color);
    }

    .toast-notification.warning .toast-icon {
        background: rgba(255, 193, 7, 0.1);
        color: var(--warning-color);
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

    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            text-align: center;
        }

        .action-buttons {
            justify-content: flex-start;
            margin-top: 1rem;
        }

        .toast-container {
            width: 90%;
        }

        .toast-notification {
            min-width: auto;
        }
    }

    /* DataTable Custom Theme */
    :root {
        --dt-primary: #234c6a;
        --dt-primary-light: #2c5a7a;
        --dt-primary-dark: #1a3a4f;
        --dt-accent: #e9ecef;
        --dt-text: #495057;
        --dt-header-text: white;
    }

    /* DataTable Container */
    .dataTables_wrapper {
        padding: 20px 0;
        font-family: "Titillium Web", sans-serif;
    }

    /* Table Header */
    table.dataTable thead th {
        background: linear-gradient(135deg, var(--dt-primary) 0%, var(--dt-primary-light) 100%);
        color: var(--dt-header-text);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        padding: 15px 10px;
        border-bottom: none;
        border-right: 1px solid rgba(255, 255, 255, 0.1);
    }

    table.dataTable thead th:last-child {
        border-right: none;
    }

    /* Table Header Sorting Icons */
    table.dataTable thead .sorting:after,
    table.dataTable thead .sorting_asc:after,
    table.dataTable thead .sorting_desc:after {
        color: white;
        opacity: 0.8;
    }

    table.dataTable thead .sorting:hover:after,
    table.dataTable thead .sorting_asc:after,
    table.dataTable thead .sorting_desc:after {
        opacity: 1;
    }

    /* Table Body */
    table.dataTable tbody td {
        padding: 12px 10px;
        color: var(--dt-text);
        border-bottom: 1px solid #e9ecef;
        vertical-align: middle;
    }

    /* Table Rows Hover */
    table.dataTable tbody tr:hover {
        background-color: rgba(35, 76, 106, 0.05) !important;
        cursor: pointer;
    }

    /* Table Striped Rows */
    table.dataTable.stripe tbody tr.odd,
    table.dataTable.display tbody tr.odd {
        background-color: rgba(0, 0, 0, 0.02);
    }

    table.dataTable.stripe tbody tr.odd.selected,
    table.dataTable.display tbody tr.odd.selected {
        background-color: rgba(35, 76, 106, 0.1);
    }

    /* Table Borders */
    table.dataTable {
        border-collapse: separate !important;
        border-spacing: 0;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    }

    /* Table Footer */
    table.dataTable tfoot th {
        background-color: #f8f9fa;
        color: var(--dt-primary);
        font-weight: 600;
        padding: 12px 10px;
        border-top: 2px solid var(--dt-primary);
    }

    /* Pagination Controls */
    .dataTables_paginate {
        margin-top: 20px;
    }

    .dataTables_paginate .paginate_button {
        padding: 8px 15px !important;
        margin: 0 3px;
        border-radius: 6px !important;
        border: 1px solid #e9ecef !important;
        background: white !important;
        color: var(--dt-primary) !important;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .dataTables_paginate .paginate_button:hover {
        background: linear-gradient(135deg, var(--dt-primary) 0%, var(--dt-primary-light) 100%) !important;
        color: white !important;
        border-color: var(--dt-primary) !important;
        transform: translateY(-2px);
        box-shadow: 0 5px 10px rgba(35, 76, 106, 0.2);
    }

    .dataTables_paginate .paginate_button.current {
        background: linear-gradient(135deg, var(--dt-primary) 0%, var(--dt-primary-light) 100%) !important;
        color: white !important;
        border-color: var(--dt-primary) !important;
        font-weight: 600;
    }

    /* Length Menu */
    .dataTables_length select {
        padding: 5px 10px;
        border-radius: 6px;
        border: 1px solid #e9ecef;
        color: var(--dt-primary);
        font-weight: 500;
        outline: none;
        cursor: pointer;
    }

    .dataTables_length select:focus {
        border-color: var(--dt-primary);
        box-shadow: 0 0 0 3px rgba(35, 76, 106, 0.1);
    }

    /* Search Box */
    .dataTables_filter input {
        padding: 7px 15px;
        border-radius: 20px;
        border: 1px solid #e9ecef;
        outline: none;
        transition: all 0.3s ease;
    }

    .dataTables_filter input:focus {
        border-color: var(--dt-primary);
        box-shadow: 0 0 0 3px rgba(35, 76, 106, 0.1);
        width: 250px;
    }

    .dataTables_filter label {
        color: var(--dt-text);
        font-weight: 500;
    }

    /* Info Text */
    .dataTables_info {
        color: var(--dt-text);
        padding-top: 10px;
        font-size: 0.9rem;
    }

    /* Processing Indicator */
    .dataTables_processing {
        background: linear-gradient(135deg, var(--dt-primary) 0%, var(--dt-primary-light) 100%) !important;
        color: white !important;
        border: none !important;
        box-shadow: 0 5px 20px rgba(35, 76, 106, 0.3);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .dataTables_wrapper {
            overflow-x: auto;
        }

        table.dataTable {
            min-width: 800px;
        }
    }

    /* Custom Badge Styles for Table */
    .user-role-badge {
        background: rgba(35, 76, 106, 0.1);
        color: var(--dt-primary);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
        display: inline-block;
    }

    .user-status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
        display: inline-block;
    }

    .user-status-badge.active {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }

    .user-status-badge.inactive {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }

    /* Action Buttons */
    .table-action-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        border: 1px solid #e9ecef;
        color: var(--dt-primary);
        background: white;
        text-decoration: none;
        margin: 0 2px;
    }

    .table-action-btn:hover {
        background: var(--dt-primary);
        color: white;
        border-color: var(--dt-primary);
        transform: translateY(-2px);
    }

    .table-action-btn.delete:hover {
        background: #dc3545;
        border-color: #dc3545;
        color: white;
    }
</style>

<div class="users-container">
    <!-- Toast Container for Notifications -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1><i class="fas fa-users me-2"></i>User Management</h1>
            <p class="mb-0 opacity-75">Manage user accounts and permissions</p>
        </div>
        <button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#userModal">
            <i class="fas fa-plus me-2"></i>Add New User
        </button>
    </div>

    <!-- Search Section -->
    <div class="search-section">
        <div class="row g-3">
            <div class="col-md-6">
                <input type="text" class="form-control" id="searchInput" placeholder="Search by name, username, or email...">
            </div>
            <div class="col-md-3">
                <select class="form-select" id="roleFilter">
                    <option value="">All Roles</option>
                    <?php foreach ($roles as $key => $value): ?>
                        <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="table-responsive">
        <table class="table table-hover" id="usersTable" style="width:100%">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Contact</th>
                    <th>Department</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user_item): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="user-avatar me-2" style="width: 35px; height: 35px; font-size: 0.9rem;">
                                    <?php echo strtoupper(substr($user_item['full_name'] ?? $user_item['username'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($user_item['full_name'] ?? $user_item['username']); ?></div>
                                    <small class="text-muted">@<?php echo htmlspecialchars($user_item['username']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div><i class="fas fa-envelope me-1 text-muted"></i><?php echo htmlspecialchars($user_item['email']); ?></div>
                            <?php if (!empty($user_item['phone'])): ?>
                                <small><i class="fas fa-phone me-1 text-muted"></i><?php echo htmlspecialchars($user_item['phone']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="user-role-badge">
                                <?php echo htmlspecialchars($user_item['department'] ?? 'N/A'); ?>
                            </span>
                        </td>
                        <td>
                            <span class="user-role-badge">
                                <i class="fas fa-user-tag me-1"></i>
                                <?php echo $roles[$user_item['role']] ?? $user_item['role']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="user-status-badge <?php echo $user_item['is_active'] ? 'active' : 'inactive'; ?>">
                                <i class="fas fa-circle me-1"></i>
                                <?php echo $user_item['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="text-muted small">
                                <i class="fas fa-calendar me-1"></i>
                                <?php echo date('M j, Y', strtotime($user_item['created_at'])); ?>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="?edit=<?php echo $user_item['id']; ?>" class="table-action-btn" title="Edit User" data-bs-toggle="modal" data-bs-target="#userModal" data-user-id="<?php echo $user_item['id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <?php if ($user_item['is_active']): ?>
                                    <a href="?delete=<?php echo $user_item['id']; ?>" class="table-action-btn delete" title="Deactivate User" onclick="return confirm('Are you sure you want to deactivate this user?')">
                                        <i class="fas fa-ban"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="?activate=<?php echo $user_item['id']; ?>" class="table-action-btn" title="Activate User" onclick="return confirm('Activate this user?')" style="color: #28a745;">
                                        <i class="fas fa-check-circle"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (empty($users)): ?>
        <div class="text-center py-5">
            <i class="fas fa-users fa-3x text-muted mb-3"></i>
            <h5>No Users Found</h5>
            <p class="text-muted">Click "Add New User" to create your first user.</p>
        </div>
    <?php endif; ?>
</div>

<!-- profile.php - Add to sidebar or main content -->

<!-- Access Summary Card -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0" style="color: #234c6a;">
            <i class="fas fa-key me-2"></i>Your System Access
        </h6>
    </div>
    <div class="card-body">
        <?php
        // Calculate page count for current user (same calculation as above)
        $total_pages = 14;
        $accessible_pages = 5;

        if (hasAnyRole(['admin', 'manager', 'stock_manager', 'stock_controller', 'tech_lead', 'technician', 'user', 'driver'])) {
            $accessible_pages++;
        }
        if (hasAnyRole(['admin', 'manager', 'stock_manager', 'stock_controller', 'tech_lead'])) {
            $accessible_pages++;
        }
        if (hasAnyRole(['admin', 'manager', 'stock_manager'])) {
            $accessible_pages++;
        }
        if (hasAnyRole(['admin', 'manager', 'stock_controller'])) {
            $accessible_pages++;
        }
        if (hasAnyRole(['admin', 'manager'])) {
            $accessible_pages += 3;
        }
        if (isAdmin()) {
            $accessible_pages += 2;
        }
        ?>

        <div class="row">
            <div class="col-6">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle p-2 me-2" style="background: rgba(40, 167, 69, 0.1);">
                        <i class="fas fa-check-circle text-success"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Accessible</div>
                        <strong><?php echo $accessible_pages; ?></strong> pages
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle p-2 me-2" style="background: rgba(220, 53, 69, 0.1);">
                        <i class="fas fa-times-circle text-danger"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Restricted</div>
                        <strong><?php echo $total_pages - $accessible_pages; ?></strong> pages
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress bar -->
        <div class="progress mb-3" style="height: 8px;">
            <div class="progress-bar" role="progressbar"
                style="width: <?php echo ($accessible_pages / $total_pages) * 100; ?>%; background: linear-gradient(135deg, #234c6a 0%, #2c5a7a 100%);"
                aria-valuenow="<?php echo $accessible_pages; ?>"
                aria-valuemin="0"
                aria-valuemax="<?php echo $total_pages; ?>">
            </div>
        </div>

        <!-- Role badge -->
        <div class="d-flex justify-content-between align-items-center">
            <span class="small text-muted">Your role:</span>
            <span class="badge" style="background: #234c6a; padding: 6px 12px;">
                <i class="fas <?php echo $role_icon; ?> me-1"></i>
                <?php echo getRoleDisplayName(getUserRole()); ?>
            </span>
        </div>
    </div>
</div>

<!-- User Modal (Add/Edit) -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>
                    <span id="modalTitle">Add New User</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="userForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add_user">
                    <input type="hidden" name="user_id" id="userId" value="">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Username *</label>
                                <input type="text" class="form-control" name="username" id="username" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" id="email" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" name="full_name" id="full_name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone" id="phone">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Department</label>
                                <select class="form-select" name="department" id="department">
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $key => $value): ?>
                                        <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Role *</label>
                                <select class="form-select" name="role" id="role" required>
                                    <?php foreach ($roles as $key => $value): ?>
                                        <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row" id="passwordFields">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" id="passwordLabel">Password *</label>
                                <input type="password" class="form-control" name="password" id="password">
                                <small class="text-muted">Min. 6 characters</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" name="confirm_password" id="confirm_password">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save User
                    </button>
                </div>
            </form>
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


<!-- jQuery MUST come first -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Then DataTables CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

<!-- Then DataTables JS (jQuery must be loaded before these) -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>


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

        // Handle edit modal
        $('#userModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var userId = button.data('user-id');

            if (userId) {
                // Edit mode
                $('#modalTitle').text('Edit User');
                $('#formAction').val('edit_user');
                $('#passwordLabel').text('New Password (optional)');
                $('#password').prop('required', false);

                // Fetch user data via AJAX
                $.ajax({
                    url: 'get_user.php',
                    method: 'GET',
                    data: {
                        id: userId
                    },
                    dataType: 'json',
                    success: function(user) {
                        $('#userId').val(user.id);
                        $('#username').val(user.username);
                        $('#email').val(user.email);
                        $('#full_name').val(user.full_name || ''); // Changed from user.fullname to user.full_name
                        $('#phone').val(user.phone || '');
                        $('#department').val(user.department || '');
                        $('#role').val(user.role);
                    },
                    error: function(xhr) {
                        if (xhr.status === 403) {
                            showToast('You do not have permission to edit users', 'error');
                            $('#userModal').modal('hide');
                        } else {
                            showToast('Error loading user data', 'error');
                        }
                    }
                });
            } else {
                // Add mode
                $('#modalTitle').text('Add New User');
                $('#formAction').val('add_user');
                $('#passwordLabel').text('Password *');
                $('#password').prop('required', true);
                $('#userForm')[0].reset();
                $('#userId').val('');
            }
        });

        // Reset form when modal is hidden
        $('#userModal').on('hidden.bs.modal', function() {
            $('#userForm')[0].reset();
            $('#userId').val('');
        });

        // Search and filter functionality
        function filterUsers() {
            var searchTerm = $('#searchInput').val().toLowerCase();
            var roleFilter = $('#roleFilter').val();
            var statusFilter = $('#statusFilter').val();

            $('.user-item').each(function() {
                var $item = $(this);
                var username = $item.data('username');
                var email = $item.data('email');
                var full_name = $item.data('full_name');
                var role = $item.data('role');
                var status = $item.data('status');

                var matchesSearch = searchTerm === '' ||
                    (username && username.includes(searchTerm)) ||
                    (email && email.includes(searchTerm)) ||
                    (full_name && full_name.includes(searchTerm));

                var matchesRole = roleFilter === '' || role === roleFilter;
                var matchesStatus = statusFilter === '' || status === statusFilter;

                if (matchesSearch && matchesRole && matchesStatus) {
                    $item.show();
                } else {
                    $item.hide();
                }
            });
        }

        $('#searchInput').on('keyup', filterUsers);
        $('#roleFilter, #statusFilter').on('change', filterUsers);

        // Form validation
        $('#userForm').on('submit', function(e) {
            var password = $('#password').val();
            var confirm = $('#confirm_password').val();
            var action = $('#formAction').val();

            if (password || confirm) {
                if (password.length < 6) {
                    e.preventDefault();
                    showToast('Password must be at least 6 characters long', 'error');
                    return false;
                }
                if (password !== confirm) {
                    e.preventDefault();
                    showToast('Passwords do not match', 'error');
                    return false;
                }
            } else if (action === 'add_user' && !password) {
                e.preventDefault();
                showToast('Password is required for new users', 'error');
                return false;
            }
        });
    });

    // Initialize DataTable
    if ($.fn.DataTable) {
        $('#usersTable').DataTable({
            pageLength: 5,
            lengthMenu: [
                [5, 10, 15, -1],
                [5, 10, 15, 25, 50, "All"]
            ],
            order: [
                [5, 'asc']
            ], // Sort by join date descending
            language: {
                search: "<i class='fas fa-search me-1'></i> Search:",
                lengthMenu: "Show _MENU_ users",
                info: "Showing _START_ to _END_ of _TOTAL_ users",
                infoEmpty: "Showing 0 to 0 of 0 users",
                infoFiltered: "(filtered from _MAX_ total users)",
                paginate: {
                    first: '<i class="fas fa-angle-double-left"></i>',
                    previous: '<i class="fas fa-angle-left"></i>',
                    next: '<i class="fas fa-angle-right"></i>',
                    last: '<i class="fas fa-angle-double-right"></i>'
                }
            },
            columnDefs: [{
                    orderable: false,
                    targets: [6]
                }, // Disable sorting on actions column
                {
                    className: "align-middle",
                    targets: "_all"
                }
            ],
            // Remove search and filter from specific columns
            initComplete: function() {
                // Add custom styling to search input
                $('.dataTables_filter input').attr('placeholder', 'Search users...');
            }
        });
    }
</script>

<?php require_once 'views/partials/footer.php'; ?>