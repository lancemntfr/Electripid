<?php
require_once __DIR__ . '/../../../connect.php';
require_once __DIR__ . '/send_sms.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$phone = trim($_POST['cp_number'] ?? '');

if (empty($phone)) {
    echo json_encode(['success' => false, 'error' => 'Phone number is required']);
    exit;
}

// Ensure +63 country code
if (!preg_match('/^\+63/', $phone)) {
    // Remove leading 0 if present, add +63
    $phone = preg_replace('/^0/', '', $phone);
    $phone = '+63' . $phone;
}

// Remove spaces and dashes for storage
$phone_clean = preg_replace('/[\s\-\(\)]/', '', $phone);

// Validate Philippine phone number format (+63XXXXXXXXXX)
if (!preg_match('/^\+63[0-9]{10}$/', $phone_clean)) {
    echo json_encode(['success' => false, 'error' => 'Invalid phone number format. Please use +63 followed by 10 digits.']);
    exit;
}

$otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
$expires = date("Y-m-d H:i:s", strtotime("+15 minutes"));

// Check if user's email is verified (required to access settings)
$email_check = $conn->prepare("SELECT verification_id FROM VERIFICATION WHERE user_id=? AND verification_type='email' AND is_verified=1 LIMIT 1");
$email_check->bind_param("i", $user_id);
$email_check->execute();
$email_result = $email_check->get_result();

if ($email_result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Please verify your email first before adding a phone number.']);
    exit;
}

// Delete old unverified SMS OTPs for this user
$delete_stmt = $conn->prepare("DELETE FROM VERIFICATION WHERE user_id=? AND verification_type='sms' AND is_verified=0");
$delete_stmt->bind_param("i", $user_id);
$delete_stmt->execute();

// Insert new OTP
$stmt = $conn->prepare("
    INSERT INTO VERIFICATION (user_id, verification_type, verification_code, expires_at)
    VALUES (?, 'sms', ?, ?)
");
$stmt->bind_param("iss", $user_id, $otp, $expires);
$stmt->execute();

$_SESSION['pending_phone'] = $phone_clean;

// Send SMS
$sms_sent = sendSMS($phone_clean, "Electripid OTP: $otp (expires in 15 minutes)");

if (!$sms_sent) {
    // If SMS failed, still save OTP but log error
    error_log("Failed to send SMS OTP to $phone_clean for user $user_id");
    echo json_encode(['success' => false, 'error' => 'Failed to send SMS. Please check your phone number and try again.']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'OTP sent successfully']);
exit;
