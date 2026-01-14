<?php
    require_once __DIR__ . '/../../connect.php';

    $logDir = realpath(__DIR__ . '/../../../') . '/log';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }

    $debugLog = $logDir . '/sync_debug.log';
    $errorLog = $logDir . '/sync_errors.log';

    function syncUserToAirlyft($user_id) {
        global $conn, $debugLog, $errorLog;

        $user_id = mysqli_real_escape_string($conn, $user_id);

        $q = "SELECT fname, lname, email, cp_number, city, barangay, password FROM USER WHERE user_id = '$user_id'";
        $res = executeQuery($q);
        if (!$res || mysqli_num_rows($res) === 0) return;

        $u = mysqli_fetch_assoc($res);

        $payload = [
            "external_id"   => "G1-$user_id",
            "first_name"    => $u['fname'],
            "last_name"     => $u['lname'],
            "name"          => $u['fname']." ".$u['lname'],
            "email"         => $u['email'],
            "phone"         => $u['cp_number'] ?: "n/a",
            "password"      => $u['password'],
            "source_system" => "Electripid"
        ];

        $api_key = getenv('GROUP2_API_KEY');

        $ch = curl_init("http://192.168.18.136/airlyft/api/sync_user.php");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "X-API-KEY: $api_key"
            ],
            CURLOPT_POSTFIELDS => json_encode($payload)
        ]);

        $response = curl_exec($ch);

        file_put_contents(
            $debugLog,
            date('Y-m-d H:i:s') . " - Sync update for {$u['email']} → $response\n",
            FILE_APPEND
        );

        if ($response === false) {
            file_put_contents(
                $errorLog,
                date('Y-m-d H:i:s') . " - User {$payload['external_id']} failed sync\n",
                FILE_APPEND
            );
        } else {
            file_put_contents(
                $debugLog,
                date('Y-m-d H:i:s') . " - Response from Airlyft: $response\n",
                FILE_APPEND
            );
        }

        curl_close($ch);
    }
?>