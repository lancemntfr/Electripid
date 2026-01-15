<?php
require_once __DIR__ . '/../../connect.php';

function syncUserToAirlyft($user_id) {
    global $conn;

    // Get user data
    $user_id_escaped = mysqli_real_escape_string($conn, $user_id);
    $query = "SELECT fname, lname, email, cp_number, password FROM USER WHERE user_id = '$user_id_escaped'";
    $result = executeQuery($query);

    if (!$result || mysqli_num_rows($result) === 0) {
        return false;
    }

    $user = mysqli_fetch_assoc($result);

    // Prepare payload for Airlyft
    $payload = [
        "external_id"   => "G1-$user_id",
        "first_name"    => $user['fname'],
        "last_name"     => $user['lname'],
        "email"         => $user['email'],
        "phone"         => $user['cp_number'] ?: "n/a",
        "password"      => $user['password'],
        "source_system" => "Electripid"
    ];

    // Send to Airlyft
    $ch = curl_init(getenv('API_LOCAL_ADDRESS') . "/airlyft/integrations/api/sync_user.php");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "X-API-KEY: " . getenv('GROUP2_API_KEY')
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    error_log("Sync to Airlyft - HTTP Code: $httpCode, Response: $response, Error: $curlError");

    return $httpCode === 200;
}
?>