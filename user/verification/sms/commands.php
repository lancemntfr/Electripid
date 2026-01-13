<?php
    require_once __DIR__ . '/../../../connect.php';
    require_once __DIR__ . '/send_sms.php';

    function handleSMSCommand($phone, $text) {
        global $conn;

        $cmd = strtolower(trim($text));

        $lockFile = sys_get_temp_dir() . '/sms_' . md5($phone . '|' . $cmd);

        if (file_exists($lockFile) && (time() - filemtime($lockFile)) < 5) {
            return;
        }
        touch($lockFile);

        if (preg_match('/otp/i', $cmd) && preg_match('/[0-9]{6}/', $cmd)) {
            return;
        }

        $escaped_phone = mysqli_real_escape_string($conn, $phone);
        $result = executeQuery("SELECT user_id FROM USER WHERE cp_number='$escaped_phone'");

        if ($result->num_rows === 0) {
            sendSMS($phone, "Number not registered.\nPlease register first.");
            return;
        }

        if ($cmd === '1') {
            sendSMS($phone, "📊 Forecast:\nExpected usage tomorrow is 3.1 kWh.");
            return;
        } elseif ($cmd === '2') {
            sendSMS($phone, "💡 Tip:\nUnplug unused appliances to save energy.");
            return;
        }

        sendSMS($phone, "Reply:\n1 - Forecast\n2 - Energy Tips");
    }
?>