<?php
    require_once __DIR__ . '/../../../connect.php';
    require_once __DIR__ . '/send_sms.php';

    function handleSMSCommand($phone, $cmd) {
        global $conn;

        $cmd = trim($cmd);

        if (preg_match('/^[0-9]{6}$/', $cmd)) {
            return; // Don't process OTP codes as commands
        }

        $escaped_phone = mysqli_real_escape_string($conn, $phone);
        $result = executeQuery("SELECT user_id FROM USER WHERE cp_number='$escaped_phone'");

        if ($result->num_rows === 0) {
            sendSMS($phone, "Number not registered.");
            return;
        }

        if (preg_match('/^[0-9]{1,2}$/', $cmd)) {
            switch ($cmd) {
                case '1':
                    sendSMS($phone, "📊 Forecast: Expected usage tomorrow is 3.1 kWh.");
                    return;

                case '2':
                    sendSMS($phone, "💡 Tip: Unplug unused appliances.");
                    return;

                default:
                    sendSMS($phone, "Reply:\n1 - Forecast\n2 - Energy Tips");
                    return;
            }
        }

        sendSMS($phone, "Reply:\n1 - Forecast\n2 - Energy Tips");
    }
?>