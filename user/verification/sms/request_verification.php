<?php
    session_start();
    require_once __DIR__ . '/../../../connect.php';
    require_once __DIR__ . '/send_sms.php';

    if (!isset($_SESSION['user_id'])) {
        header('Location: ../../login.php');
        exit;
    }

    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT cp_number FROM USER WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header('Location: ../../login.php');
        exit;
    }

    $user = $result->fetch_assoc();
    $original_phone = $user['cp_number'];

    $phone_digits = isset($_GET['new_phone']) ? trim($_GET['new_phone']) : '';
    $phone = '';

    if ($phone_digits) {
        if (!preg_match('/^[0-9]{10}$/', $phone_digits)) {
            header('Location: ../../settings.php?error=invalid_phone');
            exit;
        }
        $phone = '+63' . $phone_digits;
    } else {
        if ($original_phone) {
            $digits_only = preg_replace('/\D/', '', $original_phone);
            $phone_digits = substr($digits_only, -10);
            $phone = '+63' . $phone_digits;
        }
    }

    if (!$phone) {
        header('Location: ../../settings.php?error=no_phone');
        exit;
    }

    if ($phone_digits && $original_phone !== $phone) {
        // Check if another user already has this phone number
        $phone_check_stmt = $conn->prepare("SELECT user_id FROM USER WHERE cp_number = ? AND user_id != ? LIMIT 1");
        $phone_check_stmt->bind_param("si", $phone, $user_id);
        $phone_check_stmt->execute();
        $phone_check_result = $phone_check_stmt->get_result();

        if ($phone_check_result->num_rows > 0) {
            header('Location: ../../settings.php?error=phone_taken');
            exit;
        }
    } else {
        $phone_verification_check = $conn->prepare("SELECT verification_id FROM VERIFICATION WHERE user_id=? AND verification_type='sms' AND is_verified=1 LIMIT 1");
        $phone_verification_check->bind_param("i", $user_id);
        $phone_verification_check->execute();
        $phone_verification_result = $phone_verification_check->get_result();

        if ($phone_verification_result->num_rows > 0) {
            // Phone already verified, redirect back to settings
            header('Location: ../../settings.php');
            exit;
        }
    }

    $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires_at = date("Y-m-d H:i:s", strtotime("+15 minutes"));

    $insert_stmt = $conn->prepare("INSERT INTO VERIFICATION (user_id, verification_type, verification_code, expires_at, is_verified) VALUES (?, 'sms', ?, ?, 0) ON DUPLICATE KEY UPDATE verification_code = VALUES(verification_code), expires_at = VALUES(expires_at)");
    $insert_stmt->bind_param("iss", $user_id, $code, $expires_at);
    $insert_stmt->execute();

    $message = "Electripid Phone Verification: $code (expires in 15 minutes)";
    $sms_sent = sendSMS($phone, $message);

    if ($sms_sent) {
        $_SESSION['pending_phone'] = $phone;
        $_SESSION['phone_verification'] = [
            'user_id' => $user_id,
            'phone' => $phone,
            'original_phone' => $original_phone,
            'verification_code' => $code,
            'expires_at' => $expires_at
        ];

        header('Location: verify_otp.php');
        exit;
    } else {
        header('Location: ../../settings.php?error=sms_send_failed');
        exit;
    }
?>