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
    <title>Electripid - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/user.css">
    <style>
        .success-modal-content {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: none;
        }
        .success-modal-header {
            border-bottom: none;
            text-align: center;
            padding-bottom: 0.25rem;
        }
        .success-modal-title {
            width: 100%;
            font-weight: 600;
            color: #198754; /* Bootstrap green */
        }
        .success-modal-body {
            text-align: center;
            padding-top: 0;
            padding-bottom: 0.75rem;
        }
        .success-modal-body p {
            margin-bottom: 0;
        }
        .success-modal-footer {
            border-top: none;
            padding-top: 0;
        }
    </style>
</head>
<body class="login-page">
    <div class="auth-container">
        <!-- Left Section - Welcome (Blue Background) -->
        <div class="welcome-section">
            <h1>Welcome<br>Back!</h1>
            <p>Enter your credentials to access your account.</p>
            <div>
                <p class="mb-0">Don't have an account?</p>
                <a href="register.php" class="register-link">Create Account</a>
            </div>
        </div>

        <!-- Right Section - Form (White Background) -->
        <div class="form-section">
            <div class="login-form-container">
                <div class="text-center mb-4">
                    <h2>Log In</h2>
                </div>
                
                <!-- Alert Messages -->
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Login Form -->
                <form method="POST" action="" id="loginForm">
                    <div class="mb-compact">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" required placeholder="Enter your email address" autocomplete="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <div class="mb-compact">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <div class="position-relative">
                            <input type="password" class="form-control" name="password" 
                                   id="password" required
                                   placeholder="Enter your password"
                                   autocomplete="current-password">
                            <button type="button" class="eye-toggle position-absolute text-secondary z-3 border-0 bg-transparent" id="togglePassword" style="right: 10px; top: 50%; transform: translateY(-50%);">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label small" for="remember">Remember me</label>
                        </div>
                        <a href="verification/reset_pass/forgot_password.php" class="text-decoration-none small" style="color: #1e88e5;">
                            Forgot password?
                        </a>
                    </div>

                    <button type="submit" class="btn btn-login" id="loginBtn">
                        Log in
                    </button>

                    <div class="text-center mt-3">
                        <p class="mb-0 small">Don't have an account? 
                            <a href="register.php" class="text-decoration-none" style="color: #1e88e5;">Sign up now</a>
                        </p>
                        <p class="mt-2">
                            <a href="../index.php" class="text-decoration-none small">
                                <i class="bi bi-arrow-left me-1"></i> Back to Homepage
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
<!-- Success Modal for password reset / account creation -->
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content success-modal-content">
            <div class="modal-body success-modal-body text-center py-5">
                <div class="success-icon-wrapper mb-4">
                    <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="40" cy="40" r="38" stroke="#28a745" stroke-width="4" fill="none"/>
                        <path d="M25 40L35 50L55 30" stroke="#28a745" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h5 class="modal-title success-modal-title mb-3" id="successModalTitle">Success</h5>
                <p id="successModalMessage" class="mb-4"></p>
                <button type="button" class="btn btn-outline-light px-5 py-2" id="successModalOk" data-bs-dismiss="modal">CLOSE</button>
            </div>
        </div>
    </div>
</div>

<style>
#successModal .modal-content {
    background: transparent;
    border: none;
    max-width: 400px;
    margin: 0 auto;
}

#successModal .success-modal-body {
    background: #FFFFFF;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

#successModal .success-icon-wrapper {
    display: inline-block;
    animation: checkmarkPop 0.5s ease-out;
}

#successModal .modal-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #000000;
    text-transform: uppercase;
    letter-spacing: 1px;
}

#successModal #successModalMessage {
    color: #5a6c7d;
    font-size: 0.95rem;
    line-height: 1.6;
}

#successModal .btn-outline-light {
    border: 2px solid #0d6efd;
    color: #FFFFFF;
    background: #0d6efd;
    font-weight: 600;
    letter-spacing: 0.5px;
    font-size: 0.85rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

#successModal .btn-outline-light:hover {
    background: #0b5ed7;
    border-color: #0b5ed7;
    transform: translateY(-2px);
}

@keyframes checkmarkPop {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    50% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}
</style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
                passwordInput.focus();
            });

            document.getElementById('loginForm').addEventListener('submit', function(e) {
                const email = document.querySelector('input[name="email"]').value.trim();
                const password = document.querySelector('input[name="password"]').value.trim();
                
                if (!email || !password) {
                    e.preventDefault();
                    alert('Please fill in all required fields');
                    if (!email) {
                        document.querySelector('input[name="email"]').focus();
                    } else {
                        document.querySelector('input[name="password"]').focus();
                    }
                    return;
                }

                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(email)) {
                    e.preventDefault();
                    alert('Please enter a valid email address');
                    document.querySelector('input[name="email"]').focus();
                    return;
                }

                const loginBtn = document.getElementById('loginBtn');
                const originalText = loginBtn.innerHTML;
                loginBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Signing in...';
                loginBtn.disabled = true;
            });

            const emailField = document.querySelector('input[name="email"]');
            if (emailField && !emailField.value) {
                emailField.focus();
            }

            // Show success modal after password reset or account creation
            const params = new URLSearchParams(window.location.search);
            let successMessage = '';

            if (params.get('reset') === 'success') {
                successMessage = 'You have successfully changed your password.';
            } else if (params.get('verified') === '1') {
                successMessage = 'You have successfully created your Electripid account.';
            }

            if (successMessage) {
                const modalElement = document.getElementById('successModal');
                const messageElement = document.getElementById('successModalMessage');
                const okButton = document.getElementById('successModalOk');

                if (modalElement && messageElement && okButton) {
                    messageElement.textContent = successMessage;
                    const successModal = new bootstrap.Modal(modalElement);

                    okButton.addEventListener('click', function () {
                        // Return to clean login URL without query parameters
                        window.location.href = 'login.php';
                    });

                    successModal.show();
                }
            }
        });
    </script>
</body>
</html>