<?php
    session_start();
    require_once __DIR__ . '/../connect.php';
    require_once __DIR__ . '/phpmailer.php';

    if (!isset($_SESSION['pending_user_id'])) {
        die("No user pending verification.");
    }

    $user_id = $_SESSION['pending_user_id'];

    // Generate new 6-digit code
    $new_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires_at = date("Y-m-d H:i:s", strtotime("+15 minutes"));

    // Update DB
    $stmt = $conn->prepare("
        UPDATE VERIFICATION 
        SET verification_code = ?, expires_at = ?, is_verified = 0
        WHERE user_id = ?
    ");
    $stmt->bind_param("ssi", $new_code, $expires_at, $user_id);
    $stmt->execute();

    // Fetch user email
    $stmt = $conn->prepare("SELECT email FROM USER WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $email = $user['email'];

    // Send new code via PHPMailer
    sendVerificationEmail($email, $new_code);

    echo "A new verification code has been sent to your email.";
?>
