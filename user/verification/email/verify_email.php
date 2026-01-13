<?php
    session_start();
    require_once __DIR__ . '/../../../connect.php';
    require_once __DIR__ . '/phpmailer.php';
    require_once __DIR__ . '/resend_code.php';

    if (!isset($_SESSION['pending_registration'])) {
        header('Location: ../../register.php');
        exit;
    }

    $error = '';
    $success = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Verify button clicked
        if (isset($_POST['verify'])) {
            $code = trim($_POST['code']);

            if (empty($code)) {
                $error = "Please enter the verification code.";
            } else {
                // Check code in VERIFICATION table
                $stmt = $conn->prepare("SELECT verification_id, expires_at FROM VERIFICATION WHERE verification_code=? AND verification_type='email' AND is_verified=0");
                $stmt->bind_param("s", $code);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $row = $result->fetch_assoc();
                    if (strtotime($row['expires_at']) >= time()) {
                        // Valid → insert user
                        $pending_data = $_SESSION['pending_registration'];

                        $stmt_insert = $conn->prepare("INSERT INTO USER (fname, lname, email, cp_number, city, barangay, password, role, acc_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'user', 'active')");
                        $stmt_insert->bind_param(
                            "sssssss",
                            $pending_data['fname'],
                            $pending_data['lname'],
                            $pending_data['email'],
                            $pending_data['cp_number'],
                            $pending_data['city'],
                            $pending_data['barangay'],
                            $pending_data['password']
                        );
                        $stmt_insert->execute();
                        $user_id = $conn->insert_id;

                        // Update VERIFICATION table
                        $stmt_update = $conn->prepare("UPDATE VERIFICATION SET user_id=?, is_verified=1 WHERE verification_id=?");
                        $stmt_update->bind_param("ii", $user_id, $row['verification_id']);
                        $stmt_update->execute();

                        // Clear session
                        unset($_SESSION['pending_registration']);

                        header("Location: ../../login.php?verified=1");
                        exit;

                    } else {
                        $error = "Verification code expired.";
                    }
                } else {
                    $error = "Invalid verification code.";
                }
            }
        }

        // Resend button clicked
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

        <form method="POST" class="mt-3">
            <button type="submit" name="resend" class="btn btn-outline-secondary w-100">Resend Code</button>
        </form>
        
        <p class="text-center text-muted small mt-3 mb-0" style="font-size: 0.75rem;">Electripid admin will never ask for your own 6 digit code</p>
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
    </script>
</body>
</html>
