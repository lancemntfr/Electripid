<?php
require_once __DIR__ . '/../verification/sms/send_sms.php';

function sendBudgetSMSAlert($phone, $name, $alert_type, $exceeded_amount, $percentage, $monthly_budget) {
    $exceeded_formatted = number_format($exceeded_amount, 2);
    $percentage_formatted = number_format($percentage, 1);

    if ($alert_type === 'warning') {
        $message = "Electripid Budget Warning: Your consumption exceeded budget by ₱$exceeded_formatted ($percentage_formatted%). Consider reducing usage. Login to dashboard for details.";
    } else {
        $message = "Electripid Budget Alert: URGENT! Consumption exceeded budget by ₱$exceeded_formatted ($percentage_formatted%). Reduce usage immediately or adjust budget in Settings.";
    }

    return sendSMS($phone, $message);
}

function sendBudgetEmailAlert($email, $name, $alert_type, $exceeded_amount, $percentage, $monthly_budget) {
    // This function is now handled in send_email_alert.php
    // Keeping this as a wrapper for backward compatibility
    require_once __DIR__ . '/send_email_alert.php';
    return sendBudgetEmailAlert($email, $name, $alert_type, $exceeded_amount, $percentage, $monthly_budget);
}
?>