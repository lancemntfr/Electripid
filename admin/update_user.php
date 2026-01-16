<?php
// Prevent any output before JSON
ob_start();
session_start();

require_once '../connect.php';
require_once 'sync_helper.php';

// Clear any output that might have been generated
ob_clean();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Check authentication - match admin_auth.php check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user_id = intval($_POST['user_id'] ?? 0);
$fname = trim($_POST['fname'] ?? '');
$lname = trim($_POST['lname'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = trim($_POST['role'] ?? 'user');
$city = trim($_POST['city'] ?? '');
$barangay = trim($_POST['barangay'] ?? '');
$cp_number = trim($_POST['cp_number'] ?? '');
$acc_status = trim($_POST['acc_status'] ?? 'active');
$source_system = trim($_POST['source_system'] ?? 'Electripid');

if (!$user_id || !$fname || !$lname || !$email || !$city) {
    echo json_encode(['success' => false, 'error' => 'All required fields must be filled']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit;
}

// Validate role
if (!in_array($role, ['user', 'admin'])) {
    $role = 'user';
}

// Validate status
if (!in_array($acc_status, ['active', 'inactive', 'suspended'])) {
    $acc_status = 'active';
}

// Validate source_system
if (!in_array($source_system, ['Electripid', 'Airlyft'])) {
    $source_system = 'Electripid';
}

$user_id_escaped = mysqli_real_escape_string($conn, $user_id);
$fname_escaped = mysqli_real_escape_string($conn, $fname);
$lname_escaped = mysqli_real_escape_string($conn, $lname);
$email_escaped = mysqli_real_escape_string($conn, $email);
$role_escaped = mysqli_real_escape_string($conn, $role);
$city_escaped = mysqli_real_escape_string($conn, $city);
$barangay_escaped = mysqli_real_escape_string($conn, $barangay);
$cp_number_escaped = mysqli_real_escape_string($conn, $cp_number);
$acc_status_escaped = mysqli_real_escape_string($conn, $acc_status);
$source_system_escaped = mysqli_real_escape_string($conn, $source_system);

// Check if email is being changed and if new email already exists
$current_user_query = "SELECT email FROM USER WHERE user_id = '$user_id_escaped'";
$current_user_result = executeQuery($current_user_query);

if ($current_user_result && mysqli_num_rows($current_user_result) > 0) {
    $current_user = mysqli_fetch_assoc($current_user_result);
    $current_email = $current_user['email'];
    
    if ($email !== $current_email) {
        $check_email_query = "SELECT user_id FROM USER WHERE email = '$email_escaped' AND user_id != '$user_id_escaped'";
        $check_email_result = executeQuery($check_email_query);
        
        if ($check_email_result && mysqli_num_rows($check_email_result) > 0) {
            echo json_encode(['success' => false, 'error' => 'Email address is already registered']);
            exit;
        }
    }
}

$update_query = "UPDATE USER SET 
    fname = '$fname_escaped',
    lname = '$lname_escaped',
    email = '$email_escaped',
    role = '$role_escaped',
    city = '$city_escaped',
    barangay = '$barangay_escaped',
    cp_number = '$cp_number_escaped',
    acc_status = '$acc_status_escaped',
    source_system = '$source_system_escaped'
    WHERE user_id = '$user_id_escaped'";

if (executeQuery($update_query)) {
    // User updated successfully, now sync to external database
    $sync_result = ['success' => true, 'message' => 'Sync not attempted'];

    if (isSyncEnabled()) {
        $sync_result = syncUserUpdate($user_id);

        if ($sync_result['success']) {
            $response_message = 'User updated and synced successfully';
        } else {
            $response_message = 'User updated successfully, but sync failed: ' . $sync_result['message'];
            // Still return success since the main operation succeeded
        }
    } else {
        $response_message = 'User updated successfully (sync disabled)';
    }

    echo json_encode([
        'success' => true,
        'message' => $response_message,
        'sync_result' => $sync_result
    ]);
} else {
    $error_message = mysqli_error($conn);
    echo json_encode(['success' => false, 'error' => 'Failed to update user: ' . $error_message]);
}

$conn->close();
?>
