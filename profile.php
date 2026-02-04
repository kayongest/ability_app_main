<?php
// profile.php - User Profile Management
$current_page = 'profile.php';
require_once 'bootstrap.php';

// Check authentication
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$pageTitle = "My Profile - aBility";
$showBreadcrumb = true;
$breadcrumbItems = [
    'Dashboard' => 'dashboard.php',
    'My Profile' => ''
];

require_once 'views/partials/header.php';

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validate required fields
        if (empty($username) || empty($email)) {
            $message = 'Username and email are required.';
            $messageType = 'danger';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Invalid email format.';
            $messageType = 'danger';
        } else {
            try {
                // Check if username/email already exists (excluding current user)
                $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ? AND is_active = 1");
                $stmt->execute([$username, $email, $_SESSION['user_id']]);
                $existing = $stmt->fetch();

                if ($existing) {
                    $message = 'Username or email already exists.';
                    $messageType = 'danger';
                } else {
                    // Update profile
                    $updateFields = ["username = ?", "email = ?"];
                    $params = [$username, $email];

                    // Handle password change
                    if (!empty($new_password)) {
                        if (empty($current_password)) {
                            $message = 'Current password is required to change password.';
                            $messageType = 'danger';
                        } elseif ($new_password !== $confirm_password) {
                            $message = 'New passwords do not match.';
                            $messageType = 'danger';
                        } elseif (strlen($new_password) < 6) {
                            $message = 'New password must be at least 6 characters long.';
                            $messageType = 'danger';
                        } else {
                            // Verify current password
                            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                            $stmt->execute([$_SESSION['user_id']]);
                            $user = $stmt->fetch();

                            if (!password_verify($current_password, $user['password'])) {
                                $message = 'Current password is incorrect.';
                                $messageType = 'danger';
                            } else {
                                $updateFields[] = "password = ?";
                                $params[] = password_hash($new_password, PASSWORD_DEFAULT);
                            }
                        }
                    }

                    if ($messageType !== 'danger') {
                        $params[] = $_SESSION['user_id'];
                        $stmt = $pdo->prepare("UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?");
                        $result = $stmt->execute($params);

                        if ($result) {
                            // Update session data
                            $_SESSION['username'] = $username;

                            $message = 'Profile updated successfully!';
                            $messageType = 'success';

                            // Log activity
                            logActivity($pdo, $_SESSION['user_id'], 'profile_updated', "Updated profile information");
                        } else {
                            $message = 'Failed to update profile.';
                            $messageType = 'danger';
                        }
                    }
                }
            } catch (Exception $e) {
                $message = 'Error updating profile: ' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }
}

// Get current user data
try {
    $stmt = $pdo->prepare("
        SELECT u.*, COUNT(a.id) as activity_count
        FROM users u
        LEFT JOIN activity_log a ON u.id = a.user_id
        WHERE u.id = ? AND u.is_active = 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // User not found or inactive
        session_destroy();
        header('Location: login.php');
        exit();
    }
} catch (Exception $e) {
    die("Error loading profile: " . $e->getMessage());
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
?>

<div class="container-fluid">
    <div class="row">
        <!-- Profile Sidebar -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar-circle mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2em;">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                    <h5 class="card-title"><?php echo htmlspecialchars($user['username']); ?></h5>
                    <p class="text-muted"><?php echo $roles[$user['role']] ?? $user['role']; ?></p>
                    <p class="text-muted"><?php echo htmlspecialchars($user['department'] ?? 'No Department'); ?></p>

                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <h6><?php echo $user['activity_count']; ?></h6>
                            <small class="text-muted">Activities</small>
                        </div>
                        <div class="col-6">
                            <h6><?php echo $user['last_login'] ? date('M j', strtotime($user['last_login'])) : 'Never'; ?></h6>
                            <small class="text-muted">Last Login</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Account Information</h6>
                </div>
                <div class="card-body">
                    <p><strong>Member Since:</strong><br><?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                    <p><strong>Last Updated:</strong><br><?php echo $user['updated_at'] ? date('F j, Y', strtotime($user['updated_at'])) : 'Never'; ?></p>
                    <p><strong>Account Status:</strong><br><span class="badge bg-success">Active</span></p>
                </div>
            </div>
        </div>

        <!-- Profile Form -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Edit Profile</h5>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="profileForm">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Username *</label>
                                    <input type="text" class="form-control" name="username"
                                           value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email"
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Role</label>
                                    <input type="text" class="form-control" value="<?php echo $roles[$user['role']] ?? $user['role']; ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Department</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['department'] ?? 'Not Assigned'); ?>" readonly>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6>Change Password (Optional)</h6>
                        <p class="text-muted">Leave blank if you don't want to change your password.</p>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" class="form-control" name="current_password">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control" name="new_password" minlength="6">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" name="confirm_password">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Update Profile
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #007bff;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 2em;
}
</style>

<script>
$(document).ready(function() {
    // Form validation
    $('#profileForm').on('submit', function(e) {
        const newPassword = $('input[name="new_password"]').val();
        const confirmPassword = $('input[name="confirm_password"]').val();
        const currentPassword = $('input[name="current_password"]').val();

        if (newPassword || confirmPassword || currentPassword) {
            if (!currentPassword) {
                e.preventDefault();
                toastr.error('Current password is required to change password');
                return false;
            }
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                toastr.error('New passwords do not match');
                return false;
            }
            if (newPassword.length < 6) {
                e.preventDefault();
                toastr.error('New password must be at least 6 characters long');
                return false;
            }
        }
    });
});
</script>

<?php require_once 'views/partials/footer.php'; ?>
