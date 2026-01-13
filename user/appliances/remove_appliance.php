<?php
    session_start();
    header('Content-Type: application/json');
    require_once __DIR__ . '/../../connect.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);
    $appliance_id = intval($data['appliance_id'] ?? 0);

    if ($appliance_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid appliance ID']);
        exit;
    }

    // Verify ownership and delete
    $appliance_id = mysqli_real_escape_string($conn, $appliance_id);
    $user_id = mysqli_real_escape_string($conn, $user_id);

    $delete_query = "DELETE a FROM APPLIANCE a INNER JOIN HOUSEHOLD h ON a.household_id = h.household_id WHERE a.appliance_id = '$appliance_id' AND h.user_id = '$user_id'";

    if (executeQuery($delete_query)) {
        echo json_encode(['success' => true, 'message' => 'Appliance removed successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to remove appliance']);
    }

    $conn->close();
?>
