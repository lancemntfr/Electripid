<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_id = mysqli_real_escape_string($conn, $user_id);

// Get count of unread budget notifications
$count_query = "SELECT COUNT(*) as unread_count FROM NOTIFICATION WHERE user_id = '$user_id' AND notification_type = 'budget' AND is_read = FALSE";
$count_result = executeQuery($count_query);

if ($count_result && mysqli_num_rows($count_result) > 0) {
    $count_row = mysqli_fetch_assoc($count_result);
    echo json_encode([
        'success' => true,
        'unread_count' => intval($count_row['unread_count'])
    ]);
} else {
    echo json_encode([
        'success' => true,
        'unread_count' => 0
    ]);
}

$conn->close();
?>