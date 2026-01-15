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

    // Check if this is a budget-only update
    $monthly_budget = isset($data['monthly_budget']) ? floatval($data['monthly_budget']) : null;
    $is_budget_only = ($monthly_budget !== null && !isset($data['fname']) && !isset($data['email']));

    // Handle budget-only update
    if ($is_budget_only) {
        if ($monthly_budget < 0) {
            response(['success' => false, 'error' => 'Budget amount must be greater than or equal to 0.']);
        }

        $user_id_escaped = mysqli_real_escape_string($conn, $user_id);
        $monthly_budget_escaped = mysqli_real_escape_string($conn, $monthly_budget);

        // Check if household exists
        $check_household_query = "SELECT household_id, provider_id FROM HOUSEHOLD WHERE user_id = '$user_id_escaped'";
        $check_household_result = executeQuery($check_household_query);

        if ($check_household_result && mysqli_num_rows($check_household_result) > 0) {
            // Update existing household
            $household_row = mysqli_fetch_assoc($check_household_result);
            $household_id_escaped = mysqli_real_escape_string($conn, $household_row['household_id']);
            $provider_id = intval($household_row['provider_id']);
            
            // If provider_id is 0 or null, we need to set a default or require it
            if ($provider_id <= 0) {
                // Get the first available provider as default
                $default_provider_query = "SELECT provider_id FROM ELECTRICITY_PROVIDER ORDER BY provider_id LIMIT 1";
                $default_provider_result = executeQuery($default_provider_query);
                if ($default_provider_result && mysqli_num_rows($default_provider_result) > 0) {
                    $default_provider = mysqli_fetch_assoc($default_provider_result);
                    $provider_id = intval($default_provider['provider_id']);
                    $provider_id_escaped = mysqli_real_escape_string($conn, $provider_id);
                    $update_household_query = "UPDATE HOUSEHOLD SET monthly_budget = '$monthly_budget_escaped', provider_id = '$provider_id_escaped' WHERE household_id = '$household_id_escaped'";
                } else {
                    $update_household_query = "UPDATE HOUSEHOLD SET monthly_budget = '$monthly_budget_escaped' WHERE household_id = '$household_id_escaped'";
                }
            } else {
                $update_household_query = "UPDATE HOUSEHOLD SET monthly_budget = '$monthly_budget_escaped' WHERE household_id = '$household_id_escaped'";
            }
            
            $update_household_result = executeQuery($update_household_query);
            if (!$update_household_result) {
                response(['success' => false, 'error' => 'Failed to update monthly budget.']);
            }
        } else {
            // Create new household with budget
            // Get default provider if none exists
            $default_provider_query = "SELECT provider_id FROM ELECTRICITY_PROVIDER ORDER BY provider_id LIMIT 1";
            $default_provider_result = executeQuery($default_provider_query);
            
            if ($default_provider_result && mysqli_num_rows($default_provider_result) > 0) {
                $default_provider = mysqli_fetch_assoc($default_provider_result);
                $provider_id = intval($default_provider['provider_id']);
                $provider_id_escaped = mysqli_real_escape_string($conn, $provider_id);
                $insert_household_query = "INSERT INTO HOUSEHOLD (user_id, provider_id, monthly_budget) VALUES ('$user_id_escaped', '$provider_id_escaped', '$monthly_budget_escaped')";
            } else {
                // If no provider exists, insert without provider_id (but this shouldn't happen based on schema)
                response(['success' => false, 'error' => 'No electricity provider found. Please set a provider first.']);
            }
            
            $insert_household_result = executeQuery($insert_household_query);
            if (!$insert_household_result) {
                response(['success' => false, 'error' => 'Failed to create household with budget.']);
            }
        }

        response(['success' => true, 'message' => 'Monthly budget updated successfully']);
    }

    // Continue with profile update logic
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
    $household_updated = false;
    if ($provider_id > 0 || $monthly_budget !== null) {
        $check_household_query = "SELECT household_id, provider_id, monthly_budget FROM HOUSEHOLD WHERE user_id = '$user_id_escaped'";
        $check_household_result = executeQuery($check_household_query);

        if ($check_household_result && mysqli_num_rows($check_household_result) > 0) {
            // Update existing household
            $household_row = mysqli_fetch_assoc($check_household_result);
            $household_id_escaped = mysqli_real_escape_string($conn, $household_row['household_id']);
            $current_provider_id = intval($household_row['provider_id'] ?? 0);
            
            // Build update query
            $update_fields = [];
            
            if ($provider_id > 0) {
                $provider_id_escaped = mysqli_real_escape_string($conn, $provider_id);
                $update_fields[] = "provider_id = '$provider_id_escaped'";
            }
            
            if ($monthly_budget !== null && $monthly_budget >= 0) {
                $monthly_budget_escaped = mysqli_real_escape_string($conn, $monthly_budget);
                $update_fields[] = "monthly_budget = '$monthly_budget_escaped'";
            }
            
            if (!empty($update_fields)) {
                $update_household_query = "UPDATE HOUSEHOLD SET " . implode(', ', $update_fields) . " WHERE household_id = '$household_id_escaped'";
                $update_household_result = executeQuery($update_household_query);
                if (!$update_household_result) {
                    response(['success' => false, 'error' => 'Failed to update household settings.']);
                }
                $household_updated = true;
            }
        } else {
            // Create new household
            $insert_fields = ['user_id'];
            $insert_values = ["'$user_id_escaped'"];
            
            if ($provider_id > 0) {
                $provider_id_escaped = mysqli_real_escape_string($conn, $provider_id);
                $insert_fields[] = 'provider_id';
                $insert_values[] = "'$provider_id_escaped'";
            } else {
                // Get default provider if none provided
                $default_provider_query = "SELECT provider_id FROM ELECTRICITY_PROVIDER ORDER BY provider_id LIMIT 1";
                $default_provider_result = executeQuery($default_provider_query);
                if ($default_provider_result && mysqli_num_rows($default_provider_result) > 0) {
                    $default_provider = mysqli_fetch_assoc($default_provider_result);
                    $default_provider_id = intval($default_provider['provider_id']);
                    $default_provider_id_escaped = mysqli_real_escape_string($conn, $default_provider_id);
                    $insert_fields[] = 'provider_id';
                    $insert_values[] = "'$default_provider_id_escaped'";
                }
            }
            
            if ($monthly_budget !== null && $monthly_budget >= 0) {
                $monthly_budget_escaped = mysqli_real_escape_string($conn, $monthly_budget);
                $insert_fields[] = 'monthly_budget';
                $insert_values[] = "'$monthly_budget_escaped'";
            }
            
            $insert_household_query = "INSERT INTO HOUSEHOLD (" . implode(', ', $insert_fields) . ") VALUES (" . implode(', ', $insert_values) . ")";
            $insert_household_result = executeQuery($insert_household_query);
            if (!$insert_household_result) {
                response(['success' => false, 'error' => 'Failed to create household settings.']);
            }
            $household_updated = true;
        }
    }

    // Update session
    $_SESSION['fname'] = $fname;
    $_SESSION['lname'] = $lname;
    $_SESSION['email'] = $email;
    $_SESSION['cp_number'] = $cp_number;

    response(['success' => true, 'message' => 'Profile updated successfully']);
?>