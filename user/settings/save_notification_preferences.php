<?php
    session_start();
    header('Content-Type: application/json');

    require_once __DIR__ . '/../../connect.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
        exit;
    }

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
        exit;
    }

    $notify_email = isset($data['notify_email']) ? intval($data['notify_email']) : 1;
    $notify_sms = isset($data['notify_sms']) ? intval($data['notify_sms']) : 1;

    $user_id_escaped = mysqli_real_escape_string($conn, $user_id);

    // Check if columns exist, if not add them
    $check_columns_query = "SHOW COLUMNS FROM USER LIKE 'notify_email'";
    $email_col_result = executeQuery($check_columns_query);
    
    if (!$email_col_result || mysqli_num_rows($email_col_result) === 0) {
        // Add notify_email column
        $add_email_col = "ALTER TABLE USER ADD COLUMN notify_email TINYINT(1) DEFAULT 1";
        executeQuery($add_email_col);
    }

    $check_sms_query = "SHOW COLUMNS FROM USER LIKE 'notify_sms'";
    $sms_col_result = executeQuery($check_sms_query);
    
    if (!$sms_col_result || mysqli_num_rows($sms_col_result) === 0) {
        // Add notify_sms column
        $add_sms_col = "ALTER TABLE USER ADD COLUMN notify_sms TINYINT(1) DEFAULT 1";
        executeQuery($add_sms_col);
    }

    // Update notification preferences
    $notify_email_escaped = mysqli_real_escape_string($conn, $notify_email);
    $notify_sms_escaped = mysqli_real_escape_string($conn, $notify_sms);

    $update_query = "UPDATE USER SET notify_email = '$notify_email_escaped', notify_sms = '$notify_sms_escaped' WHERE user_id = '$user_id_escaped'";
    $update_result = executeQuery($update_query);

    if ($update_result) {
        echo json_encode(['success' => true, 'message' => 'Notification preferences updated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update notification preferences']);
    }

    $conn->close();
?>
