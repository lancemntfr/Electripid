<?php
session_start();
header('Content-Type: application/json');

require_once 'admin_auth.php';
require_once '../connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user_id = intval($_POST['user_id'] ?? 0);
$fname = trim($_POST['fname'] ?? '');
$lname = trim($_POST['lname'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = trim($_POST['role'] ?? 'user');
$city = trim($_POST['city'] ?? '');
$cp_number = trim($_POST['cp_number'] ?? '');
$acc_status = trim($_POST['acc_status'] ?? 'active');

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

$user_id_escaped = mysqli_real_escape_string($conn, $user_id);
$fname_escaped = mysqli_real_escape_string($conn, $fname);
$lname_escaped = mysqli_real_escape_string($conn, $lname);
$email_escaped = mysqli_real_escape_string($conn, $email);
$role_escaped = mysqli_real_escape_string($conn, $role);
$city_escaped = mysqli_real_escape_string($conn, $city);
$cp_number_escaped = mysqli_real_escape_string($conn, $cp_number);
$acc_status_escaped = mysqli_real_escape_string($conn, $acc_status);

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
    cp_number = '$cp_number_escaped',
    acc_status = '$acc_status_escaped'
    WHERE user_id = '$user_id_escaped'";

if (executeQuery($update_query)) {
    echo json_encode(['success' => true, 'message' => 'User updated successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update user']);
}

$conn->close();
?>
