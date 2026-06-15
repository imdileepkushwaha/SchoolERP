<?php
// admin/index.php (Login Page)
session_start();
require_once '../includes/db_connect.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id, username, password FROM admin_users WHERE username = :username');
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch(PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - SchoolERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">

    <div class="login-container">
        <div class="login-brand-panel">
            <div class="login-brand-content">
                <div class="login-brand-logo">
                    <div class="sidebar-logo-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h1>School<span>ERP</span></h1>
                </div>
                <p class="login-brand-tagline">Manage your school smarter — students, teachers, fees &amp; reports in one place.</p>
                <ul class="login-brand-features">
                    <li><i class="fas fa-check-circle"></i> Real-time dashboard &amp; analytics</li>
                    <li><i class="fas fa-check-circle"></i> Student &amp; staff management</li>
                    <li><i class="fas fa-check-circle"></i> Secure admin access</li>
                </ul>
            </div>
            <div class="login-brand-shapes">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
                <div class="shape shape-3"></div>
            </div>
        </div>

        <div class="login-form-panel">
            <div class="login-box">
                <div class="login-header">
                    <h2>Welcome back</h2>
                    <p>Sign in to your admin account</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="login-form">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" autocomplete="username" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" autocomplete="current-password" required>
                            <button type="button" class="password-toggle" id="passwordToggle" aria-label="Toggle password visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn-admin btn-login">
                        <span>Sign In</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>

                <div class="login-footer">
                    <a href="../index.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Back to Website
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('passwordToggle').addEventListener('click', function () {
            var input = document.getElementById('password');
            var icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    </script>
</body>
</html>
