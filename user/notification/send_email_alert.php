<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../phpmailer/src/Exception.php';
require_once __DIR__ . '/../../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../../phpmailer/src/SMTP.php';
require_once __DIR__ . '/../verification/sms/send_sms.php';

function sendBudgetAlert($user_id, $alert_type, $budget_data) {
    global $conn;

    // Get user details
    $user_query = "SELECT fname, lname, email, cp_number FROM USER WHERE user_id = ?";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();

    if ($user_result->num_rows === 0) {
        error_log("User not found for budget alert: $user_id");
        return false;
    }

    $user = $user_result->fetch_assoc();
    $user_name = trim($user['fname'] . ' ' . $user['lname']);
    $user_email = $user['email'];
    $user_phone = $user['cp_number'];

    // Get notification preferences
    $prefs_query = "SELECT email_alerts, sms_alerts FROM NOTIFICATION_PREFERENCES WHERE user_id = ?";
    $stmt = $conn->prepare($prefs_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $prefs_result = $stmt->get_result();

    $email_enabled = true; // Default to enabled
    $sms_enabled = true;   // Default to enabled

    if ($prefs_result && $prefs_result->num_rows > 0) {
        $prefs = $prefs_result->fetch_assoc();
        $email_enabled = $prefs['email_alerts'] ?? true;
        $sms_enabled = $prefs['sms_alerts'] ?? true;
    } elseif (!$prefs_result) {
        // Table might not exist yet, use defaults
        error_log("NOTIFICATION_PREFERENCES table not found, using default settings");
    }

    $budget_exceeded = $budget_data['exceeded_amount'];
    $budget_percentage = $budget_data['percentage'];
    $monthly_budget = $budget_data['monthly_budget'];

    $success = true;

    // Send email alert if enabled
    if ($email_enabled && !empty($user_email)) {
        $email_sent = sendBudgetEmailAlert($user_email, $user_name, $alert_type, $budget_exceeded, $budget_percentage, $monthly_budget);
        if (!$email_sent) {
            error_log("Failed to send email alert to user $user_id");
            $success = false;
        }
    }

    // Send SMS alert if enabled
    if ($sms_enabled && !empty($user_phone)) {
        $sms_sent = sendBudgetSMSAlert($user_phone, $user_name, $alert_type, $budget_exceeded, $budget_percentage, $monthly_budget);
        if (!$sms_sent) {
            error_log("Failed to send SMS alert to user $user_id");
            $success = false;
        }
    }

    return $success;
}

function sendBudgetEmailAlert($email, $name, $alert_type, $exceeded_amount, $percentage, $monthly_budget) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username = getenv('EMAIL_USER');
        $mail->Password = getenv('EMAIL_APP_PASSWORD');
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom(getenv('EMAIL_USER'), 'Electripid');
        $mail->addAddress($email);

        $mail->isHTML(true);

        if ($alert_type === 'warning') {
            $mail->Subject = 'Electripid Budget Warning';
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #ff9800;'>⚠️ Budget Warning</h2>
                    <p>Dear $name,</p>
                    <p>Your electricity consumption has exceeded your monthly budget:</p>
                    <ul>
                        <li><strong>Monthly Budget:</strong> ₱" . number_format($monthly_budget, 2) . "</li>
                        <li><strong>Exceeded by:</strong> ₱" . number_format($exceeded_amount, 2) . "</li>
                        <li><strong>Percentage over budget:</strong> " . number_format($percentage, 1) . "%</li>
                    </ul>
                    <p><strong>Recommendation:</strong> Consider reducing appliance usage or adjusting your budget in Settings to avoid unexpected costs.</p>
                    <p>Log in to your Electripid dashboard to monitor your usage.</p>
                    <br>
                    <p>Best regards,<br>Electripid Team</p>
                </div>
            ";
        } else {
            $mail->Subject = 'Electripid Budget Alert - Action Required';
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #f44336;'>🚨 Budget Alert</h2>
                    <p>Dear $name,</p>
                    <p><strong>URGENT:</strong> Your electricity consumption has significantly exceeded your monthly budget:</p>
                    <ul>
                        <li><strong>Monthly Budget:</strong> ₱" . number_format($monthly_budget, 2) . "</li>
                        <li><strong>Exceeded by:</strong> ₱" . number_format($exceeded_amount, 2) . "</li>
                        <li><strong>Percentage over budget:</strong> " . number_format($percentage, 1) . "%</li>
                    </ul>
                    <p><strong>Action Required:</strong> Please reduce appliance usage immediately or increase your budget in Settings to avoid unexpected costs.</p>
                    <p>Log in to your Electripid dashboard to take action now.</p>
                    <br>
                    <p>Best regards,<br>Electripid Team</p>
                </div>
            ";
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Budget email alert could not be sent: {$mail->ErrorInfo}");
        return false;
    }
}

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
?>