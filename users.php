<?php
// users.php - User Management Page
$current_page = 'users.php';
require_once 'bootstrap.php';

// DEBUG: Check what's in the session
error_log("======= USER SESSION DEBUG =======");
error_log("Session ID: " . session_id());
error_log("User logged in: " . (isset($_SESSION['logged_in']) ? 'Yes' : 'No'));
error_log("User ID: " . ($_SESSION['user_id'] ?? 'Not set'));
error_log("Username: " . ($_SESSION['username'] ?? 'Not set'));
error_log("User role: " . ($_SESSION['role'] ?? 'Not set'));
error_log("Session data: " . print_r($_SESSION, true));
error_log("==================================");

// Check authentication
if (!isLoggedIn()) {
    error_log("User not logged in - redirecting to login.php");
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

    error_log("User role check: '" . $userRole . "' in admin roles? " . ($hasAdminAccess ? 'Yes' : 'No'));
} else {
    error_log("No role found in session!");
}

// Set access flag - if not admin, view-only mode
$accessDenied = !$hasAdminAccess;
$accessDeniedMessage = 'Administrator access required for user management. You have view-only access.';

// Include database connection fix - USE THE SAME AS DASHBOARD.PHP
require_once 'includes/database_fix.php';

// Get database connection
try {
    $db = new DatabaseFix();
    $conn = $db->getConnection();

    // Test the connection
    $test = $conn->query("SELECT 1");
    error_log("Database connection successful");
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
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
?>

<!-- Add this HTML container for toasts (place it after header) -->
<div id="centeredToastContainer" class="toast-container-centered"></div>

<?php
// Handle actions - only if user has admin access
$action = $_GET['action'] ?? '';
$message = '';
$messageType = '';

// Handle user deletion - only for admin users
if (!$accessDenied && $action === 'delete' && isset($_GET['id'])) {
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

<!-- Toast Notification System -->
<style>
    /* Centered Toast Container */
    .toast-container-centered {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 9999;
        pointer-events: none;
    }

    .toast-centered {
        min-width: 300px;
        max-width: 450px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2), 0 8px 16px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        animation: slideInDown 0.5s ease-out;
        pointer-events: auto;
        border-left: 6px solid;
    }

    .toast-centered.toast-danger {
        border-left-color: #b50909;
    }

    .toast-centered.toast-success {
        border-left-color: #28a745;
    }

    .toast-centered.toast-warning {
        border-left-color: #ffc107;
    }

    .toast-centered.toast-info {
        border-left-color: #17a2b8;
    }

    .toast-content {
        display: flex;
        align-items: center;
        padding: 16px 20px;
    }

    .toast-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        font-size: 20px;
    }

    .toast-danger .toast-icon {
        background: rgba(220, 53, 69, 0.15);
        color: #dc3545;
    }

    .toast-message {
        flex: 1;
        font-size: 15px;
        font-weight: 500;
        color: #333;
    }

    .toast-close {
        color: #999;
        cursor: pointer;
        font-size: 18px;
        padding: 0 5px;
        transition: color 0.2s;
    }

    .toast-close:hover {
        color: #333;
    }

    .toast-progress {
        height: 4px;
        background: rgba(220, 53, 69, 0.2);
        position: relative;
    }

    .toast-progress-bar {
        height: 100%;
        background: #dc3545;
        animation: progressShrink 10s linear forwards;
    }

    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes progressShrink {
        from {
            width: 100%;
        }
        to {
            width: 0%;
        }
    }

    /* Pulse animation for attention */
    .toast-pulse {
        animation: pulse 0.5s ease-in-out 1;
    }

    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
    }

    /* Disabled button styles */
    .btn.disabled, .btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }

    /* View-only indicator */
    .view-only-badge {
        background: #ffc107;
        color: #50361e;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 600;
        margin-left: 8px;
    }
</style>

<script>
// Centered Toast Notification System
function showCenteredToast(message, type = 'danger', duration = 5000) {
    const container = document.getElementById('centeredToastContainer');
    if (!container) return;
    
    // Remove any existing toasts
    const existingToasts = container.querySelectorAll('.toast-centered');
    existingToasts.forEach(toast => toast.remove());
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast-centered toast-${type}`;
    
    // Choose icon based on type
    let icon = '🔒';
    if (type === 'danger') icon = '⚠️';
    else if (type === 'success') icon = '✅';
    else if (type === 'warning') icon = '⚠️';
    else if (type === 'info') icon = 'ℹ️';
    
    // Toast HTML structure
    toast.innerHTML = `
        <div class="toast-content">
            <div class="toast-icon">${icon}</div>
            <div class="toast-message">${message}</div>
            <div class="toast-close" onclick="this.closest('.toast-centered').remove()">✕</div>
        </div>
        <div class="toast-progress">
            <div class="toast-progress-bar" style="background-color: ${type === 'danger' ? '#dc3545' : (type === 'success' ? '#28a745' : (type === 'warning' ? '#ffc107' : '#17a2b8'))}"></div>
        </div>
    `;
    
    // Add to container
    container.appendChild(toast);
    
    // Add pulse animation
    toast.classList.add('toast-pulse');
    setTimeout(() => toast.classList.remove('toast-pulse'), 1000);
    
    // Auto remove after duration
    setTimeout(() => {
        if (toast.parentNode) {
            toast.style.animation = 'slideInDown 0.3s reverse';
            setTimeout(() => toast.remove(), 300);
        }
    }, duration);
    
    return toast;
}

// Function to disable user actions for view-only mode
function disableUserActions() {
    // Disable add user button
    const addBtn = document.querySelector('[data-bs-target="#addUserModal"]');
    if (addBtn) {
        addBtn.disabled = true;
        addBtn.classList.add('disabled');
        addBtn.title = 'Administrator access required';
    }
    
    // Disable edit buttons
    document.querySelectorAll('.edit-user-btn').forEach(btn => {
        btn.disabled = true;
        btn.classList.add('disabled');
        btn.style.opacity = '0.5';
        btn.style.pointerEvents = 'none';
        btn.title = 'Administrator access required';
    });
    
    // Disable delete buttons
    document.querySelectorAll('.delete-user-btn').forEach(btn => {
        btn.disabled = true;
        btn.classList.add('disabled');
        btn.style.opacity = '0.5';
        btn.style.pointerEvents = 'none';
        btn.title = 'Administrator access required';
    });
    
    // Prevent modal from opening
    $('.edit-user-btn, .delete-user-btn').off('click');
    
    // Show toast when trying to open modals
    $('.edit-user-btn, .delete-user-btn, [data-bs-target="#addUserModal"]').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        showCenteredToast('Administrator access required to perform this action.', 'warning', 4000);
        return false;
    });
}

// Show toast immediately if access denied
<?php if ($accessDenied): ?>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        showCenteredToast('<?php echo $accessDeniedMessage; ?>', 'danger', 8000);
    }, 500);
    disableUserActions();
});
<?php endif; ?>
</script>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-users me-2"></i>User Management
            <?php if ($accessDenied): ?>
                <span class="view-only-badge">VIEW ONLY</span>
            <?php endif; ?>
        </h1>
        <button type="button" class="btn btn-<?php echo $accessDenied ? 'secondary' : 'primary'; ?>" 
                data-bs-toggle="modal" data-bs-target="#addUserModal"
                <?php echo $accessDenied ? 'disabled' : ''; ?>
                title="<?php echo $accessDenied ? 'Administrator access required' : ''; ?>">
            <i class="fas fa-<?php echo $accessDenied ? 'lock' : 'plus'; ?> me-1"></i> 
            <?php echo $accessDenied ? 'Add User (Restricted)' : 'Add User'; ?>
        </button>
    </div>

    <?php if ($accessDenied): ?>
    <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Limited Access:</strong> You are viewing this page in read-only mode. Administrator access required to modify users.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

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
                                            data-department="<?php echo htmlspecialchars($user['department'] ?? ''); ?>"
                                            <?php echo $accessDenied ? 'disabled' : ''; ?>
                                            title="<?php echo $accessDenied ? 'Administrator access required' : ''; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-user-btn"
                                                data-id="<?php echo $user['id']; ?>"
                                                data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                <?php echo $accessDenied ? 'disabled' : ''; ?>
                                                title="<?php echo $accessDenied ? 'Administrator access required' : ''; ?>">
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

<!-- Delete Confirmation Modal -->
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

<!-- Add CSS Styles (keeping your existing styles) -->
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
        order: [
            [0, 'asc']
        ],
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
        table.order([
            [0, 'asc']
        ]).draw();
    });

    // Only attach event handlers if user has admin access
    <?php if (!$accessDenied): ?>
    
    // Fix: Add autocomplete attributes to password fields
    $('#action_password').attr('autocomplete', 'new-password');
    $('#action_password_confirm').attr('autocomplete', 'new-password');
    $('#addUserForm input[name="password"]').attr('autocomplete', 'new-password');

    // =============== CREATE USER ===============
    $('#addUserForm').on('submit', function(e) {
        e.preventDefault();

        const formData = $(this).serialize();

        $.ajax({
            url: 'api/user_create.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload();
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
            data: {
                user_id: userId
            },
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
                data: {
                    user_id: userId
                },
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
    
    <?php endif; // End of admin-only event handlers ?>
});
</script>

<?php require_once 'views/partials/footer.php'; ?>