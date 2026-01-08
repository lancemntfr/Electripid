<?php
session_start();
require_once '../connect.php';

$error_message = '';
$success_message = '';

// Check if already logged in and redirect based on role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($email) || empty($password)) {
        $error_message = 'Please fill in all required fields.';
    } else {
        $email = mysqli_real_escape_string($conn, $email);
        $query = "SELECT user_id, fname, lname, email, password, role, acc_status FROM USER WHERE email = '$email'";
        $result = executeQuery($query);
        
        if ($result && mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);
            
            if ($user['acc_status'] !== 'active') {
                $error_message = 'Your account is ' . $user['acc_status'] . '. Please contact support.';
            } else {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['fname'] = $user['fname'];
                    $_SESSION['lname'] = $user['lname'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    
                    if ($remember) {
                        setcookie('remember_token', base64_encode($user['user_id'] . ':' . hash('sha256', $user['password'])), time() + (30 * 24 * 60 * 60), '/');
                    }
                    
                    // Redirect based on role
                    if ($user['role'] === 'admin') {
                        header('Location: ../admin/dashboard.php');
                    } else {
                        header('Location: dashboard.php');
                    }
                    exit;
                } else {
                    $error_message = 'Invalid email or password.';
                }
            }
        } else {
            $error_message = 'Invalid email or password.';
        }
    }
}

// Remember me functionality with role-based redirect
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token_data = base64_decode($_COOKIE['remember_token']);
    $parts = explode(':', $token_data);
    
    if (count($parts) === 2) {
        $user_id = intval($parts[0]);
        $token_hash = $parts[1];
        
        $user_id = mysqli_real_escape_string($conn, $user_id);
        $query = "SELECT user_id, fname, lname, email, password, role, acc_status FROM USER WHERE user_id = '$user_id' AND acc_status = 'active'";
        $result = executeQuery($query);
        
        if ($result && mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);

            if (hash('sha256', $user['password']) === $token_hash) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['fname'] = $user['fname'];
                $_SESSION['lname'] = $user['lname'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header('Location: ../admin/dashboard.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit;
            }
        }
        
        setcookie('remember_token', '', time() - 3600, '/');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="../assets/css/user.css">
<style>
    body {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 15px;
        background: linear-gradient(135deg, #e3f2fd 0%, white 100%);
    }
</style>
</head>
<body>
    <div class="login-card bg-white p-5 w-100" style="max-width: 500px;">
        <div class="text-center mb-4">
            <div class="logo-icon rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4 text-white">⚡</div>
            <h2 class="text-primary">Welcome Back</h2>
            <p class="text-muted">Sign in to your Electripid account</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- LOGIN FORM (for both user and admin) -->
        <form method="POST" action="" id="loginForm">
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" class="form-control" name="email" required placeholder="Enter your email address" autocomplete="email">
            </div>

            <div class="mb-3 position-relative">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" name="password" id="password" required placeholder="Enter your password" autocomplete="current-password">
                    <span class="eye-toggle position-absolute text-secondary z-3" id="togglePassword">
                        <i class="bi bi-eye"></i>
                    </span>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <a href="forgot_password.php" class="text-decoration-none text-primary small">Forgot password?</a>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
            </button>
        </form>

        <div class="text-center mt-3">
            <p class="mb-2">Don't have an account? 
                <a href="register.php" class="text-decoration-none">Sign up now</a>
            </p>
            <p class="mb-0">
                <a href="../index.php" class="text-decoration-none">
                    <i class="bi bi-arrow-left me-1"></i> Back to Homepage
                </a>
            </p>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const togglePassword = document.getElementById('togglePassword');
const passwordInput = document.getElementById('password');
const eyeIcon = togglePassword.querySelector('i');

togglePassword.addEventListener('click', function() {
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.classList.remove('bi-eye');
        eyeIcon.classList.add('bi-eye-slash');
    } else {
        passwordInput.type = 'password';
        eyeIcon.classList.remove('bi-eye-slash');
        eyeIcon.classList.add('bi-eye');
    }
});

document.getElementById('loginForm').addEventListener('submit', function(e) {
    const email = document.querySelector('input[name="email"]').value;
    const password = document.querySelector('input[name="password"]').value;
    
    if (!email.trim() || !password.trim()) {
        e.preventDefault();
        alert('Please fill in all required fields');
        return;
    }

    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email)) {
        e.preventDefault();
        alert('Please enter a valid email address');
        return;
    }

    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Signing in...';
    submitBtn.disabled = true;
});
</script>
</body>
</html>