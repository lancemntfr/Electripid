<?php
// paypal/config.php

// PayPal Sandbox Credentials
define('PAYPAL_CLIENT_ID', 'AWYEp1TqBsmBV8WfID4-nr3Soew-fL2FUx2ubkfXS_Qw41bKVP_YligWWRKjdYJSaQeZvDbSoKzrg5Ro');
define('PAYPAL_SECRET', 'EA_mtzgD2YBgsJ_CQCmgdraxm5cVQuBZ9rl-FoBnDazQwmBdtWGVlzJDeJ3jQFRzYXyyECSvt_Qh544k');

// PayPal API Base URL (Sandbox)
define('PAYPAL_API_BASE', 'https://api-m.sandbox.paypal.com');

// For Production, use:
// define('PAYPAL_API_BASE', 'https://api-m.paypal.com');

/**
 * Get PayPal OAuth Access Token
 * 
 * @return string|false Access token or false on failure
 */
function getAccessToken()
{
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => PAYPAL_API_BASE . '/v1/oauth2/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_USERPWD => PAYPAL_CLIENT_ID . ':' . PAYPAL_SECRET,
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Accept-Language: en_US'
        ]
    ]);
    
    $response = curl_exec($ch);
    
    if ($response === false) {
        $error = curl_error($ch);
        error_log("PayPal cURL Error: " . $error);
        curl_close($ch);
        return false;
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("PayPal Auth Failed. HTTP Code: " . $httpCode . " Response: " . $response);
        return false;
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['access_token'])) {
        error_log("PayPal Auth Response missing access_token: " . $response);
        return false;
    }
    
    return $data['access_token'];
}