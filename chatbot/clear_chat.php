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

$stmt = $conn->prepare("DELETE FROM CHATBOT WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();

ob_clean();
die(json_encode([
    'success' => true,
    'message' => 'Chat history cleared successfully',
    'deleted_count' => $stmt->affected_rows
]));
?>