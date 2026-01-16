<?php
session_start();
require_once __DIR__ . '/../../connect.php';
require_once __DIR__ . '/send_email_alert.php';

echo "<h1>Notification System Test</h1>";

$user_id = 1;

$budget_data = [
    'monthly_budget' => 500.00,
    'current_cost' => 650.00,
    'exceeded_amount' => 150.00,
    'percentage' => 130.0
];

echo "<h2>Testing Budget Alert System</h2>";
echo "<p>Testing with user ID: $user_id</p>";
echo "<p>Budget Data: " . json_encode($budget_data) . "</p>";

// Test warning alert
echo "<h3>Testing Warning Alert</h3>";
$warning_result = sendBudgetAlert($user_id, 'warning', $budget_data);
echo "<p>Warning alert result: " . ($warning_result ? 'SUCCESS' : 'FAILED') . "</p>";

// Test critical alert
echo "<h3>Testing Critical Alert</h3>";
$alert_result = sendBudgetAlert($user_id, 'alert', $budget_data);
echo "<p>Critical alert result: " . ($alert_result ? 'SUCCESS' : 'FAILED') . "</p>";

echo "<h2>Testing Notification Preferences</h2>";

// Check notification preferences
$prefs_query = "SELECT * FROM NOTIFICATION_PREFERENCES WHERE user_id = ?";
$stmt = $conn->prepare($prefs_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$prefs_result = $stmt->get_result();

if ($prefs_result->num_rows > 0) {
    $prefs = $prefs_result->fetch_assoc();
    echo "<p>Current preferences: Email=" . ($prefs['email_alerts'] ? 'ENABLED' : 'DISABLED') .
         ", SMS=" . ($prefs['sms_alerts'] ? 'ENABLED' : 'DISABLED') . "</p>";
} else {
    echo "<p>No preferences found for user $user_id</p>";
}

echo "<h2>Test Complete</h2>";
echo "<p>Check your email and phone for notifications (if configured).</p>";
?>