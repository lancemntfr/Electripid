<?php
function sendSMS($phone, $message) {
    // SMSgate API configuration
    $public_address = getenv('PUBLIC_ADDRESS');
    $username = getenv('SMS_USERNAME');
    $password = getenv('SMS_PASSWORD');
    
    if (empty($public_address) || empty($username) || empty($password)) {
        error_log("SMS Configuration missing: PUBLIC_ADDRESS, SMS_USERNAME, or SMS_PASSWORD not set");
        return false;
    }
    
    // Construct URL - SMSgate typically uses /send endpoint
    // Format: http://IP:PORT/send
    $url = 'http://' . $public_address . '/send';
    
    // Prepare data - SMSgate typically uses 'phone' and 'message' parameters
    $data = [
        'phone' => $phone,
        'message' => $message,
        'username' => $username,
        'password' => $password
    ];
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded'
        ],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_VERBOSE => false
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Log for debugging
    if ($curl_error) {
        error_log("SMS CURL Error to $phone: " . $curl_error);
        return false;
    }
    
    // Check response - SMSgate might return different success indicators
    if ($http_code >= 200 && $http_code < 300) {
        error_log("SMS sent successfully to $phone: HTTP $http_code, Response: " . substr($response, 0, 100));
        return true;
    } else {
        // Some SMSgate apps return 200 even on error, check response content
        $response_lower = strtolower(trim($response));
        if (strpos($response_lower, 'success') !== false || strpos($response_lower, 'sent') !== false || empty($response_lower)) {
            error_log("SMS sent to $phone: HTTP $http_code, Response: " . substr($response, 0, 100));
            return true;
        }
        
        error_log("SMS failed to $phone: HTTP $http_code, Response: " . substr($response, 0, 200));
        return false;
    }
}
