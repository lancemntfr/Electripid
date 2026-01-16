<?php
require_once '../connect.php';

/**
 * Sync user update to external database (Airlyft)
 * @param int $user_id The user ID to sync
 * @return array Result with success status and message
 */
function syncUserUpdate($user_id) {
    global $conn;

    try {
        // Get user data
        $user_id_escaped = mysqli_real_escape_string($conn, $user_id);
        $query = "SELECT fname, lname, email, cp_number, password, source_system FROM USER WHERE user_id = '$user_id_escaped'";
        $result = executeQuery($query);

        if (!$result || mysqli_num_rows($result) === 0) {
            return ['success' => false, 'message' => 'User not found for sync'];
        }

        $user = mysqli_fetch_assoc($result);

        // Prepare payload for external system
        $payload = [
            "external_id"   => "G1-$user_id",
            "first_name"    => $user['fname'],
            "last_name"     => $user['lname'],
            "email"         => $user['email'],
            "phone"         => $user['cp_number'] ?: "n/a",
            "password"      => $user['password'] ?: "n/a",
            "source_system" => $user['source_system'] ?: "Electripid"
        ];

        // Send to external system
        $ch = curl_init(getenv('API_LOCAL_ADDRESS') . "/airlyft/integrations/api/sync_user.php");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "X-API-KEY: " . getenv('GROUP2_API_KEY')
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Log the sync attempt
        $logMessage = "User sync update - User ID: $user_id, HTTP Code: $httpCode, Response: $response";
        if ($curlError) {
            $logMessage .= ", Error: $curlError";
        }
        error_log($logMessage);

        if ($httpCode === 200) {
            $responseData = json_decode($response, true);
            if (isset($responseData['success']) && $responseData['success']) {
                return ['success' => true, 'message' => 'User synced successfully'];
            } else {
                return ['success' => false, 'message' => 'External system rejected sync: ' . ($responseData['message'] ?? 'Unknown error')];
            }
        } else {
            return ['success' => false, 'message' => "Sync failed with HTTP $httpCode: $response"];
        }

    } catch (Exception $e) {
        error_log("Exception in syncUserUpdate: " . $e->getMessage());
        return ['success' => false, 'message' => 'Exception occurred during sync: ' . $e->getMessage()];
    }
}

/**
 * Sync user deletion to external database (Airlyft)
 * @param int $user_id The user ID that was deleted
 * @param array $user_data The user data before deletion (for reference)
 * @return array Result with success status and message
 */
function syncUserDeletion($user_id, $user_data = []) {
    global $conn;

    try {
        // For deletion sync, we might need to notify the external system
        // This could be a separate endpoint or a flag in the sync payload
        $payload = [
            "external_id"   => "G1-$user_id",
            "action"        => "delete",
            "email"         => $user_data['email'] ?? '',
            "source_system" => "Electripid"
        ];

        // Send deletion notification to external system
        $ch = curl_init(getenv('API_LOCAL_ADDRESS') . "/airlyft/integrations/api/sync_user.php");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "X-API-KEY: " . getenv('GROUP2_API_KEY')
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Log the sync attempt
        $logMessage = "User sync deletion - User ID: $user_id, HTTP Code: $httpCode, Response: $response";
        if ($curlError) {
            $logMessage .= ", Error: $curlError";
        }
        error_log($logMessage);

        // For deletion, we consider it successful if the request was accepted
        // Even if the external system doesn't have the user, it's not an error
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'message' => 'User deletion synced successfully'];
        } else {
            return ['success' => false, 'message' => "Deletion sync failed with HTTP $httpCode: $response"];
        }

    } catch (Exception $e) {
        error_log("Exception in syncUserDeletion: " . $e->getMessage());
        return ['success' => false, 'message' => 'Exception occurred during deletion sync: ' . $e->getMessage()];
    }
}

/**
 * Check if sync is enabled/configured
 * @return bool True if sync is configured
 */
function isSyncEnabled() {
    return !empty(getenv('API_LOCAL_ADDRESS')) && !empty(getenv('GROUP2_API_KEY'));
}
?>