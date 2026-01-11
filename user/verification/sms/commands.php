<?php
require_once __DIR__ . '/send_sms.php';
require_once __DIR__ . '/../connect.php';

function handleSMSCommand($phone, $cmd) {
    global $conn;

    // Only verified users
    $stmt = $conn->prepare("SELECT user_id FROM USER WHERE cp_number=?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();

    if ($stmt->get_result()->num_rows === 0) {
        sendSMS($phone, "Number not registered.");
        return;
    }

    switch ($cmd) {
        case '1':
            sendSMS($phone, "📊 Forecast: Expected usage tomorrow is 3.1 kWh.");
            break;

        case '2':
            sendSMS($phone, "💡 Tip: Unplug unused appliances.");
            break;

        default:
            sendSMS($phone, "Reply:\n1 - Forecast\n2 - Energy Tips");
    }
}
