<?php
    session_start();
    require_once __DIR__ . '/../../../connect.php';
    require_once __DIR__ . '/../email/phpmailer.php';
    require_once __DIR__ . '/../sms/send_sms.php';

    if (!isset($_SESSION['fp_user_id'], $_POST['method'])) {
        header("Location: forgot_password.php");
        exit;
    }

    $user_id = $_SESSION['fp_user_id'];
    $email   = $_SESSION['fp_email'];
    $method  = $_POST['method'];

    if (!in_array($method, ['email', 'sms'], true)) {
        header("Location: choose_reset_method.php");
        exit;
    }

    $phone = null;
    if ($method === 'sms') {
        $user_id_int = (int) $user_id;
        $result = executeQuery("SELECT cp_number FROM USER WHERE user_id={$user_id_int} LIMIT 1");

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $phone = $user['cp_number'] ?? null;
        }

        if (empty($phone)) {
            header("Location: choose_reset_method.php");
            exit;
        }
    }

    $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires_at = date("Y-m-d H:i:s", strtotime("+15 minutes"));
    $type = 'password_reset';

    $stmt = $conn->prepare("INSERT INTO VERIFICATION (user_id, verification_type, password_reset_code, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $method, $code, $expires_at);
    $stmt->execute();

    if ($method === 'email') {
        sendVerificationEmail($email, $code, $type);
    }

    if ($method === 'sms') {
        sendSMS($phone, "Electripid Reset Password: $code (expires in 15 minutes)");
    }

    $_SESSION['fp_method'] = $method;

    header("Location: verify_reset_code.php");
    exit;
?>