<?php
function sendSMS($phone, $message) {

    $gateway_url = getenv('LOCAL_ADDRESS');
    $username    = getenv('SMS_USERNAME');
    $password    = getenv('SMS_PASSWORD');

    if (!$gateway_url || !$username || !$password) {
        error_log("SMSGate config missing");
        return false;
    }

    $url = rtrim($gateway_url, '/') . '/messages';

    $payload = [
        'phoneNumbers' => [$phone],
        'message'      => $message
    ];

    $headers = [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode("$username:$password")
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);

    curl_close($ch);

    error_log("SMSGate HTTP: $httpCode");
    error_log("SMSGate Response: " . ($response ?: 'NO RESPONSE'));

    if ($error) {
        error_log("CURL error: $error");
        return false;
    }

    return ($httpCode >= 200 && $httpCode < 300);
}
?>