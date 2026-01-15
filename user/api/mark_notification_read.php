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

    $notification_type = $data['notification_type'] ?? 'budget';

    if (!in_array($notification_type, ['alert', 'anomaly', 'budget'])) {
        $notification_type = 'budget';
    }

    $user_id = mysqli_real_escape_string($conn, $user_id);
    $notification_type = mysqli_real_escape_string($conn, $notification_type);

    // Mark all unread budget notifications as read
    $update_query = "UPDATE NOTIFICATION SET is_read = TRUE WHERE user_id = '$user_id' AND notification_type = '$notification_type' AND is_read = FALSE";
    $update_result = executeQuery($update_query);

    if ($update_result) {
        echo json_encode([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to mark notification as read']);
    }

    $conn->close();
?>
