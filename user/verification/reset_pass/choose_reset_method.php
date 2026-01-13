<?php
    session_start();
    require_once __DIR__ . '/../../../connect.php';

    if (!isset($_SESSION['fp_user_id'])) {
        header("Location: forgot_password.php");
        exit;
    }

    $hasPhone = $_SESSION['fp_has_phone'];
    $email = $_SESSION['fp_email'] ?? '';
    $maskedPhone = '';

    // Get phone number if available and mask it
    if ($hasPhone) {
        $user_id = $_SESSION['fp_user_id'];
        $stmt = $conn->prepare("SELECT cp_number FROM USER WHERE user_id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $phone = $user['cp_number'] ?? '';
            
            // Mask phone number: 09** *** **45 format
            if (!empty($phone) && strlen($phone) >= 11) {
                $maskedPhone = substr($phone, 0, 2) . '** *** **' . substr($phone, -2);
            } elseif (!empty($phone) && strlen($phone) >= 10) {
                $maskedPhone = substr($phone, 0, 2) . '** *** **' . substr($phone, -2);
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Reset Method - Electripid</title>
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
        .email-display {
            color: #333;
            font-size: 1rem;
            margin-bottom: 8px;
        }
        .option-card {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .option-card:hover {
            border-color: #1e88e5;
            background: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .option-icon {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        .option-content {
            flex: 1;
        }
        .option-title {
            font-weight: 600;
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 5px;
        }
        .option-description {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 3px;
        }
        .option-disclaimer {
            font-size: 0.75rem;
            color: #adb5bd;
            margin-top: 5px;
        }
        .option-card button {
            width: 100%;
            background: transparent;
            border: none;
            text-align: left;
            padding: 0;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <h2 class="mb-3">Reset Password</h2>
        <p class="text-muted mb-3">Select an option to receive your verification code</p>
        
        <?php if (!empty($email)): ?>
            <div class="email-display mb-4">
                <strong><?= htmlspecialchars($email) ?></strong>
            </div>
        <?php endif; ?>

        <form method="POST" action="send_reset_code.php" id="resetMethodForm">
            <input type="hidden" name="method" id="selectedMethod" value="">
            
            <div class="option-card" onclick="document.getElementById('selectedMethod').value='email'; document.getElementById('resetMethodForm').submit();">
                <div class="option-icon text-primary">
                    <i class="bi bi-envelope-fill"></i>
                </div>
                <div class="option-content">
                    <div class="option-title">Email</div>
                    <div class="option-description">Get a code sent to your email address</div>
                </div>
            </div>

            <?php if ($hasPhone): ?>
                <div class="option-card" onclick="document.getElementById('selectedMethod').value='sms'; document.getElementById('resetMethodForm').submit();">
                    <div class="option-icon text-success">
                        <i class="bi bi-chat-dots-fill"></i>
                    </div>
                    <div class="option-content">
                        <div class="option-title">Text message</div>
                        <?php if (!empty($maskedPhone)): ?>
                            <div class="option-description">Get a code on <?= htmlspecialchars($maskedPhone) ?></div>
                        <?php else: ?>
                            <div class="option-description">Get a code via SMS</div>
                        <?php endif; ?>
                        <div class="option-disclaimer">Message and data rates may apply.</div>
                    </div>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
