<?php
    session_start();
    header('Content-Type: application/json');

    require_once __DIR__ . '/../../connect.php';
    require_once __DIR__ . '/../includes/validation.php';
    require_once __DIR__ . '/../api/update_sync_user.php'; // syncUserToAirlyft()

    function response($data) {
        echo json_encode($data);
        exit;
    }

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

    $fname = trim($data['fname'] ?? '');
    $lname = trim($data['lname'] ?? '');
    $email = trim($data['email'] ?? '');
    $cp_number = trim($data['cp_number'] ?? '');
    $city = trim($data['city'] ?? '');
    $barangay = trim($data['barangay'] ?? '');
    $provider_id = intval($data['provider_id'] ?? 0);
    $password = $data['password'] ?? '';
    $confirm_password = $data['confirm_password'] ?? '';

    if (empty($fname) || empty($lname) || empty($email) || empty($city) || empty($barangay)) {
        response(['success' => false, 'error' => 'Please fill in all required fields.']);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        response(['success' => false, 'error' => 'Please enter a valid email address.']);
    }

    if (!empty($cp_number) && !preg_match('/^[0-9]{7,15}$/', $cp_number)) {
        response(['success' => false, 'error' => 'Please enter a valid phone number.']);
    }

    $user_id_escaped = mysqli_real_escape_string($conn, $user_id);
    $email_escaped = mysqli_real_escape_string($conn, $email);

    $current_user_query = "SELECT email FROM USER WHERE user_id = '$user_id_escaped'";
    $current_user_result = executeQuery($current_user_query);

    if (!$current_user_result || mysqli_num_rows($current_user_result) === 0) {
        response(['success' => false, 'error' => 'User not found.']);
    }

    $current_user = mysqli_fetch_assoc($current_user_result);
    $current_email = $current_user['email'];

    if ($email !== $current_email) {
        $check_email_query = "SELECT user_id FROM USER WHERE email = '$email_escaped' AND user_id != '$user_id_escaped'";
        $check_email_result = executeQuery($check_email_query);
        if ($check_email_result && mysqli_num_rows($check_email_result) > 0) {
            response(['success' => false, 'error' => 'Email address is already registered.']);
        }
    }

    // Validate provider if provided
    if ($provider_id > 0) {
        $provider_id_escaped = mysqli_real_escape_string($conn, $provider_id);
        $check_provider_query = "SELECT provider_id FROM ELECTRICITY_PROVIDER WHERE provider_id = '$provider_id_escaped'";
        $check_provider_result = executeQuery($check_provider_query);
        if (!$check_provider_result || mysqli_num_rows($check_provider_result) === 0) {
            response(['success' => false, 'error' => 'Invalid electricity provider.']);
        }
    }

    // Validate password if provided
    if (!empty($password)) {
        $passwordValidation = validatePassword($password, $confirm_password);
        if (!$passwordValidation['valid']) {
            response(['success' => false, 'error' => $passwordValidation['error']]);
        }
    }

    // Update user profile
    $fname_escaped = mysqli_real_escape_string($conn, $fname);
    $lname_escaped = mysqli_real_escape_string($conn, $lname);
    $email_escaped = mysqli_real_escape_string($conn, $email);
    $cp_number_escaped = mysqli_real_escape_string($conn, $cp_number);
    $city_escaped = mysqli_real_escape_string($conn, $city);
    $barangay_escaped = mysqli_real_escape_string($conn, $barangay);

    $update_user_query = "UPDATE USER SET fname = '$fname_escaped', lname = '$lname_escaped', email = '$email_escaped', cp_number = '$cp_number_escaped', city = '$city_escaped', barangay = '$barangay_escaped'";

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $hashed_password_escaped = mysqli_real_escape_string($conn, $hashed_password);
        $update_user_query .= ", password = '$hashed_password_escaped'";
    }

    $update_user_query .= " WHERE user_id = '$user_id_escaped'";
    $update_user_result = executeQuery($update_user_query);

    if (!$update_user_result) {
        response(['success' => false, 'error' => 'Failed to update profile.']);
    }

    // Sync to Airlyft
    syncUserToAirlyft($user_id);

    // Update or create household
    if ($provider_id > 0) {
        $provider_id_escaped = mysqli_real_escape_string($conn, $provider_id);
        $check_household_query = "SELECT household_id FROM HOUSEHOLD WHERE user_id = '$user_id_escaped'";
        $check_household_result = executeQuery($check_household_query);

        if ($check_household_result && mysqli_num_rows($check_household_result) > 0) {
            $household_row = mysqli_fetch_assoc($check_household_result);
            $household_id_escaped = mysqli_real_escape_string($conn, $household_row['household_id']);
            $update_household_query = "UPDATE HOUSEHOLD SET provider_id = '$provider_id_escaped' WHERE household_id = '$household_id_escaped'";
            $update_household_result = executeQuery($update_household_query);
            if (!$update_household_result) {
                response(['success' => false, 'error' => 'Failed to update household settings.']);
            }
        } else {
            $insert_household_query = "INSERT INTO HOUSEHOLD (user_id, provider_id) VALUES ('$user_id_escaped', '$provider_id_escaped')";
            $insert_household_result = executeQuery($insert_household_query);
            if (!$insert_household_result) {
                response(['success' => false, 'error' => 'Failed to create household settings.']);
            }
        }
    }

    // Update session
    $_SESSION['fname'] = $fname;
    $_SESSION['lname'] = $lname;
    $_SESSION['email'] = $email;
    $_SESSION['cp_number'] = $cp_number;

    response(['success' => true, 'message' => 'Profile updated successfully']);
?>