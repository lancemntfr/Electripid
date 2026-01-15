<?php
    session_start();
    header('Content-Type: application/json');

    require_once __DIR__ . '/../../connect.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
        exit;
    }

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $user_id = mysqli_real_escape_string($conn, $user_id);

    // Check if there's an unread budget notification
    $check_query = "SELECT notification_id, title, message FROM NOTIFICATION WHERE user_id = '$user_id' AND notification_type = 'budget' AND is_read = FALSE ORDER BY sent_at DESC LIMIT 1";
    $check_result = executeQuery($check_query);

    if ($check_result && mysqli_num_rows($check_result) > 0) {
        echo json_encode([
            'success' => true,
            'has_unread' => true,
            'notification' => mysqli_fetch_assoc($check_result)
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'has_unread' => false
        ]);
    }

    $conn->close();
?>
