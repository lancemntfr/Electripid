<?php
    require_once __DIR__ . '/../../connect.php';

    $api_key_env = getenv('GROUP2_API_KEY'); // make sure this matches Airlyft key

    $headers = getallheaders();
    if (!isset($headers['X-API-KEY']) || $headers['X-API-KEY'] !== $api_key_env) {
        http_response_code(401);
        echo json_encode(['success' => false,'message'=>'Unauthorized']);
        exit;
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
        exit;
    }

    $fname    = $data['first_name'] ?? 'n/a';
    $lname    = $data['last_name'] ?? 'n/a';
    $cp_number= $data['phone'] ?? 'n/a';
    $city     = 'n/a'; 
    $barangay = 'n/a'; 
    $email    = $data['email'] ?? 'n/a';
    $password = $data['password'] ?? 'n/a'; 
    $source   = $data['source_system'] ?? 'Airlyft';

    $stmt_check = $conn->prepare("SELECT user_id, source_system FROM USER WHERE email=?");
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $existing = $result_check->fetch_assoc();

        if ($existing['source_system'] === 'Airlyft') {
            $stmt_update = $conn->prepare("
                UPDATE USER SET fname=?, lname=?, cp_number=?, city=?, barangay=?, password=?, source_system=? WHERE email=?
            ");
            $stmt_update->bind_param("ssssssss", $fname, $lname, $cp_number, $city, $barangay, $password, $source, $email);
            $stmt_update->execute();
            echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'User already exists from Electripid']);
            exit;
        }
    }

    $stmt_insert = $conn->prepare("
        INSERT INTO USER (fname, lname, email, cp_number, city, barangay, password, role, acc_status, source_system) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'user', 'active', ?)
    ");
    $stmt_insert->bind_param("ssssssss", $fname, $lname, $email, $cp_number, $city, $barangay, $password, $source);

    if ($stmt_insert->execute()) {
        echo json_encode(['success' => true, 'message' => 'User synced successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to insert user']);
    }
?>