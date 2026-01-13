<?php
    session_start();
    header('Content-Type: application/json');

    require_once __DIR__ . '/../../connect.php';
    require_once __DIR__ . '/../includes/validation.php';

    // Validate request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        response(['success' => false, 'error' => 'Invalid request method']);
    }

    if (!isset($_SESSION['user_id'])) {
        response(['success' => false, 'error' => 'Not authenticated']);
    }

    $user_id = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        response(['success' => false, 'error' => 'Invalid JSON data']);
    }

    function response($data) {
        echo json_encode($data);
        exit;
    }

    $fname = trim($data['fname'] ?? '');
    $lname = trim($data['lname'] ?? '');
    $email = trim($data['email'] ?? '');
    $city = trim($data['city'] ?? '');
    $barangay = trim($data['barangay'] ?? '');
    $provider_id = intval($data['provider_id'] ?? 0);
    $password = $data['password'] ?? '';
    $confirm_password = $data['confirm_password'] ?? '';

    // Validate required fields
    if (empty($fname) || empty($lname) || empty($email) || empty($city) || empty($barangay)) {
        response(['success' => false, 'error' => 'Please fill in all required fields.']);
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        response(['success' => false, 'error' => 'Please enter a valid email address.']);
    }

    // Check if email is being changed and if new email already exists
    $user_id_escaped = mysqli_real_escape_string($conn, $user_id);
    $current_user_query = "SELECT email FROM USER WHERE user_id = '$user_id_escaped'";
    $current_user_result = executeQuery($current_user_query);
    
    if (!$current_user_result || mysqli_num_rows($current_user_result) === 0) {
        response(['success' => false, 'error' => 'User not found.']);
    }
    
    $current_user = mysqli_fetch_assoc($current_user_result);
    $current_email = $current_user['email'];
    
    if ($email !== $current_email) {
        $email_escaped = mysqli_real_escape_string($conn, $email);
        $check_email_query = "SELECT user_id FROM USER WHERE email = '$email_escaped' AND user_id != '$user_id_escaped'";
        $check_email_result = executeQuery($check_email_query);
        
        if ($check_email_result && mysqli_num_rows($check_email_result) > 0) {
            response(['success' => false, 'error' => 'Email address is already registered.']);
            exit;
        }
    }

    // Validate provider
    if ($provider_id <= 0) {
        response(['success' => false, 'error' => 'Please select an electricity provider.']);
        exit;
    }

    $provider_id_escaped = mysqli_real_escape_string($conn, $provider_id);
    $check_provider_query = "SELECT provider_id FROM ELECTRICITY_PROVIDER WHERE provider_id = '$provider_id_escaped'";
    $check_provider_result = executeQuery($check_provider_query);
    
    if (!$check_provider_result || mysqli_num_rows($check_provider_result) === 0) {
        response(['success' => false, 'error' => 'Invalid electricity provider.']);
        exit;
    }

    // Validate password if provided
    if (!empty($password)) {
        $passwordValidation = validatePassword($password, $confirm_password);
        if (!$passwordValidation['valid']) {
            response(['success' => false, 'error' => $passwordValidation['error']]);
            exit;
        }
    }

    // Escape data
    $fname = mysqli_real_escape_string($conn, $fname);
    $lname = mysqli_real_escape_string($conn, $lname);
    $email = mysqli_real_escape_string($conn, $email);
    $city = mysqli_real_escape_string($conn, $city);
    $barangay = mysqli_real_escape_string($conn, $barangay);

    // Update USER table
    $update_user_query = "UPDATE USER SET fname = '$fname', lname = '$lname', email = '$email', city = '$city', barangay = '$barangay'";
    
    // Update password if provided
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $hashed_password = mysqli_real_escape_string($conn, $hashed_password);
        $update_user_query .= ", password = '$hashed_password'";
    }
    
    $update_user_query .= " WHERE user_id = '$user_id_escaped'";
    
    $update_user_result = executeQuery($update_user_query);
    
    if (!$update_user_result) {
        response(['success' => false, 'error' => 'Failed to update profile.']);
        exit;
    }

    // Update HOUSEHOLD table
    $check_household_query = "SELECT household_id FROM HOUSEHOLD WHERE user_id = '$user_id_escaped'";
    $check_household_result = executeQuery($check_household_query);
    
    if ($check_household_result && mysqli_num_rows($check_household_result) > 0) {
        $household_row = mysqli_fetch_assoc($check_household_result);
        $household_id = mysqli_real_escape_string($conn, $household_row['household_id']);
        
        $update_household_query = "UPDATE HOUSEHOLD SET provider_id = '$provider_id_escaped' WHERE household_id = '$household_id'";
        $update_household_result = executeQuery($update_household_query);
        
        if (!$update_household_result) {
            response(['success' => false, 'error' => 'Failed to update household settings.']);
            exit;
        }
    } else {
        // Create household if it doesn't exist
        $insert_household_query = "INSERT INTO HOUSEHOLD (user_id, provider_id) VALUES ('$user_id_escaped', '$provider_id_escaped')";
        $insert_household_result = executeQuery($insert_household_query);
        
        if (!$insert_household_result) {
            response(['success' => false, 'error' => 'Failed to create household settings.']);
            exit;
        }
    }

    // Update session data
    $_SESSION['fname'] = $fname;
    $_SESSION['lname'] = $lname;
    $_SESSION['email'] = $email;

    response(['success' => true, 'message' => 'Profile updated successfully']);
    
    $conn->close();
?>
