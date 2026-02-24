<?php
// technician-login.php - Separate login for technicians
session_start();
require_once 'bootstrap.php';
require_once 'includes/db_connect.php';

$error = '';
$success = '';

// Redirect if already logged in as technician
if (isset($_SESSION['technician_id'])) {
    header('Location: technician-dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password';
    } else {
        $conn = getConnection();
        
        // Check technicians table
        $stmt = $conn->prepare("
            SELECT id, username, password, full_name, email, department, role, 
                   is_active, force_password_reset, login_attempts 
            FROM technicians 
            WHERE (username = ? OR email = ?) AND is_active = 1
        ");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($technician = $result->fetch_assoc()) {
            // Verify password
            if (password_verify($password, $technician['password'])) {
                // Reset login attempts on success
                $update = $conn->prepare("UPDATE technicians SET login_attempts = 0, last_login = NOW() WHERE id = ?");
                $update->bind_param("i", $technician['id']);
                $update->execute();
                
                // Set technician session
                $_SESSION['technician_id'] = $technician['id'];
                $_SESSION['technician_username'] = $technician['username'];
                $_SESSION['technician_name'] = $technician['full_name'];
                $_SESSION['technician_role'] = $technician['role'];
                $_SESSION['user_type'] = 'technician';
                
                // Check if password reset required
                if ($technician['force_password_reset'] == 1) {
                    header('Location: technician-reset-password.php');
                } else {
                    header('Location: technician-dashboard.php');
                }
                exit();
            } else {
                // Increment login attempts
                $attempts = $technician['login_attempts'] + 1;
                $update = $conn->prepare("UPDATE technicians SET login_attempts = ?, last_login_attempt = NOW() WHERE id = ?");
                $update->bind_param("ii", $attempts, $technician['id']);
                $update->execute();
                
                $error = 'Invalid password';
            }
        } else {
            $error = 'Technician not found or account inactive';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Login - aBility</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 400px;
            width: 100%;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            background: white;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h2 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        .login-header p {
            color: #666;
            font-size: 0.9rem;
        }
        .technician-badge {
            background: #764ba2;
            color: white;
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
            margin-top: 1rem;
        }
        .back-link {
            text-align: center;
            margin-top: 1rem;
        }
        .back-link a {
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .back-link a:hover {
            color: #764ba2;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h2>🔧 Technician Portal</h2>
            <p>Sign in to access your assignments and tools</p>
            <div class="technician-badge">
                <i class="fas fa-tools me-1"></i> Technical Staff Only
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">Username or Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                           required autofocus>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 mb-3" style="background: #764ba2; border: none;">
                <i class="fas fa-sign-in-alt me-2"></i>Login to Technician Portal
            </button>
            
            <div class="back-link">
                <a href="login.php">
                    <i class="fas fa-arrow-left me-1"></i>Back to Main Login
                </a>
            </div>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>