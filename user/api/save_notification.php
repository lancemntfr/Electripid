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

    $notification_type = $data['notification_type'] ?? 'alert';
    $channel = $data['channel'] ?? 'in-app';
    $related_id = intval($data['related_id'] ?? 0);
    $related_type = $data['related_type'] ?? 'general';
    $title = trim($data['title'] ?? '');
    $message = trim($data['message'] ?? '');

    if (empty($title) || empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Title and message are required']);
        exit;
    }

    if (!in_array($notification_type, ['alert', 'anomaly', 'budget'])) {
        $notification_type = 'alert';
    }

    if (!in_array($channel, ['sms', 'email', 'in-app'])) {
        $channel = 'in-app';
    }

    if (!in_array($related_type, ['anomaly', 'budget', 'donation', 'verification', 'general'])) {
        $related_type = 'general';
    }

    $user_id = mysqli_real_escape_string($conn, $user_id);
    $notification_type = mysqli_real_escape_string($conn, $notification_type);
    $channel = mysqli_real_escape_string($conn, $channel);
    $related_id = mysqli_real_escape_string($conn, $related_id);
    $related_type = mysqli_real_escape_string($conn, $related_type);
    $title = mysqli_real_escape_string($conn, $title);
    $message = mysqli_real_escape_string($conn, $message);

    $insert_query = "INSERT INTO NOTIFICATION (user_id, notification_type, channel, related_id, related_type, title, message, status) VALUES ('$user_id', '$notification_type', '$channel', " . ($related_id > 0 ? "'$related_id'" : "NULL") . ", '$related_type', '$title', '$message', 'sent')";
    $insert_result = executeQuery($insert_query);

    if ($insert_result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Notification saved successfully',
            'notification_id' => mysqli_insert_id($conn)
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save notification']);
    }

    $conn->close();
?>
