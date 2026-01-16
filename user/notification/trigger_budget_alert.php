<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../connect.php';
require_once __DIR__ . '/send_email_alert.php';

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

$alert_type = $data['alert_type'] ?? 'warning';
$budget_data = $data['budget_data'] ?? [];

if (empty($budget_data)) {
    echo json_encode(['success' => false, 'error' => 'Budget data is required']);
    exit;
}

// Trigger the budget alert (email and SMS)
$alert_sent = sendBudgetAlert($user_id, $alert_type, $budget_data);

if ($alert_sent) {
    echo json_encode([
        'success' => true,
        'message' => 'Budget alerts sent successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to send some or all budget alerts'
    ]);
}

$conn->close();
?>