<?php
    require_once __DIR__ . '/../../../connect.php';
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
            background: linear-gradient(135deg, #e3f2fd 0%, white 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .verify-container {
            max-width: 450px;
            width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 40px;
        }
        .otp-input {
            font-size: 2rem;
            text-align: center;
            letter-spacing: 0.5rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="text-center mb-4">
            <div class="mb-3">
                <i class="bi bi-phone-fill text-primary" style="font-size: 3rem;"></i>
            </div>
            <h2>Verify Phone Number</h2>
            <p class="text-muted">Enter the 6-digit code sent to<br><strong><?= htmlspecialchars($phone) ?></strong></p>
        </div>

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

        <form method="POST" id="verifyForm">
            <div class="mb-4">
                <label class="form-label">Verification Code</label>
                <input type="text" class="form-control otp-input" name="code" id="code" 
                       maxlength="6" pattern="[0-9]{6}" required autocomplete="off" 
                       placeholder="000000" autofocus>
                <small class="text-muted">Code expires in 15 minutes</small>
            </div>

            <div class="d-grid gap-2 mb-3">
                <button type="submit" name="verify" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-circle me-2"></i>Verify
                </button>
            </div>

            <div class="text-center">
                <p class="text-muted small mb-2">Didn't receive the code?</p>
                <button type="button" class="btn btn-link text-decoration-none" onclick="resendOTP()" id="resendBtn">
                    <i class="bi bi-arrow-clockwise me-1"></i>Resend Code
                </button>
            </div>

            <div class="text-center mt-4">
                <a href="../../settings.php" class="text-decoration-none small">
                    <i class="bi bi-arrow-left me-1"></i>Back to Settings
                </a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const codeInput = document.getElementById('code');
            
            // Auto-format and validate OTP input
            codeInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
            });

            codeInput.focus();
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
                    document.querySelector('.verify-container').insertBefore(alertDiv, document.querySelector('form'));
                    
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
