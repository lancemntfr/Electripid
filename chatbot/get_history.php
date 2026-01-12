<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
session_start();

require_once '../connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    ob_clean();
    die(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

$user_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT sender, message_text, timestamp FROM CHATBOT WHERE user_id = ? ORDER BY timestamp ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'sender' => $row['sender'],
        'message' => $row['message_text'],  // Changed to match dashboard.php expectation
        'timestamp' => $row['timestamp']
    ];
}

ob_clean();
die(json_encode([
    'success' => true,
    'messages' => $messages,
    'count' => count($messages)
]));
?>