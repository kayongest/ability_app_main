<?php
require_once 'config/database.php';

// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$debug_info = []; // Array to store debug information

// Check if user is already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Debug logging
    $debug_info[] = "POST request received";
    $debug_info[] = "Username: " . ($username ? htmlspecialchars($username) : "Empty");
    $debug_info[] = "Password provided: " . ($password ? "Yes" : "No");

    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password';
        $debug_info[] = "Validation failed: Empty fields";
    } else {
        try {
            $conn = getConnection();
            $debug_info[] = "Database connection established";

            $stmt = $conn->prepare("SELECT id, username, email, password, role, department, is_active FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();

            $debug_info[] = "Query executed. Rows found: " . $result->num_rows;

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                $debug_info[] = "User found: " . htmlspecialchars($user['username']);
                $debug_info[] = "User active status: " . $user['is_active'];

                if ($user['is_active'] == 0) {
                    $error = 'Your account is disabled. Please contact administrator.';
                    $debug_info[] = "Login failed: Account disabled";
                } elseif (password_verify($password, $user['password'])) {
                    $debug_info[] = "Password verification: SUCCESS";

                    // Set session variables
                    $_SESSION['logged_in'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['department'] = $user['department'];

                    $debug_info[] = "Session variables set";
                    $debug_info[] = "Redirecting to dashboard";

                    // Redirect to dashboard
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $error = 'Invalid password';
                    $debug_info[] = "Password verification: FAILED";
                }
            } else {
                $error = 'User not found';
                $debug_info[] = "User not found in database";
            }

            $stmt->close();
            $conn->close();
            $debug_info[] = "Database connection closed";
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
            $debug_info[] = "Exception: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - aBility Inventory Management</title>

    <!-- Glassmorphism Styles -->
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Open+Sans:wght@200;300;400;500;600;700&display=swap");

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Open Sans", sans-serif;
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            width: 100%;
            padding: 0 10px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: "";
            position: absolute;
            width: 100%;
            height: 100%;
            background: url('assets/images/login-hero-bg.jpg'), #000;
            background-position: center;
            background-size: cover;
            filter: brightness(0.7);
        }

        .wrapper {
            width: 400px;
            border-radius: 15px;
            padding: 40px 30px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            position: relative;
            z-index: 1;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        h2 {
            font-size: 2.2rem;
            margin-bottom: 25px;
            color: #fff;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            font-weight: 600;
        }

        .logo {
            margin-bottom: 20px;
        }

        .logo h1 {
            color: #fff;
            font-size: 2.8rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .logo h1 span {
            color: #4dabf7;
        }

        .input-field {
            position: relative;
            border-bottom: 2px solid rgba(255, 255, 255, 0.5);
            margin: 20px 0;
            transition: border-color 0.3s ease;
        }

        .input-field:hover {
            border-bottom-color: rgba(255, 255, 255, 0.8);
        }

        .input-field label {
            position: absolute;
            top: 50%;
            left: 0;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.8);
            font-size: 16px;
            pointer-events: none;
            transition: 0.3s ease;
        }

        .input-field input {
            width: 100%;
            height: 45px;
            background: transparent;
            border: none;
            outline: none;
            font-size: 16px;
            color: #fff;
            padding: 0 5px;
        }

        .input-field input:focus~label,
        .input-field input:valid~label {
            font-size: 0.85rem;
            top: 5px;
            transform: translateY(-120%);
            color: #4dabf7;
        }

        .input-field input:focus {
            border-bottom-color: #4dabf7;
        }

        .forget {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 20px 0 30px 0;
            color: rgba(255, 255, 255, 0.8);
        }

        #remember {
            accent-color: #4dabf7;
        }

        .forget label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .forget label p {
            margin-left: 8px;
            font-size: 14px;
        }

        .wrapper a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .wrapper a:hover {
            color: #4dabf7;
            text-decoration: underline;
        }

        button {
            background: linear-gradient(135deg, #203a4f, #6995bb);
            color: #fff;
            font-weight: 600;
            border: none;
            padding: 14px 20px;
            cursor: pointer;
            border-radius: 30px;
            font-size: 16px;
            transition: all 0.3s ease;
            margin-top: 10px;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(77, 171, 247, 0.4);
        }

        button:hover {
            background: linear-gradient(135deg, #203a4f, #6995bb);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(77, 171, 247, 0.6);
        }

        button:active {
            transform: translateY(0);
        }

        .register {
            text-align: center;
            margin-top: 30px;
            color: rgba(255, 255, 255, 0.8);
        }

        .register p {
            font-size: 14px;
        }

        .error-message {
            background: rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 107, 107, 0.3);
            font-size: 14px;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-message:before {
            content: "⚠";
            font-size: 16px;
        }

        .success-message {
            background: rgba(76, 217, 100, 0.2);
            color: #4cd964;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid rgba(76, 217, 100, 0.3);
            font-size: 14px;
            text-align: left;
        }

        /* Debug panel styles */
        .debug-panel {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.85);
            color: #fff;
            padding: 15px;
            border-radius: 10px;
            font-size: 12px;
            max-width: 350px;
            max-height: 250px;
            overflow-y: auto;
            z-index: 9999;
            display: none;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .debug-toggle {
            position: fixed;
            bottom: 10px;
            left: 10px;
            background: rgba(64, 88, 115, 0.8);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            z-index: 10000;
            font-size: 12px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .debug-toggle:hover {
            background: rgba(64, 88, 115, 1);
        }

        /* Responsive design */
        @media (max-width: 480px) {
            .wrapper {
                width: 90%;
                padding: 30px 20px;
            }

            h2 {
                font-size: 1.8rem;
            }

            .logo h1 {
                font-size: 2.2rem;
            }
        }

        /* Loading animation */
        .loading {
            display: none;
            text-align: center;
            margin-top: 10px;
        }

        .spinner {
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: #4dabf7;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 10px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <!-- Debug toggle button (visible only in development) -->
    <?php if (!empty($debug_info)): ?>
        <button class="debug-toggle" onclick="toggleDebug()">Toggle Debug</button>
        <div class="debug-panel" id="debugPanel">
            <h4 style="margin: 0 0 10px 0; color: #66c9ea; font-size: 14px;">Debug Info</h4>
            <div id="debugContent">
                <?php foreach ($debug_info as $info): ?>
                    <div style="margin-bottom: 5px; padding: 3px; border-bottom: 1px solid rgba(255, 255, 255, 0.2); font-family: monospace;">
                        <?php echo htmlspecialchars($info); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="wrapper">
        <!-- Logo -->
        <div class="logo">
            <h1>a<span>Bility</span></h1>
        </div>

        <h2>Login</h2>

        <!-- Display error message if any -->
        <?php if ($error): ?>
            <div class="error-message" id="errorMessage">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Display success message if redirected from registration -->
        <?php if (isset($_GET['registered']) && $_GET['registered'] == 'true'): ?>
            <div class="success-message">
                Registration successful! Please login with your credentials.
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="" id="loginForm">
            <div class="input-field">
                <input type="text" id="username" name="username" required
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                <label for="username">Username or Email</label>
            </div>

            <div class="input-field">
                <input type="password" id="password" name="password" required>
                <label for="password">Password</label>
            </div>

            <div class="forget">
                <label for="remember">
                    <input type="checkbox" id="remember" name="remember">
                    <p>Remember me</p>
                </label>
                <a href="forgot_password.php">Forgot password?</a>
            </div>

            <button type="submit" id="submitBtn">
                <span id="buttonText">Log In</span>
                <div class="loading" id="loadingSpinner">
                    <div class="spinner"></div> Logging in...
                </div>
            </button>

            <div class="register">
                <p>Don't have an account? <a href="register.php">Register</a></p>
            </div>
        </form>
    </div>

    <!-- JavaScript -->
    <script>
        // Form validation and submission
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const submitBtn = document.getElementById('submitBtn');
            const buttonText = document.getElementById('buttonText');
            const loadingSpinner = document.getElementById('loadingSpinner');

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const username = document.getElementById('username').value.trim();
                const password = document.getElementById('password').value;

                // Basic validation
                if (!username || !password) {
                    showError('Please fill in all fields');
                    return;
                }

                // Show loading state
                buttonText.style.display = 'none';
                loadingSpinner.style.display = 'block';
                submitBtn.disabled = true;

                // Submit the form after a brief delay for animation
                setTimeout(() => {
                    form.submit();
                }, 500);
            });

            // Auto-hide error message after 5 seconds
            const errorMessage = document.getElementById('errorMessage');
            if (errorMessage) {
                setTimeout(() => {
                    errorMessage.style.opacity = '0';
                    setTimeout(() => {
                        errorMessage.style.display = 'none';
                    }, 300);
                }, 5000);
            }

            // Input field label animation
            const inputs = document.querySelectorAll('.input-field input');
            inputs.forEach(input => {
                // Check if input has value on page load
                if (input.value) {
                    input.parentElement.classList.add('filled');
                }

                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });

                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.parentElement.classList.remove('focused');
                    }
                });
            });
        });

        // Error display function
        function showError(message) {
            // Remove existing error message
            const existingError = document.querySelector('.error-message');
            if (existingError) {
                existingError.remove();
            }

            // Create new error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.id = 'errorMessage';
            errorDiv.innerHTML = message;

            // Insert after the h2 element
            const h2 = document.querySelector('h2');
            h2.parentNode.insertBefore(errorDiv, h2.nextSibling);

            // Auto-hide after 5 seconds
            setTimeout(() => {
                errorDiv.style.opacity = '0';
                setTimeout(() => {
                    errorDiv.remove();
                }, 300);
            }, 5000);
        }

        // Debug panel toggle
        function toggleDebug() {
            const panel = document.getElementById('debugPanel');
            if (panel) {
                panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
            }
        }

        // Auto-show debug panel if there are errors
        <?php if ($error || !empty($debug_info)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const debugPanel = document.getElementById('debugPanel');
                if (debugPanel) {
                    debugPanel.style.display = 'block';
                }
            });
        <?php endif; ?>

        // Console logging for debugging
        console.log('Login page loaded');
        console.log('Session active: <?php echo session_status() === PHP_SESSION_ACTIVE ? "Yes" : "No"; ?>');

        <?php if ($error): ?>
            console.error('Login error: <?php echo addslashes($error); ?>');
        <?php endif; ?>
    </script>
</body>

</html>