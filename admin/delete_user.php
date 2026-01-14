<?php
session_start();
header('Content-Type: application/json');

require_once 'admin_auth.php';
require_once '../connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user_id = intval($_POST['user_id'] ?? 0);

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'User ID is required']);
    exit;
}

$user_id_escaped = mysqli_real_escape_string($conn, $user_id);

// Check if user exists
$check_query = "SELECT user_id FROM USER WHERE user_id = '$user_id_escaped'";
$check_result = executeQuery($check_query);

if (!$check_result || mysqli_num_rows($check_result) === 0) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

// Delete user (you may want to use soft delete by setting acc_status to 'deleted' instead)
// For now, we'll delete the user record
$delete_query = "DELETE FROM USER WHERE user_id = '$user_id_escaped'";

if (executeQuery($delete_query)) {
    echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete user']);
}

$conn->close();
?>
