<?php
    // Prevent any output before JSON
    ob_start();
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

    $appliance_id = intval($data['appliance_id'] ?? 0);

    if ($appliance_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid appliance ID']);
        exit;
    }

    $appliance_id = mysqli_real_escape_string($conn, $appliance_id);
    $user_id = mysqli_real_escape_string($conn, $user_id);
    
    $verify_query = "SELECT a.appliance_id FROM APPLIANCE a INNER JOIN HOUSEHOLD h ON a.household_id = h.household_id WHERE a.appliance_id = '$appliance_id' AND h.user_id = '$user_id'";
    $verify_result = executeQuery($verify_query);
    
    if (!$verify_result || mysqli_num_rows($verify_result) === 0) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Appliance not found or access denied']);
        exit;
    }
    
    // Get appliance name before deletion
    $appliance_query = "SELECT appliance_name FROM APPLIANCE WHERE appliance_id = '$appliance_id'";
    $appliance_result = executeQuery($appliance_query);
    $appliance_name = 'appliance';
    if ($appliance_result && mysqli_num_rows($appliance_result) > 0) {
        $appliance_row = mysqli_fetch_assoc($appliance_result);
        $appliance_name = $appliance_row['appliance_name'];
    }
    
    $delete_query = "DELETE FROM APPLIANCE WHERE appliance_id = '$appliance_id'";
    $delete_result = executeQuery($delete_query);
    
    if ($delete_result) {
        // Create notification for appliance removed
        $appliance_name = mysqli_real_escape_string($conn, $appliance_name);
        $notif_title = "Appliance Removed";
        $notif_message = "You have successfully removed '" . $appliance_name . "' from your appliances list.";
        $notif_query = "INSERT INTO NOTIFICATION (user_id, notification_type, channel, related_id, related_type, title, message, status) VALUES ('$user_id', 'alert', 'in-app', NULL, 'general', '$notif_title', '$notif_message', 'sent')";
        executeQuery($notif_query);
        
        ob_clean();
        echo json_encode(['success' => true, 'message' => 'Appliance removed successfully']);
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Failed to remove appliance. Please try again.']);
    }
    
    $conn->close();
    exit;
?>
