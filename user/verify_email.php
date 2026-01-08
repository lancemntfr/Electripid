<?php
    require_once '../connect.php';

    $code = $_GET['code'] ?? '';

    if (empty($code)) {
        die("Invalid verification link.");
    }

    // Check verification code
    $query = "
    SELECT user_id FROM VERIFICATION
    WHERE verification_code = '$code'
    AND is_verified = 0
    AND expires_at > NOW()
    ";

    $result = executeQuery($query);

    if (mysqli_num_rows($result) === 0) {
        die("Verification link is invalid or expired.");
    }

    $data = mysqli_fetch_assoc($result);
    $user_id = $data['user_id'];

    // Mark verified
    executeQuery("
    UPDATE VERIFICATION
    SET is_verified = 1
    WHERE user_id = '$user_id'
    ");

    // Activate account
    executeQuery("
    UPDATE USER
    SET acc_status = 'active'
    WHERE user_id = '$user_id'
    ");

    echo "Email verified successfully! You may now <a href='login.php'>log in</a>.";
?>