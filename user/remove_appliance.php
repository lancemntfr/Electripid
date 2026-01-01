<?php
    session_start();
    header('Content-Type: application/json');

    require_once '../connect.php';

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
        echo json_encode(['success' => false, 'error' => 'Appliance not found or access denied']);
        exit;
    }
    
    $delete_query = "DELETE FROM APPLIANCE WHERE appliance_id = '$appliance_id'";
    $delete_result = executeQuery($delete_query);
    
    if ($delete_result) {
        echo json_encode(['success' => true, 'message' => 'Appliance removed successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to remove appliance']);
    }
    
    $conn->close();
?>