<?php
    session_start();
    require_once __DIR__ . '/../../../connect.php';
    require_once __DIR__ . '/phpmailer.php';

    if (!isset($_SESSION['user_id'])) {
        header('Location: ../../login.php');
        exit;
    }

    $user_id = $_SESSION['user_id'];

    // Get user email
    $stmt = $conn->prepare("SELECT email FROM USER WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header('Location: ../../login.php');
        exit;
    }

    $user = $result->fetch_assoc();
    $original_email = $user['email'];

    // Check if a new email was provided (for email change verification)
    $email = isset($_GET['new_email']) ? trim($_GET['new_email']) : $original_email;

    // Validate the email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: ../../settings.php?error=invalid_email');
        exit;
    }

    // Check if this is a new email and if it's already taken by another user
    if ($email !== $original_email) {
        $email_check_stmt = $conn->prepare("SELECT user_id FROM USER WHERE email = ? AND user_id != ? LIMIT 1");
        $email_check_stmt->bind_param("si", $email, $user_id);
        $email_check_stmt->execute();
        $email_check_result = $email_check_stmt->get_result();

        if ($email_check_result->num_rows > 0) {
            // Email already taken by another user, redirect back with error
            header('Location: ../../settings.php?error=email_taken');
            exit;
        }
    } else {
        // This is the original email - check if it's already verified
        $check_stmt = $conn->prepare("SELECT verification_id FROM VERIFICATION WHERE user_id = ? AND verification_type = 'email' AND is_verified = 1 LIMIT 1");
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            // Email already verified, redirect back to settings
            header('Location: ../../settings.php');
            exit;
        }
    }

    // Generate verification code
    $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires_at = date("Y-m-d H:i:s", strtotime("+15 minutes"));

    // Insert or update verification record
    $insert_stmt = $conn->prepare("INSERT INTO VERIFICATION (user_id, verification_type, verification_code, expires_at, is_verified) VALUES (?, 'email', ?, ?, 0) ON DUPLICATE KEY UPDATE verification_code = VALUES(verification_code), expires_at = VALUES(expires_at)");
    $insert_stmt->bind_param("iss", $user_id, $code, $expires_at);
    $insert_stmt->execute();

    // Send verification email
    $type = 'verification';
    $email_sent = sendVerificationEmail($email, $code, $type);

    if ($email_sent) {
        // Store verification info in session for the verification page
        $_SESSION['email_verification'] = [
            'user_id' => $user_id,
            'email' => $email,
            'original_email' => $original_email,
            'verification_code' => $code,
            'expires_at' => $expires_at
        ];

        // Redirect to verification page
        header('Location: verify_email.php');
        exit;
    } else {
        // Failed to send email, redirect back with error
        header('Location: ../../settings.php?error=email_send_failed');
        exit;
    }
?>