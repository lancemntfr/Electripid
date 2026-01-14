<?php
    session_start();
    require_once __DIR__ . '/../../../connect.php';

    $error = '';

    if (!isset($_SESSION['fp_user_id'])) {
        header("Location: forgot_password.php");
        exit;
    }

    $user_id = $_SESSION['fp_user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $code = trim($_POST['code']);

        $stmt = $conn->prepare("
            SELECT verification_id FROM VERIFICATION
            WHERE user_id=? 
            AND password_reset_code=? 
            AND expires_at > NOW()
        ");
        $stmt->bind_param("is", $user_id, $code);
        $stmt->execute();

        if ($stmt->get_result()->num_rows === 0) {
            $error = "Invalid or expired code.";
        } else {
            $_SESSION['fp_verified'] = true;
            header("Location: reset_password.php");
            exit;
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Reset Code - Electripid</title>
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
        .form-error-container {
            border: 2px solid #f8d7da;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 15px;
            background: #fff5f5;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #842029;
            font-size: 0.9rem;
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
        <h2 class="mb-2 text-center">Reset Password</h2>
        <p class="text-muted text-center mb-4">Enter the 6-digit code sent to you.</p>
        <div id="formError"></div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
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
            
            <button type="submit" class="btn btn-primary w-100">Verify</button>
        </form>

        <?php if (isset($_SESSION['fp_method'])): ?>
            <form method="POST" action="send_reset_code.php" class="mt-3">
                <input type="hidden" name="method" value="<?= htmlspecialchars($_SESSION['fp_method']) ?>">
                <button type="submit" class="btn btn-outline-secondary w-100">Resend Code</button>
            </form>
        <?php endif; ?>
        
        <p class="text-center text-muted small mt-3 mb-0" style="font-size: 0.75rem;">Electripid admin will never ask your own 6 digit code</p>
        <p class="text-center mt-3 mb-0">
            <a href="choose_reset_method.php" class="text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = ['code1', 'code2', 'code3', 'code4', 'code5', 'code6'];
            const inputElements = inputs.map(id => document.getElementById(id));
            const formError = document.getElementById('formError');
            
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
                    
                    if (formError) {
                        formError.innerHTML = '';
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
                        if (formError) {
                            formError.innerHTML = '';
                        }
                        updateFullCode();
                    }
                });
                
                input.addEventListener('keydown', function(e) {
                    // Handle backspace
                    if (e.key === 'Backspace' && !e.target.value && index > 0) {
                        inputElements[index - 1].focus();
                        inputElements[index - 1].value = '';
                        if (formError) {
                            formError.innerHTML = '';
                        }
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
                    if (formError) {
                        formError.innerHTML = '<div class="form-error-container"><i class="bi bi-exclamation-triangle-fill"></i><span>Please enter the complete 6-digit code.</span></div>';
                        formError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    inputElements[0].focus();
                    return false;
                }
                
                document.getElementById('fullCode').value = fullCode;
            });
        });
    </script>
</body>
</html>
