<?php
    require_once __DIR__ . '/../../connect.php';

    header("Content-Type: application/json");

    // Validate API key
    $headers = getallheaders();
    if (!isset($headers['X-API-KEY']) || $headers['X-API-KEY'] !== getenv('GROUP2_API_KEY')) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    // Validate input
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data || !isset($data['email'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid payload']);
        exit;
    }

    // Extract and sanitize data
    $fname  = $data['first_name'] ?? $data['fname'] ?? 'n/a';
    $lname  = $data['last_name']  ?? $data['lname'] ?? 'n/a';
    $email  = $data['email'];
    $phone  = $data['phone'] ?? $data['cp_number'] ?? 'n/a';
    $pass   = $data['password'] ?? 'n/a';
    $source = $data['source_system'] ?? $data['origin_system'] ?? 'Airlyft';

    // Escape all data once
    $fname_escaped = mysqli_real_escape_string($conn, $fname);
    $lname_escaped = mysqli_real_escape_string($conn, $lname);
    $email_escaped = mysqli_real_escape_string($conn, $email);
    $phone_escaped = mysqli_real_escape_string($conn, $phone);
    $pass_escaped = mysqli_real_escape_string($conn, $pass);
    $source_escaped = mysqli_real_escape_string($conn, $source);

    // Check if user exists
    $check_query = "SELECT user_id FROM USER WHERE LOWER(email)=LOWER('$email_escaped')";
    $check_result = executeQuery($check_query);

    if ($check_result && mysqli_num_rows($check_result) > 0) {
        // Update existing user
        $update_query = "UPDATE USER SET fname='$fname_escaped', lname='$lname_escaped', cp_number='$phone_escaped', password='$pass_escaped', source_system='$source_escaped' WHERE email='$email_escaped'";
        $update_result = executeQuery($update_query);

        if ($update_result) {
            echo json_encode(['success' => true, 'message' => 'User updated']);
        } else {
            error_log("Update failed: " . mysqli_error($conn));
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database update failed']);
        }
    } else {
        // Create new user
        $insert_query = "INSERT INTO USER (fname, lname, email, cp_number, password, source_system, role) VALUES ('$fname_escaped', '$lname_escaped', '$email_escaped', '$phone_escaped', '$pass_escaped', '$source_escaped', 'Client')";
        $insert_result = executeQuery($insert_query);

        if ($insert_result) {
            echo json_encode(['success' => true, 'message' => 'User created']);
        } else {
            error_log("Insert failed: " . mysqli_error($conn));
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database insert failed']);
        }
    }
?>"