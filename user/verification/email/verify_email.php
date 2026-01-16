<?php
    session_start();
    require_once __DIR__ . '/../../../connect.php';
    require_once __DIR__ . '/phpmailer.php';
    require_once __DIR__ . '/resend_code.php';
    require_once __DIR__ . '/../../api/update_sync_user.php';

    if (!isset($_SESSION['pending_registration']) && !isset($_SESSION['email_verification'])) {
        header('Location: ../../login.php');
        exit;
    }

    $error = '';
    $success = '';

    // Check for message from login redirect
    if (isset($_GET['message']) && $_GET['message'] === 'verify_email') {
        $success = 'Please verify your email to continue. A verification code has been sent to your email address.';
    }

    $logDir = realpath(__DIR__ . '/../../../') . '/log';
    if (!is_dir($logDir)) mkdir($logDir, 0777, true);

    $debugLog = $logDir . '/sync_debug.log';
    $errorLog = $logDir . '/sync_errors.log';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        if (isset($_POST['verify'])) {
            $code = trim($_POST['code']);

            if (empty($code)) {
                $error = "Please enter the verification code.";
            } else {
                // Handle both pending registration and existing user verification
                if (isset($_SESSION['pending_registration'])) {
                    // Registration verification
                    if (!isset($_SESSION['pending_registration']['verification_id'])) {
                        $error = "Session expired. Please start registration again.";
                    } else {
                        $verification_id = $_SESSION['pending_registration']['verification_id'];

                        $stmt = $conn->prepare("SELECT verification_id, expires_at  FROM VERIFICATION  WHERE verification_id=? AND verification_code=? AND verification_type='email' AND is_verified=0");
                        $stmt->bind_param("is", $verification_id, $code);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows === 1) {
                            $row = $result->fetch_assoc();

                            if (strtotime($row['expires_at']) >= time()) {
                                $pending_data = $_SESSION['pending_registration'];
                                $source_system = 'Electripid'; // Track source

                                $email_check = $conn->prepare("SELECT user_id FROM USER WHERE email=? LIMIT 1");
                                $email_check->bind_param("s", $pending_data['email']);
                                $email_check->execute();
                                $email_result = $email_check->get_result();
                                if ($email_result->num_rows > 0) {
                                    $error = "This email is already registered. Please login or use another email.";
                                } else {
                                    $stmt_insert = $conn->prepare("INSERT INTO USER (fname, lname, email, cp_number, city, barangay, password, source_system) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                                    $stmt_insert->bind_param(
                                        "ssssssss",
                                        $pending_data['fname'],
                                        $pending_data['lname'],
                                        $pending_data['email'],
                                        $pending_data['cp_number'],
                                        $pending_data['city'],
                                        $pending_data['barangay'],
                                        $pending_data['password'],
                                        $source_system
                                    );
                                    $stmt_insert->execute();
                                    $user_id = $conn->insert_id;

                                    syncUserToAirlyft($user_id);

                                    // Create HOUSEHOLD record
                                    $provider_id = $pending_data['provider_id'];
                                    $stmt_household = $conn->prepare("INSERT INTO HOUSEHOLD (user_id, provider_id) VALUES (?, ?)");
                                    $stmt_household->bind_param("ii", $user_id, $provider_id);
                                    $stmt_household->execute();

                                    $stmt_update = $conn->prepare("UPDATE VERIFICATION SET user_id=?, is_verified=1 WHERE verification_id=?");
                                    $stmt_update->bind_param("ii", $user_id, $row['verification_id']);
                                    $stmt_update->execute();

                                    unset($_SESSION['pending_registration']);
                                    header("Location: ../../login.php?verified=1");
                                    exit;
                                }
                            } else {
                                $error = "Verification code expired.";
                            }
                        } else {
                            $error = "Invalid verification code.";
                        }
                    }
                } elseif (isset($_SESSION['email_verification'])) {
                    // Existing user email verification
                    $user_id = $_SESSION['email_verification']['user_id'];
                    $expected_code = $_SESSION['email_verification']['verification_code'];
                    $expires_at = $_SESSION['email_verification']['expires_at'];
                    $email_to_verify = $_SESSION['email_verification']['email'];
                    $original_email = $_SESSION['email_verification']['original_email'] ?? $email_to_verify;

                    if (strtotime($expires_at) < time()) {
                        $error = "Verification code expired.";
                    } elseif ($code === $expected_code) {
                        $verification_success = false;

                        // Check if this is a new email verification
                        if ($email_to_verify !== $original_email) {
                            // Check if the new email is already taken by another user
                            $check_email_stmt = $conn->prepare("SELECT user_id FROM USER WHERE email=? AND user_id != ? LIMIT 1");
                            $check_email_stmt->bind_param("si", $email_to_verify, $user_id);
                            $check_email_stmt->execute();
                            $check_email_result = $check_email_stmt->get_result();

                            if ($check_email_result->num_rows > 0) {
                                $error = "This email address is already registered by another user. Please choose a different email address.";
                            } else {
                                // This is a new email - update the user's email address
                                $update_email_stmt = $conn->prepare("UPDATE USER SET email=? WHERE user_id=?");
                                $update_email_stmt->bind_param("si", $email_to_verify, $user_id);
                                $update_email_stmt->execute();

                                // Sync email update to Airlyft
                                syncUserToAirlyft($user_id);

                                // Mark any existing email verifications as verified for this user
                                $stmt_update_existing = $conn->prepare("UPDATE VERIFICATION SET is_verified=1 WHERE user_id=? AND verification_type='email'");
                                $stmt_update_existing->bind_param("i", $user_id);
                                $stmt_update_existing->execute();

                                $verification_success = true;
                            }
                        } else {
                            // This is verification of existing email
                            $stmt_update = $conn->prepare("UPDATE VERIFICATION SET is_verified=1 WHERE user_id=? AND verification_type='email' AND verification_code=? AND is_verified=0");
                            $stmt_update->bind_param("is", $user_id, $code);
                            $stmt_update->execute();

                            $verification_success = true;
                        }

                        if ($verification_success) {
                            // Check if this verification came from login (email_verification session exists)
                            // If so, redirect to login with success message, otherwise to settings
                            $came_from_login = isset($_SESSION['email_verification']);
                            unset($_SESSION['email_verification']);

                            if ($came_from_login) {
                                header("Location: ../../login.php?login_verified=1");
                            } else {
                                header("Location: ../../settings.php?verified=1");
                            }
                            exit;
                        }
                    } else {
                        $error = "Invalid verification code.";
                    }
                } else {
                    $error = "Session expired. Please try again.";
                }
            }
        }

        if (isset($_POST['resend'])) {
            $result = resendVerificationCode();
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Electripid</title>
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
        <h2 class="mb-2 text-center">Verify Your Email</h2>
        <p class="text-muted text-center mb-4">Enter the 6-digit code sent to your email.</p>

        <?php if(!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if(!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" id="verifyForm" class="mt-3">
            <input type="hidden" name="code" id="fullCode" required>
            
            <div class="code-input-container">
                <div class="code-input-group">
                    <input type="text" class="code-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" id="code1" autocomplete="off" required>
                    <input type="text" class="code-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" id="code2" autocomplete="off" required>
                    <input type="text" class="code-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" id="code3" autocomplete="off" required>
                    <input type="text" class="code-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" id="code4" autocomplete="off" required>
                    <input type="text" class="code-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" id="code5" autocomplete="off" required>
                    <input type="text" class="code-input-box" maxlength="1" pattern="[0-9]" inputmode="numeric" id="code6" autocomplete="off" required>
                </div>
            </div>
            
            <button type="submit" name="verify" class="btn btn-primary w-100">Verify</button>
        </form>

        <form method="POST" class="mt-3">
            <button type="submit" name="resend" class="btn btn-outline-secondary w-100">Resend Code</button>
        </form>
        
        <p class="text-center text-muted small mt-3 mb-0" style="font-size: 0.75rem;">Electripid admin will never ask for your own 6 digit code</p>
        <p class="text-center mt-3 mb-0">
            <a href="<?php echo isset($_SESSION['email_verification']) ? '../../settings.php' : '../../register.php'; ?>" class="text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </p>
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
                    // Set custom validation message on first input
                    inputElements[0].setCustomValidity('Please enter the complete 6-digit code.');
                    inputElements[0].reportValidity();
                    inputElements[0].focus();
                    return false;
                } else {
                    // Clear any custom validity
                    inputElements.forEach(input => input.setCustomValidity(''));
                }
                
                document.getElementById('fullCode').value = fullCode;
            });
            
            // Clear custom validity when user starts typing
            inputElements.forEach(input => {
                input.addEventListener('input', function() {
                    this.setCustomValidity('');
                });
            });
        });
    </script>
</body>
</html>
