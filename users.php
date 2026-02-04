<?php
// users.php - User Management Page
$current_page = 'users.php';
require_once 'bootstrap.php';

// Check authentication
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Check permissions - only admins can manage users
$adminRoles = ['admin', 'administrator', 'superadmin'];

// Check if user has any admin role
$hasAdminAccess = false;
if (isset($_SESSION['role'])) {
    $userRole = strtolower(trim($_SESSION['role']));
    $hasAdminAccess = in_array($userRole, $adminRoles);
}

if (!$hasAdminAccess) {
    // You could log this attempt
    error_log("Access denied to users.php for user: " . $_SESSION['username'] . " with role: " . $_SESSION['role']);

    // Redirect with error message
    $_SESSION['error'] = 'Administrator access required for user management.';
    header('Location: dashboard.php');
    exit();
}

// Include database connection fix - USE THE SAME AS DASHBOARD.PHP
require_once 'includes/database_fix.php';

// Get database connection
try {
    $db = new DatabaseFix();
    $conn = $db->getConnection(); // Use $conn instead of $pdo
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

require_once 'includes/functions.php';

$pageTitle = "User Management - aBility";
$showBreadcrumb = true;
$breadcrumbItems = [
    'Dashboard' => 'dashboard.php',
    'User Management' => ''
];

require_once 'views/partials/header.php';

// Handle actions
$action = $_GET['action'] ?? '';
$message = '';
$messageType = '';

// Handle user deletion
if ($action === 'delete' && isset($_GET['id'])) {
    $userId = (int)$_GET['id'];

    // Prevent deleting self
    if ($userId === $_SESSION['user_id']) {
        $message = 'You cannot delete your own account.';
        $messageType = 'danger';
    } else {
        try {
            $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $message = 'User deactivated successfully.';
                $messageType = 'success';

                // Log activity
                // Note: You need to update logActivity function to work with mysqli
                // For now, we'll skip or create a simple log
                $logStmt = $conn->prepare("INSERT INTO activity_log (user_id, action_type, description, ip_address) VALUES (?, 'user_deactivated', ?, ?)");
                $description = "Deactivated user ID: $userId";
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                $logStmt->bind_param("iss", $_SESSION['user_id'], $description, $ip);
                $logStmt->execute();
                $logStmt->close();
            } else {
                $message = 'User not found.';
                $messageType = 'warning';
            }
            $stmt->close();
        } catch (Exception $e) {
            $message = 'Error deactivating user: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Get users with role information
try {
    $result = $conn->query("
        SELECT u.*, COUNT(a.id) as activity_count
        FROM users u
        LEFT JOIN activity_log a ON u.id = a.user_id
        WHERE u.is_active = 1
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");

    if ($result) {
        $users = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
    } else {
        $users = [];
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    $users = [];
    $message = 'Error loading users: ' . $e->getMessage();
    $messageType = 'danger';
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

// Define departments
$departments = ['IT', 'Audio', 'Video', 'Lighting', 'Electrical', 'Rigging', 'Stock'];
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-users me-2"></i>User Management
        </h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-plus me-1"></i> Add User
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" id="searchInput" placeholder="Search users...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Role</label>
                    <select class="form-select" id="roleFilter">
                        <option value="">All Roles</option>
                        <?php foreach ($roles as $key => $label): ?>
                            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Department</label>
                    <select class="form-select" id="departmentFilter">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept; ?>"><?php echo $dept; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-secondary" id="clearFilters">
                        <i class="fas fa-times me-1"></i> Clear Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="usersTable">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Last Login</th>
                            <th>Activity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr data-role="<?php echo $user['role']; ?>" data-department="<?php echo $user['department']; ?>">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle me-2">
                                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($user['username']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?php
                                                            echo match ($user['role']) {
                                                                'admin' => 'danger',
                                                                'manager' => 'warning',
                                                                'user' => 'info',
                                                                default => 'secondary'
                                                            };
                                                            ?>">
                                        <?php echo $roles[$user['role']] ?? $user['role']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($user['department'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if (isset($user['last_login']) && !empty($user['last_login']) && $user['last_login'] != '0000-00-00 00:00:00'): ?>
                                        <small><?php echo date('M j, Y g:i A', strtotime($user['last_login'])); ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">Never</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark"><?php echo $user['activity_count']; ?> actions</span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-user-btn"
                                            data-id="<?php echo $user['id']; ?>"
                                            data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                            data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                            data-role="<?php echo $user['role']; ?>"
                                            data-department="<?php echo htmlspecialchars($user['department'] ?? ''); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-user-btn"
                                                data-id="<?php echo $user['id']; ?>"
                                                data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addUserForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role *</label>
                        <select class="form-select" name="role" required>
                            <option value="">Select Role</option>
                            <?php foreach ($roles as $key => $label): ?>
                                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select class="form-select" name="department">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept; ?>"><?php echo $dept; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Edit User Modal - LANDSCAPE VERSION -->
<!-- User Actions Modal - Combined Edit & Disable -->
<div class="modal fade" id="userActionsModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-user-cog me-2"></i>User Actions
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    <!-- Left Side: User Information -->
                    <div class="col-lg-5 bg-light">
                        <div class="p-4 h-100">
                            <div class="text-center mb-4">
                                <div class="avatar-circle-lg mx-auto mb-3">
                                    <span id="action_user_initials">U</span>
                                </div>
                                <h4 id="action_user_name" class="fw-bold mb-1">User Name</h4>
                                <p class="text-muted mb-2" id="action_user_email">email@example.com</p>
                                <div class="d-flex justify-content-center gap-2 mb-3">
                                    <span class="badge bg-primary" id="action_user_role">Role</span>
                                    <span class="badge bg-secondary" id="action_user_dept">Department</span>
                                </div>
                            </div>

                            <div class="user-stats">
                                <div class="row text-center mb-3">
                                    <div class="col-4">
                                        <div class="stat-number" id="action_activity_count">0</div>
                                        <div class="stat-label small">Activities</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="stat-number" id="action_items_count">0</div>
                                        <div class="stat-label small">Items</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="stat-number" id="action_last_login">-</div>
                                        <div class="stat-label small">Last Login</div>
                                    </div>
                                </div>
                            </div>

                            <div class="border-top pt-3">
                                <h6 class="fw-bold mb-3">Quick Actions</h6>
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-outline-primary" id="sendResetEmailBtn">
                                        <i class="fas fa-envelope me-2"></i> Send Password Reset
                                    </button>
                                    <button type="button" class="btn btn-outline-info" id="copyUserInfoBtn">
                                        <i class="fas fa-copy me-2"></i> Copy User Details
                                    </button>
                                    <button type="button" class="btn btn-outline-dark" id="viewActivityBtn">
                                        <i class="fas fa-history me-2"></i> View Activity Log
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side: Edit Form -->
                    <div class="col-lg-7">
                        <div class="p-4">
                            <form id="userActionsForm">
                                <input type="hidden" name="user_id" id="action_user_id">

                                <!-- Basic Information Section -->
                                <div class="mb-4">
                                    <h6 class="border-bottom pb-2 mb-3 fw-bold">Edit User Information</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Username *</label>
                                            <input type="text" class="form-control" name="username" id="action_username" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email *</label>
                                            <input type="email" class="form-control" name="email" id="action_email" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">New Password</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" name="password" id="action_password">
                                                <button class="btn btn-outline-secondary" type="button" id="action_generate_password">
                                                    <i class="fas fa-bolt"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Confirm Password</label>
                                            <input type="password" class="form-control" name="password_confirm" id="action_password_confirm">
                                        </div>
                                    </div>
                                </div>

                                <!-- Role & Department Section -->
                                <div class="mb-4">
                                    <h6 class="border-bottom pb-2 mb-3 fw-bold">Permissions & Access</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Role *</label>
                                            <select class="form-select" name="role" id="action_role" required>
                                                <option value="">Select Role</option>
                                                <?php foreach ($roles as $key => $label): ?>
                                                    <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Department</label>
                                            <select class="form-select" name="department" id="action_department">
                                                <option value="">Select Department</option>
                                                <?php foreach ($departments as $dept): ?>
                                                    <option value="<?php echo $dept; ?>"><?php echo $dept; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Account Status Section -->
                                <div class="mb-4">
                                    <h6 class="border-bottom pb-2 mb-3 fw-bold">Account Management</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="action_is_active" name="is_active" checked>
                                                <label class="form-check-label" for="action_is_active">
                                                    Account Active
                                                </label>
                                            </div>
                                            <small class="form-text text-muted">Toggle to enable/disable user access</small>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="action_reset_login" name="reset_login">
                                                <label class="form-check-label" for="action_reset_login">
                                                    Force password reset
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="action_email_notifications" name="email_notifications" checked>
                                                <label class="form-check-label" for="action_email_notifications">
                                                    Email notifications
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Danger Zone -->
                                <div class="border rounded p-3 bg-danger bg-opacity-10 border-danger">
                                    <h6 class="text-danger fw-bold mb-3">
                                        <i class="fas fa-exclamation-triangle me-2"></i>Danger Zone
                                    </h6>
                                    <p class="text-muted mb-3">These actions are irreversible. Use with caution.</p>
                                    <div class="d-grid gap-2">
                                        <button type="button" class="btn btn-outline-danger" id="disableAccountBtn">
                                            <i class="fas fa-user-slash me-2"></i> Disable User Account
                                        </button>
                                        <button type="button" class="btn btn-danger" id="deleteAccountBtn" disabled>
                                            <i class="fas fa-trash-alt me-2"></i> Delete User Account
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <button type="submit" form="userActionsForm" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal (Keep this for quick delete actions from table) -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to deactivate <strong id="delete_username"></strong>?</p>
                <p class="text-muted">This user will no longer be able to access the system.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" class="btn btn-danger" id="confirmDeleteBtn">Deactivate User</a>
            </div>
        </div>
    </div>
</div>

<!-- Add CSS Styles -->
<style>
    .avatar-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #007bff;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }

    .avatar-circle-lg {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #007bff, #6610f2);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: bold;
    }

    .stat-number {
        font-size: 1.5rem;
        font-weight: bold;
        color: #233643;
    }

    .stat-label {
        font-size: 0.8rem;
        color: #6c757d;
    }

    .bg-gradient-primary {
        background: linear-gradient(135deg, #233643 0%, #2c4760 100%) !important;
    }

    /* Landscape modal specific styles */
    .modal-xl .modal-content {
        border-radius: 0.5rem;
        overflow: hidden;
    }

    .modal-xl .modal-body {
        max-height: 70vh;
        overflow-y: auto;
    }

    @media (max-width: 992px) {
        .modal-xl .row.g-0 {
            flex-direction: column;
        }

        .modal-xl .col-lg-5,
        .modal-xl .col-lg-7 {
            width: 100%;
        }

        .modal-xl .col-lg-5.bg-light {
            border-bottom: 1px solid #dee2e6;
        }
    }
</style>


<script>
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#usersTable').DataTable({
        pageLength: 25,
        order: [[0, 'asc']],
        columnDefs: [{
            orderable: false,
            targets: 5
        }]
    });

    // Search functionality
    $('#searchInput').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Role filter
    $('#roleFilter').on('change', function() {
        const role = this.value;
        table.column(1).search(role ? `^${role}$` : '', true, false).draw();
    });

    // Department filter
    $('#departmentFilter').on('change', function() {
        const dept = this.value;
        table.column(2).search(dept).draw();
    });

    // Clear filters
    $('#clearFilters').on('click', function() {
        $('#searchInput').val('');
        $('#roleFilter').val('');
        $('#departmentFilter').val('');
        table.columns().search('').draw();
        table.search('').draw();
        table.order([[0, 'asc']]).draw();
    });

    // Fix: Add autocomplete attributes to password fields
    $('#action_password').attr('autocomplete', 'new-password');
    $('#action_password_confirm').attr('autocomplete', 'new-password');
    $('#addUserForm input[name="password"]').attr('autocomplete', 'new-password');

    // =============== CREATE USER ===============
    $('#addUserForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: 'api/user_create.php', // Create this file
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload(); // Reload page to show new user
                } else {
                    alert('Error: ' + (response.error || 'Failed to create user'));
                }
            },
            error: function() {
                alert('Server error occurred while creating user');
            }
        });
    });

    // =============== READ/VIEW USER (Open Edit Modal) ===============
    $('.edit-user-btn').on('click', function() {
        const userId = $(this).data('id');
        const username = $(this).data('username');
        const email = $(this).data('email');
        const role = $(this).data('role');
        const department = $(this).data('department');
        
        // Set basic user info
        $('#action_user_id').val(userId);
        $('#action_username').val(username);
        $('#action_email').val(email);
        $('#action_role').val(role);
        $('#action_department').val(department || '');
        
        // Set display info
        $('#action_user_name').text(username);
        $('#action_user_email').text(email);
        $('#action_user_role').text($('#action_role option:selected').text());
        $('#action_user_dept').text(department || 'N/A');
        $('#action_user_initials').text(username.charAt(0).toUpperCase());
        
        // Clear password fields
        $('#action_password').val('');
        $('#action_password_confirm').val('');
        
        // Load additional user stats via AJAX
        loadUserStats(userId);
        
        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('userActionsModal'));
        modal.show();
    });

    // Function to load user statistics
    function loadUserStats(userId) {
        $.ajax({
            url: 'api/user_stats.php?id=' + userId,
            method: 'GET',
            dataType: 'json',
            success: function(stats) {
                $('#action_activity_count').text(stats.activity_count || 0);
                $('#action_items_count').text(stats.items_count || 0);
                if (stats.last_login && stats.last_login !== '0000-00-00 00:00:00') {
                    const date = new Date(stats.last_login);
                    $('#action_last_login').text(date.toLocaleDateString());
                } else {
                    $('#action_last_login').text('Never');
                }
            }
        });
    }

    // =============== UPDATE USER ===============
    $('#userActionsForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        // Check if passwords match if provided
        const password = $('#action_password').val();
        const passwordConfirm = $('#action_password_confirm').val();
        
        if (password && password !== passwordConfirm) {
            alert('Passwords do not match!');
            return;
        }
        
        $.ajax({
            url: 'api/user_update.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('User updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (response.error || 'Failed to update user'));
                }
            },
            error: function() {
                alert('Server error occurred while updating user');
            }
        });
    });

    // =============== DELETE USER ===============
    $('.delete-user-btn').on('click', function() {
        const userId = $(this).data('id');
        const username = $(this).data('username');
        
        $('#delete_username').text(username);
        $('#confirmDeleteBtn').attr('href', 'users.php?action=delete&id=' + userId);
        
        const modal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
        modal.show();
    });

    // =============== ADDITIONAL FEATURES ===============
    
    // Generate random password
    $('#action_generate_password').on('click', function() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
        let password = '';
        for (let i = 0; i < 12; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        
        $('#action_password').val(password);
        $('#action_password_confirm').val(password);
        
        // Show password temporarily
        $('#action_password').attr('type', 'text');
        $('#action_password_confirm').attr('type', 'text');
        setTimeout(() => {
            $('#action_password').attr('type', 'password');
            $('#action_password_confirm').attr('type', 'password');
        }, 2000);
    });

    // Send password reset email
    $('#sendResetEmailBtn').on('click', function() {
        const userId = $('#action_user_id').val();
        
        $.ajax({
            url: 'api/send_password_reset.php',
            method: 'POST',
            data: { user_id: userId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Password reset email sent successfully!');
                } else {
                    alert('Error: ' + (response.error || 'Failed to send email'));
                }
            }
        });
    });

    // Copy user info
    $('#copyUserInfoBtn').on('click', function() {
        const userInfo = `Username: ${$('#action_username').val()}\nEmail: ${$('#action_email').val()}\nRole: ${$('#action_role option:selected').text()}`;
        
        navigator.clipboard.writeText(userInfo).then(function() {
            alert('User information copied to clipboard!');
        }, function(err) {
            console.error('Could not copy text: ', err);
        });
    });

    // Disable account
    $('#disableAccountBtn').on('click', function() {
        if (confirm('Are you sure you want to disable this account? The user will not be able to login.')) {
            const userId = $('#action_user_id').val();
            
            $.ajax({
                url: 'api/user_disable.php',
                method: 'POST',
                data: { user_id: userId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Account disabled successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + (response.error || 'Failed to disable account'));
                    }
                }
            });
        }
    });

    // View activity log
    $('#viewActivityBtn').on('click', function() {
        const userId = $('#action_user_id').val();
        window.open('activity_log.php?user_id=' + userId, '_blank');
    });
});
</script>
<?php require_once 'views/partials/footer.php'; ?>