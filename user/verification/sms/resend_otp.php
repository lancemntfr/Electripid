<?php
require_once __DIR__ . '/../../../connect.php';
require_once __DIR__ . '/send_sms.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['pending_phone'])) {
    echo json_encode(['success' => false, 'error' => 'No pending verification']);
    exit;
}

$user_id = $_SESSION['user_id'];
$phone = $_SESSION['pending_phone'];

// Check if user's email is verified (required to access settings)
$email_check = $conn->prepare("SELECT verification_id FROM VERIFICATION WHERE user_id=? AND verification_type='email' AND is_verified=1 LIMIT 1");
$email_check->bind_param("i", $user_id);
$email_check->execute();
$email_result = $email_check->get_result();

if ($email_result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Email not verified']);
    exit;
}

$otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
$expires = date("Y-m-d H:i:s", strtotime("+15 minutes"));

// Update the most recent unverified SMS OTP
$stmt = $conn->prepare("
    UPDATE VERIFICATION SET verification_code=?, expires_at=?, is_verified=0
    WHERE user_id=? AND verification_type='sms' AND is_verified=0
    ORDER BY created_at DESC LIMIT 1
");
$stmt->bind_param("ssi", $otp, $expires, $user_id);
$stmt->execute();

// If no record was updated, create a new one
if ($stmt->affected_rows === 0) {
    $insert_stmt = $conn->prepare("
        INSERT INTO VERIFICATION (user_id, verification_type, verification_code, expires_at)
        VALUES (?, 'sms', ?, ?)
    ");
    $insert_stmt->bind_param("iss", $user_id, $otp, $expires);
    $insert_stmt->execute();
}

$sms_sent = sendSMS($phone, "Electripid OTP: $otp (expires in 15 minutes)");

if (!$sms_sent) {
    error_log("Failed to resend SMS OTP to $phone for user $user_id");
    echo json_encode(['success' => false, 'error' => 'Failed to send SMS. Please try again.']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'OTP resent successfully']);
exit;
