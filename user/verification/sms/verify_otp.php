<?php
    require_once __DIR__ . '/../../../connect.php';
    require_once __DIR__ . '/../../api/update_sync_user.php';
    session_start();

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['pending_phone'])) {
        header('Location: ../../settings.php');
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $phone = $_SESSION['pending_phone'];
    $error = '';
    $success = '';

    // Check if user's email is verified (required to access settings)
    $email_check = $conn->prepare("SELECT verification_id FROM VERIFICATION WHERE user_id=? AND verification_type='email' AND is_verified=1 LIMIT 1");
    $email_check->bind_param("i", $user_id);
    $email_check->execute();
    $email_result = $email_check->get_result();

    if ($email_result->num_rows === 0) {
        header('Location: ../../settings.php?error=email_not_verified');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['verify'])) {
            $code = trim($_POST['code'] ?? '');

            if (empty($code)) {
                $error = "Please enter the verification code.";
            } else {
                // Check for unverified SMS OTP (is_verified=0 for SMS verification)
                $stmt = $conn->prepare("
                    SELECT verification_id FROM VERIFICATION
                    WHERE user_id=? AND verification_code=? AND verification_type='sms' AND is_verified=0 AND expires_at>NOW()
                    ORDER BY created_at DESC LIMIT 1
                ");
                $stmt->bind_param("is", $user_id, $code);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 0) {
                    $error = "Invalid or expired OTP.";
                } else {
                    $verification_row = $result->fetch_assoc();
                    $verification_id = $verification_row['verification_id'];
                    
                    // Mark SMS verification as verified (is_verified=1)
                    $update_verification = $conn->prepare("UPDATE VERIFICATION SET is_verified=1 WHERE verification_id=?");
                    $update_verification->bind_param("i", $verification_id);
                    $update_verification->execute();

                    // Save phone to database (with +63 country code)
                    $update_stmt = $conn->prepare("UPDATE USER SET cp_number=? WHERE user_id=?");
                    $update_stmt->bind_param("si", $phone, $user_id);
                    $update_stmt->execute();

                    syncUserToAirlyft($user_id);

                    unset($_SESSION['pending_phone']);
                    header("Location: ../../settings.php?verified=1");
                    exit;
                }
            }
        }

    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Electripid - Verify Phone Number</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../../assets/css/user.css">
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
        .code-input-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 30px 0;
        }
        .code-input-box {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            background: white;
            transition: all 0.3s ease;
        }
        .code-input-box:focus {
            outline: none;
            border-color: #1e88e5;
            box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.1);
        }
        .code-input-group {
            display: flex;
            gap: 8px;
            align-items: center;
            justify-content: center;
        }
        @media (max-width: 576px) {
            .code-input-box {
                width: 40px;
                height: 50px;
                font-size: 1.2rem;
            }
            .code-input-group {
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <h2 class="mb-2 text-center">Verify Phone Number</h2>
        <p class="text-muted text-center mb-4">Enter the 6-digit code sent to <strong><?= htmlspecialchars($phone) ?></strong></p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" id="verifyForm" class="mt-3">
            <input type="hidden" name="code" id="fullCode" required>
            
            <div class="code-input-container">
                <div class="code-input-group">
                    <input type="text" class="code-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" id="code1" autocomplete="off">
                    <input type="text" class="code-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" id="code2" autocomplete="off">
                    <input type="text" class="code-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" id="code3" autocomplete="off">
                    <input type="text" class="code-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" id="code4" autocomplete="off">
                    <input type="text" class="code-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" id="code5" autocomplete="off">
                    <input type="text" class="code-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" id="code6" autocomplete="off">
                </div>
            </div>
            
            <button type="submit" name="verify" class="btn btn-primary w-100">Verify</button>
        </form>

        <div class="text-center mt-3">
            <p class="text-muted small mb-2">Didn't receive the code?</p>
            <button type="button" class="btn btn-outline-secondary w-100" onclick="resendOTP()" id="resendBtn">
                <i class="bi bi-arrow-clockwise me-1"></i>Resend Code
            </button>
        </div>

        <div class="text-center mt-3">
            <a href="../../settings.php" class="text-decoration-none small">
                <i class="bi bi-arrow-left me-1"></i>Back to Settings
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = ['code1', 'code2', 'code3', 'code4', 'code5', 'code6'];
            const inputElements = inputs.map(id => document.getElementById(id));
            
            // Focus first input on load
            inputElements[0].focus();
            
            // Handle input and auto-advance
            inputElements.forEach((input, index) => {
                input.addEventListener('input', function(e) {
                    // Only allow numbers
                    if (!/^\d$/.test(e.target.value)) {
                        e.target.value = '';
                        return;
                    }
                    
                    // Auto-advance to next input
                    if (e.target.value && index < inputElements.length - 1) {
                        inputElements[index + 1].focus();
                    }
                    
                    updateFullCode();
                });
                
                // Handle paste
                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedData = (e.clipboardData || window.clipboardData).getData('text');
                    if (/^\d{6}$/.test(pastedData)) {
                        pastedData.split('').forEach((digit, idx) => {
                            if (idx < inputElements.length) {
                                inputElements[idx].value = digit;
                            }
                        });
                        inputElements[inputElements.length - 1].focus();
                        updateFullCode();
                    }
                });
                
                input.addEventListener('keydown', function(e) {
                    // Handle backspace
                    if (e.key === 'Backspace' && !e.target.value && index > 0) {
                        inputElements[index - 1].focus();
                        inputElements[index - 1].value = '';
                        updateFullCode();
                    }
                });
                
                // Prevent non-numeric input
                input.addEventListener('keypress', function(e) {
                    if (!/^\d$/.test(e.key) && !['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
                        e.preventDefault();
                    }
                });
            });
            
            // Update hidden input with full code
            function updateFullCode() {
                const fullCode = inputElements.map(input => input.value).join('');
                document.getElementById('fullCode').value = fullCode;
            }
            
            // Form submission validation
            document.getElementById('verifyForm').addEventListener('submit', function(e) {
                const fullCode = inputElements.map(input => input.value).join('');
                
                if (fullCode.length !== 6) {
                    e.preventDefault();
                    alert('Please enter the complete 6-digit code.');
                    inputElements[0].focus();
                    return false;
                }
                
                document.getElementById('fullCode').value = fullCode;
            });
        });

        async function resendOTP() {
            const resendBtn = document.getElementById('resendBtn');
            const originalText = resendBtn.innerHTML;
            resendBtn.disabled = true;
            resendBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending...';
            
            try {
                const response = await fetch('resend_otp.php', {
                    method: 'POST'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Show success message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show';
                    alertDiv.innerHTML = `
                        <i class="bi bi-check-circle-fill me-2"></i>${result.message || 'OTP resent successfully!'}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.querySelector('.reset-container').insertBefore(alertDiv, document.querySelector('form'));
                    
                    setTimeout(() => alertDiv.remove(), 3000);
                } else {
                    alert('Failed to resend OTP: ' + (result.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while resending OTP.');
            } finally {
                resendBtn.disabled = false;
                resendBtn.innerHTML = originalText;
            }
        }
    </script>
</body>
</html>
