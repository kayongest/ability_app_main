<?php
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password';
    } else {
        $conn = getConnection();

        $stmt = $conn->prepare("SELECT id, username, email, password, role, department, is_active FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if ($user['is_active'] == 0) {
                $error = 'Your account is disabled. Please contact administrator.';
            } elseif (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['department'] = $user['department'];
                $_SESSION['logged_in'] = true;

                // Redirect to dashboard
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Invalid password';
            }
        } else {
            $error = 'User not found';
        }

        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Ability DB Inventory</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Titillium+Web:ital,wght@0,200;0,300;0,400;0,600;0,700;0,900;1,200;1,300;1,400;1,600;1,700&display=swap");


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

        .titillium-web-black-italic {
            font-family: "Titillium Web", sans-serif;
            font-weight: 900;
            font-style: italic;
        }

        body {
            background: url('assets/images/bg/5.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            background: rgba(137, 133, 133, 0.48);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        h2 {
            text-align: center;
            color: #ffffffff;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 0.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #9b9999ff;
        }

        input[type="text"],
        input[type="password"] {
            width: 94%;
            padding: 0.55rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        button {
            width: 100%;
            padding: 0.75rem;
            background: #405873ff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background: #557496ff;
        }

        .error {
            background: #fee;
            color: rgba(172, 30, 8, 1);
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .register-link {
            text-align: center;
            margin-top: 1rem;
            text-decoration: none;
            color: #ffffffff;
        }

        .register-link a {
            text-decoration: none;
            color: #5c99dfff;
        }

        .forgot-password {
            text-align: right;
            margin-bottom: 0.9rem;
            text-decoration: none;
        }

        .forgot-password a {
            text-decoration: none;
            color: #fff;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Login to Ability DB</h2>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username or Email *</label>
                <input type="text" id="username" name="username" required
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="forgot-password">
                <a href="forgot_password.php">Forgot Password?</a>
            </div>

            <button type="submit">Login</button>
        </form>

        <div class="register-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</body>

</html>