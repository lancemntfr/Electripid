<?php
    session_start();
    require_once __DIR__ . '/../../../connect.php';
    require_once __DIR__ . '/../../includes/validation.php';

    if (
        !isset($_SESSION['fp_user_id']) ||
        !isset($_SESSION['fp_verified']) ||
        $_SESSION['fp_verified'] !== true
    ) {
        header("Location: forgot_password.php");
        exit;
    }

    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        // Validate password using modular validation function
        $validation = validatePassword($password, $confirm);

        if (!$validation['valid']) {
            $error = $validation['error'];
        } else {
            // Password validation passed - proceed with password reset
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $user_id = $_SESSION['fp_user_id'];

            // Update password
            $stmt = $conn->prepare("
                UPDATE USER SET password=? WHERE user_id=?
            ");
            $stmt->bind_param("si", $hashed, $user_id);
            $stmt->execute();

            // Invalidate all reset codes
            $stmt = $conn->prepare("
                DELETE FROM VERIFICATION
                WHERE user_id=? AND password_reset_code IS NOT NULL
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            // Clear sessions
            unset($_SESSION['fp_user_id']);
            unset($_SESSION['fp_verified']);

            header("Location: ../../login.php?reset=success");
            exit;
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Electripid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e3f2fd 0%, white 100%);
        }
        .reset-container {
            max-width: 500px;
            width: 100%;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .eye-toggle {
            cursor: pointer;
        }
        .eye-toggle:hover {
            color: #1e88e5 !important;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <h2 class="mb-4">Reset Password</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" id="resetPasswordForm">
            <div class="mb-3">
                <label class="form-label">New Password <span class="text-danger">*</span></label>
                <div class="position-relative">
                    <input type="password" class="form-control" name="password" id="password" required minlength="8" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{8,}" placeholder="Enter new password" onkeyup="checkPasswordStrength()" autocomplete="new-password">
                    <button type="button" class="eye-toggle position-absolute text-secondary z-3 border-0 bg-transparent" id="togglePassword" style="right: 10px; top: 50%; transform: translateY(-50%);">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <div class="small text-secondary mt-1" style="font-size: 0.75rem;">
                    <div id="lengthReq"><i class="bi bi-circle"></i> 8+ characters</div>
                    <div id="caseReq"><i class="bi bi-circle"></i> Upper & lowercase</div>
                    <div id="numberReq"><i class="bi bi-circle"></i> One number</div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                <div class="position-relative">
                    <input type="password" class="form-control" name="confirm_password" id="confirm_password" required minlength="8" placeholder="Re-enter password" onkeyup="checkPasswordMatch()" autocomplete="new-password">
                    <button type="button" class="eye-toggle position-absolute text-secondary z-3 border-0 bg-transparent" id="toggleConfirmPassword" style="right: 10px; top: 50%; transform: translateY(-50%);">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <div class="mt-1" id="passwordMatch"></div>
            </div>

            <button type="submit" class="btn btn-primary w-100" id="resetBtn">Change Password</button>
        </form>
        <p class="text-center mt-3 mb-0">
            <a href="choose_reset_method.php" class="text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function setupPasswordToggle(inputId, toggleId) {
                const toggle = document.getElementById(toggleId);
                const input = document.getElementById(inputId);
                const icon = toggle.querySelector('i');
                
                toggle.addEventListener('click', function() {
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('bi-eye');
                        icon.classList.add('bi-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.remove('bi-eye-slash');
                        icon.classList.add('bi-eye');
                    }
                    input.focus();
                });
            }
            
            setupPasswordToggle('password', 'togglePassword');
            setupPasswordToggle('confirm_password', 'toggleConfirmPassword');
        });

        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const lengthReq = document.getElementById('lengthReq');
            const caseReq = document.getElementById('caseReq');
            const numberReq = document.getElementById('numberReq');
            
            const hasLength = password.length >= 8;
            const hasCase = /([a-z].*[A-Z])|([A-Z].*[a-z])/.test(password);
            const hasNumber = /[0-9]/.test(password);
            
            lengthReq.innerHTML = `<i class="bi ${hasLength ? 'bi-check-circle text-success' : 'bi-circle text-secondary'}"></i> 8+ characters`;
            caseReq.innerHTML = `<i class="bi ${hasCase ? 'bi-check-circle text-success' : 'bi-circle text-secondary'}"></i> Upper & lowercase`;
            numberReq.innerHTML = `<i class="bi ${hasNumber ? 'bi-check-circle text-success' : 'bi-circle text-secondary'}"></i> One number`;
        }
        
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (!confirmPassword) {
                matchDiv.innerHTML = '';
            } else {
                const isMatch = password === confirmPassword;
                const icon = isMatch ? 'bi-check-circle' : 'bi-x-circle';
                const color = isMatch ? 'text-success' : 'text-danger';
                const text = isMatch ? 'Passwords match' : 'Passwords do not match';
                matchDiv.innerHTML = `<small class="${color}"><i class="bi ${icon} me-1"></i> ${text}</small>`;
            }
        }

        document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            const resetBtn = document.getElementById('resetBtn');
            
            if (!passwordInput.checkValidity()) {
                e.preventDefault();
                passwordInput.reportValidity();
                passwordInput.focus();
                return;
            }

            if (!confirmPasswordInput.checkValidity()) {
                e.preventDefault();
                confirmPasswordInput.reportValidity();
                confirmPasswordInput.focus();
                return;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                document.getElementById('confirm_password').focus();
                return;
            }
            
            resetBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Resetting password...';
            resetBtn.disabled = true;
        });
    </script>
</body>
</html>
