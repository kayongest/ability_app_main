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

// Get database connection
$conn = getConnection();

// First, let's check what tables exist and create them if needed
function checkAndCreateTables($conn)
{
    $tables_created = false;

    // Check if users table exists
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows == 0) {
        // Create users table
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT(11) PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100),
            phone VARCHAR(20),
            department VARCHAR(50),
            bio TEXT,
            profile_image VARCHAR(500),
            role VARCHAR(50) DEFAULT 'user',
            is_active TINYINT(1) DEFAULT 1,
            last_login DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";

        if ($conn->query($sql)) {
            error_log("Users table created successfully");
        } else {
            error_log("Error creating users table: " . $conn->error);
        }
    }

    // Check if activity_log table exists
    $result = $conn->query("SHOW TABLES LIKE 'activity_log'");
    if ($result->num_rows == 0) {
        // Create activity_log table
        $sql = "CREATE TABLE IF NOT EXISTS activity_log (
            id INT(11) PRIMARY KEY AUTO_INCREMENT,
            user_id INT(11),
            action VARCHAR(50),
            description TEXT,
            ip_address VARCHAR(45),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";

        if ($conn->query($sql)) {
            error_log("Activity_log table created successfully");
        } else {
            error_log("Error creating activity_log table: " . $conn->error);
        }
    }

    // Check if user_sessions table exists
    $result = $conn->query("SHOW TABLES LIKE 'user_sessions'");
    if ($result->num_rows == 0) {
        // Create user_sessions table
        $sql = "CREATE TABLE IF NOT EXISTS user_sessions (
            id INT(11) PRIMARY KEY AUTO_INCREMENT,
            user_id INT(11),
            session_id VARCHAR(255),
            login_time DATETIME DEFAULT CURRENT_TIMESTAMP,
            logout_time DATETIME,
            ip_address VARCHAR(45),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";

        if ($conn->query($sql)) {
            error_log("User_sessions table created successfully");
        } else {
            error_log("Error creating user_sessions table: " . $conn->error);
        }
    }
}

// Create tables if they don't exist
checkAndCreateTables($conn);

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

// Get current user data first
try {
    // First check if user exists in database
    $check_sql = "SELECT id FROM users WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);

    if (!$check_stmt) {
        error_log("Prepare failed: " . $conn->error);
        die("Database error: " . $conn->error);
    }

    $check_stmt->bind_param("i", $_SESSION['user_id']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows == 0) {
        // User doesn't exist in database - this shouldn't happen if they're logged in
        session_destroy();
        header('Location: login.php');
        exit();
    }

    // Now get full user data
    $sql = "SELECT u.*, 
                   COUNT(DISTINCT a.id) as activity_count
            FROM users u
            LEFT JOIN activity_log a ON u.id = a.user_id
            WHERE u.id = ? AND u.is_active = 1
            GROUP BY u.id";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        die("Database error: " . $conn->error);
    }

    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        // User not found or inactive
        session_destroy();
        header('Location: login.php');
        exit();
    }

    $stmt->close();
} catch (Exception $e) {
    error_log("Error loading profile: " . $e->getMessage());
    die("Error loading profile: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
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
                $check_sql = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ? AND is_active = 1";
                $check_stmt = $conn->prepare($check_sql);

                if (!$check_stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }

                $check_stmt->bind_param("ssi", $username, $email, $_SESSION['user_id']);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                $existing = $check_result->fetch_assoc();
                $check_stmt->close();

                if ($existing) {
                    $message = 'Username or email already exists.';
                    $messageType = 'danger';
                } else {
                    // Handle password change
                    $passwordChanged = false;

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
                            $pass_sql = "SELECT password FROM users WHERE id = ?";
                            $pass_stmt = $conn->prepare($pass_sql);

                            if (!$pass_stmt) {
                                throw new Exception("Prepare failed: " . $conn->error);
                            }

                            $pass_stmt->bind_param("i", $_SESSION['user_id']);
                            $pass_stmt->execute();
                            $pass_result = $pass_stmt->get_result();
                            $userData = $pass_result->fetch_assoc();
                            $pass_stmt->close();

                            if (!password_verify($current_password, $userData['password'])) {
                                $message = 'Current password is incorrect.';
                                $messageType = 'danger';
                            } else {
                                $passwordChanged = true;
                            }
                        }
                    }

                    // Handle profile picture upload
                    $profile_image = $user['profile_image'] ?? '';
                    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                        $upload_dir = 'uploads/profiles/';
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }

                        $file_ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                        $file_name = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_ext;
                        $target_path = $upload_dir . $file_name;

                        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                        if (in_array(strtolower($file_ext), $allowed_types)) {
                            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_path)) {
                                // Delete old profile picture if exists
                                if (!empty($user['profile_image']) && file_exists($user['profile_image'])) {
                                    unlink($user['profile_image']);
                                }
                                $profile_image = $target_path;
                            }
                        }
                    }

                    if ($messageType !== 'danger') {
                        if ($passwordChanged) {
                            // Update with password change
                            $update_sql = "UPDATE users SET full_name = ?, username = ?, email = ?, phone = ?, department = ?, bio = ?, profile_image = ?, password = ?, updated_at = NOW() WHERE id = ?";
                            $update_stmt = $conn->prepare($update_sql);

                            if (!$update_stmt) {
                                throw new Exception("Prepare failed: " . $conn->error);
                            }

                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                            $update_stmt->bind_param("ssssssssi", $full_name, $username, $email, $phone, $department, $bio, $profile_image, $hashed_password, $_SESSION['user_id']);
                        } else {
                            // Update without password change
                            $update_sql = "UPDATE users SET full_name = ?, username = ?, email = ?, phone = ?, department = ?, bio = ?, profile_image = ?, updated_at = NOW() WHERE id = ?";
                            $update_stmt = $conn->prepare($update_sql);

                            if (!$update_stmt) {
                                throw new Exception("Prepare failed: " . $conn->error);
                            }

                            $update_stmt->bind_param("sssssssi", $full_name, $username, $email, $phone, $department, $bio, $profile_image, $_SESSION['user_id']);
                        }

                        $result = $update_stmt->execute();

                        if ($result) {
                            // Update session data
                            $_SESSION['username'] = $username;
                            if (!empty($full_name)) {
                                $_SESSION['full_name'] = $full_name;
                            }

                            $message = 'Profile updated successfully!';
                            $messageType = 'success';

                            // Refresh user data
                            $refresh_sql = "SELECT u.*, COUNT(a.id) as activity_count FROM users u LEFT JOIN activity_log a ON u.id = a.user_id WHERE u.id = ? GROUP BY u.id";
                            $refresh_stmt = $conn->prepare($refresh_sql);
                            if ($refresh_stmt) {
                                $refresh_stmt->bind_param("i", $_SESSION['user_id']);
                                $refresh_stmt->execute();
                                $refresh_result = $refresh_stmt->get_result();
                                $user = $refresh_result->fetch_assoc();
                                $refresh_stmt->close();
                            }

                            // Log activity
                            $log_sql = "INSERT INTO activity_log (user_id, action, description, ip_address) VALUES (?, 'profile_updated', ?, ?)";
                            $log_stmt = $conn->prepare($log_sql);
                            if ($log_stmt) {
                                $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                                $desc = "Updated profile information";
                                $log_stmt->bind_param("iss", $_SESSION['user_id'], $desc, $ip);
                                $log_stmt->execute();
                                $log_stmt->close();
                            }
                        } else {
                            $message = 'Failed to update profile.';
                            $messageType = 'danger';
                        }

                        if (isset($update_stmt)) {
                            $update_stmt->close();
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Profile update error: " . $e->getMessage());
                $message = 'Error updating profile: ' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }
}

// Get recent activity
$recent_activities = [];
try {
    $activity_sql = "SELECT * FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
    $activity_stmt = $conn->prepare($activity_sql);

    if ($activity_stmt) {
        $activity_stmt->bind_param("i", $_SESSION['user_id']);
        $activity_stmt->execute();
        $activity_result = $activity_stmt->get_result();
        $recent_activities = $activity_result->fetch_all(MYSQLI_ASSOC);
        $activity_stmt->close();
    }
} catch (Exception $e) {
    error_log("Error fetching activities: " . $e->getMessage());
    $recent_activities = [];
}

// Define roles and departments
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
    'admin' => 'Administration'
];
?>

<!-- Your existing HTML/CSS/JS code continues here... -->
<style>
    :root {
        --primary-color: #234c6a;
        --primary-light: #2c5a7a;
        --primary-dark: #1a3a4f;
        --secondary-color: #6c757d;
        --success-color: #28a745;
        --info-color: #17a2b8;
        --warning-color: #ffc107;
        --danger-color: #dc3545;
    }

    .profile-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem 1.5rem;
    }

    /* Profile Header */
    .profile-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        border-radius: 25px;
        padding: 2.5rem;
        margin-bottom: 2rem;
        color: white;
        box-shadow: 0 10px 30px rgba(35, 76, 106, 0.2);
        position: relative;
        overflow: hidden;
    }

    .profile-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
        animation: rotate 20s linear infinite;
    }

    @keyframes rotate {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .profile-avatar-wrapper {
        position: relative;
        width: 120px;
        height: 120px;
        margin: 0 auto 1rem;
    }

    .profile-avatar {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        font-weight: 600;
        color: var(--primary-color);
        border: 4px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        overflow: hidden;
    }

    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .avatar-upload {
        position: absolute;
        bottom: 0;
        right: 0;
        background: var(--primary-dark);
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border: 2px solid white;
        transition: all 0.3s ease;
    }

    .avatar-upload:hover {
        transform: scale(1.1);
        background: var(--primary-light);
    }

    .avatar-upload i {
        color: white;
        font-size: 1rem;
    }

    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        border: 1px solid #e9ecef;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(35, 76, 106, 0.1);
        border-color: var(--primary-color);
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        background: rgba(35, 76, 106, 0.1);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-color);
        font-size: 1.5rem;
    }

    .stat-content h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary-color);
        margin: 0;
        line-height: 1.2;
    }

    .stat-content p {
        color: var(--secondary-color);
        margin: 0;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Profile Content */
    .profile-content {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 1.5rem;
    }

    /* Sidebar Cards */
    .profile-card {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        border: 1px solid #e9ecef;
        margin-bottom: 1.5rem;
    }

    .card-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .card-title i {
        font-size: 1.2rem;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid #e9ecef;
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-label {
        color: var(--secondary-color);
        font-size: 0.9rem;
    }

    .info-value {
        font-weight: 500;
        color: #2c3e50;
    }

    .badge-role {
        background: rgba(35, 76, 106, 0.1);
        color: var(--primary-color);
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 500;
    }

    /* Activity List */
    .activity-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .activity-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 0;
        border-bottom: 1px solid #e9ecef;
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-icon {
        width: 35px;
        height: 35px;
        background: rgba(35, 76, 106, 0.1);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-color);
    }

    .activity-details {
        flex: 1;
    }

    .activity-action {
        font-weight: 500;
        color: #2c3e50;
        margin-bottom: 0.25rem;
    }

    .activity-time {
        font-size: 0.8rem;
        color: var(--secondary-color);
    }

    /* Form Styles */
    .form-section {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        border: 1px solid #e9ecef;
    }

    .form-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #e9ecef;
    }

    .form-label {
        font-weight: 500;
        color: #495057;
        margin-bottom: 0.5rem;
    }

    .form-control,
    .form-select {
        border: 1px solid #e0e0e0;
        border-radius: 10px;
        padding: 0.6rem 1rem;
        transition: all 0.3s ease;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(35, 76, 106, 0.25);
    }

    .btn-primary {
        background: var(--primary-color);
        border: none;
        padding: 0.6rem 1.5rem;
        border-radius: 10px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background: var(--primary-light);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(35, 76, 106, 0.3);
    }

    .btn-outline-secondary {
        border: 1px solid #e0e0e0;
        color: var(--secondary-color);
        padding: 0.6rem 1.5rem;
        border-radius: 10px;
        transition: all 0.3s ease;
    }

    .btn-outline-secondary:hover {
        background: #f8f9fa;
        border-color: var(--secondary-color);
        color: #2c3e50;
    }

    /* Responsive */
    @media (max-width: 992px) {
        .profile-content {
            grid-template-columns: 1fr;
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 576px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .profile-header {
            padding: 1.5rem;
        }
    }

    /* Alert Styles */
    .alert {
        border-radius: 10px;
        border: none;
        padding: 1rem 1.5rem;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
    }

    .alert-danger {
        background: #f8d7da;
        color: #721c24;
    }

    /* Password Change Section */
    .password-section {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 1.5rem;
        margin-top: 1.5rem;
    }

    .password-section h6 {
        color: var(--primary-color);
        margin-bottom: 1rem;
    }

    /* Animations */
    .fade-in {
        animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<div class="profile-container fade-in">
    <!-- Messages -->
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-5 mb-2">My Profile</h1>
                <p class="mb-0 opacity-75">Manage your personal information and account settings</p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <span class="badge-role bg-white bg-opacity-25 text-white">
                    <i class="fas fa-shield-alt me-1"></i>
                    <?php echo $roles[$user['role']] ?? $user['role']; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo date('M j, Y', strtotime($user['created_at'])); ?></h3>
                <p>Member Since</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $user['activity_count'] ?? 0; ?></h3>
                <p>Total Activities</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $user['items_managed'] ?? 0; ?></h3>
                <p>Items Managed</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-sign-in-alt"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $user['login_count'] ?? 0; ?></h3>
                <p>Logins (30d)</p>
            </div>
        </div>
    </div>

    <!-- Profile Content -->
    <div class="profile-content">
        <!-- Left Sidebar -->
        <div>
            <!-- Profile Summary Card -->
            <div class="profile-card text-center">
                <div class="profile-avatar-wrapper">
                    <div class="profile-avatar">
                        <?php if (!empty($user['profile_image']) && file_exists($user['profile_image'])): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile">
                        <?php else: ?>
                            <?php echo strtoupper(substr($user['full_name'] ?? $user['username'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <label for="profile_image_input" class="avatar-upload">
                        <i class="fas fa-camera"></i>
                    </label>
                </div>

                <h4 class="mb-1"><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></h4>
                <p class="text-muted mb-3">@<?php echo htmlspecialchars($user['username']); ?></p>

                <div class="d-flex justify-content-center gap-2 mb-3">
                    <span class="badge-role">
                        <i class="fas fa-briefcase me-1"></i>
                        <?php echo $departments[$user['department']] ?? $user['department'] ?? 'No Department'; ?>
                    </span>
                    <span class="badge-role">
                        <i class="fas fa-circle text-success me-1"></i>
                        Active
                    </span>
                </div>

                <?php if (!empty($user['bio'])): ?>
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                <?php endif; ?>
            </div>

            <!-- Contact Information -->
            <div class="profile-card">
                <h5 class="card-title">
                    <i class="fas fa-address-card text-primary"></i>
                    Contact Information
                </h5>

                <div class="info-item">
                    <span class="info-label"><i class="fas fa-envelope me-2"></i>Email</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label"><i class="fas fa-phone me-2"></i>Phone</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label"><i class="fas fa-building me-2"></i>Department</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['department'] ?? 'Not assigned'); ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label"><i class="fas fa-clock me-2"></i>Last Updated</span>
                    <span class="info-value"><?php echo $user['updated_at'] ? date('M j, Y', strtotime($user['updated_at'])) : 'Never'; ?></span>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="profile-card">
                <h5 class="card-title">
                    <i class="fas fa-history text-primary"></i>
                    Recent Activity
                </h5>

                <?php if (empty($recent_activities)): ?>
                    <p class="text-muted text-center py-3">No recent activity</p>
                <?php else: ?>
                    <ul class="activity-list">
                        <?php foreach ($recent_activities as $activity): ?>
                            <li class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-<?php
                                                        echo $activity['action'] === 'profile_updated' ? 'user-edit' : ($activity['action'] === 'login' ? 'sign-in-alt' : 'circle');
                                                        ?>"></i>
                                </div>
                                <div class="activity-details">
                                    <div class="activity-action">
                                        <?php echo ucwords(str_replace('_', ' ', $activity['action'])); ?>
                                    </div>
                                    <div class="activity-time">
                                        <i class="far fa-clock me-1"></i>
                                        <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Content - Edit Profile Form -->
        <div class="form-section">
            <h5 class="form-title">
                <i class="fas fa-user-edit me-2"></i>
                Edit Profile Information
            </h5>

            <form method="POST" enctype="multipart/form-data" id="profileForm">
                <input type="hidden" name="action" value="update_profile">
                <input type="file" name="profile_image" id="profile_image_input" style="display: none;" accept="image/*">

                <div class="row g-3">
                    <!-- Basic Information -->
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="full_name"
                            value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>"
                            placeholder="Enter your full name">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Username *</label>
                        <input type="text" class="form-control" name="username"
                            value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email"
                            value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" name="phone"
                            value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                            placeholder="+250 XXX XXX XXX">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Department</label>
                        <select class="form-select" name="department">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($user['department'] ?? '') == $key ? 'selected' : ''; ?>>
                                    <?php echo $value; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Role (Read Only)</label>
                        <input type="text" class="form-control"
                            value="<?php echo $roles[$user['role']] ?? $user['role']; ?>" readonly>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Bio</label>
                        <textarea class="form-control" name="bio" rows="3"
                            placeholder="Tell us a little about yourself"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>

                    <!-- Password Change Section -->
                    <div class="col-12">
                        <div class="password-section">
                            <h6>
                                <i class="fas fa-lock me-2"></i>
                                Change Password
                                <small class="text-muted ms-2">(Leave blank to keep current password)</small>
                            </h6>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" class="form-control" name="current_password">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control" name="new_password" minlength="6">
                                    <small class="text-muted">Min. 6 characters</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" name="confirm_password">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="col-12">
                        <hr>
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='dashboard.php'">
                                <i class="fas fa-arrow-left me-1"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
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

<script>
    $(document).ready(function() {
        // Profile picture upload trigger
        $('#profile_image_input').on('change', function() {
            if (this.files && this.files[0]) {
                // You can add preview here if needed
                $('#profileForm').submit(); // Auto-submit when image selected
            }
        });

        // Form validation
        $('#profileForm').on('submit', function(e) {
            const newPassword = $('input[name="new_password"]').val();
            const confirmPassword = $('input[name="confirm_password"]').val();
            const currentPassword = $('input[name="current_password"]').val();

            if (newPassword || confirmPassword || currentPassword) {
                if (!currentPassword) {
                    e.preventDefault();
                    alert('Current password is required to change password');
                    return false;
                }
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('New passwords do not match');
                    return false;
                }
                if (newPassword.length < 6) {
                    e.preventDefault();
                    alert('New password must be at least 6 characters long');
                    return false;
                }
            }
        });

        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>

<?php require_once 'views/partials/footer.php'; ?>