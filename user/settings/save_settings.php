<?php
    session_start();
    header('Content-Type: application/json');

    require_once __DIR__ . '/../../connect.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
        exit;
    }

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
        exit;
    }

    $location = $data['location'] ?? '';
    $provider = $data['provider'] ?? '';
    $rate_per_kwh = floatval($data['rate_per_kwh'] ?? 0);
    $monthly_budget = floatval($data['monthly_budget'] ?? 0);

    $user_id_escaped = mysqli_real_escape_string($conn, $user_id);
    $check_query = "SELECT household_id, provider_id FROM HOUSEHOLD WHERE user_id = '$user_id_escaped'";
    $household_result = executeQuery($check_query);

    if ($household_result && mysqli_num_rows($household_result) > 0) {
        $household_row = mysqli_fetch_assoc($household_result);
        $household_id = mysqli_real_escape_string($conn, $household_row['household_id']);
        $current_provider_id = $household_row['provider_id'];
        
        // If only budget is being updated
        if ($monthly_budget > 0 && empty($provider) && empty($location)) {
            $monthly_budget_escaped = mysqli_real_escape_string($conn, $monthly_budget);
            $update_query = "UPDATE HOUSEHOLD SET monthly_budget = '$monthly_budget_escaped' WHERE household_id = '$household_id'";
            $update_result = executeQuery($update_query);
            
            if ($update_result) {
                echo json_encode(['success' => true, 'message' => 'Monthly budget updated successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update budget']);
            }
        } else {
            // Full update with provider and location
            if (!empty($provider)) {
                $provider_escaped = mysqli_real_escape_string($conn, $provider);
                $provider_query = "SELECT provider_id FROM ELECTRICITY_PROVIDER WHERE provider_name = '$provider_escaped'";
                $provider_result = executeQuery($provider_query);

                if (!$provider_result || mysqli_num_rows($provider_result) === 0) {
                    echo json_encode(['success' => false, 'error' => 'Invalid provider']);
                    exit;
                }

                $provider_row = mysqli_fetch_assoc($provider_result);
                $provider_id = mysqli_real_escape_string($conn, $provider_row['provider_id']);
            } else {
                $provider_id = mysqli_real_escape_string($conn, $current_provider_id);
            }
            
            $location_escaped = mysqli_real_escape_string($conn, $location);
            $monthly_budget_escaped = mysqli_real_escape_string($conn, $monthly_budget);
            
            $update_query = "UPDATE HOUSEHOLD SET provider_id = '$provider_id'";
            if (!empty($location)) {
                $update_query .= ", city = '$location_escaped'";
            }
            if ($monthly_budget > 0) {
                $update_query .= ", monthly_budget = '$monthly_budget_escaped'";
            }
            $update_query .= " WHERE household_id = '$household_id'";
            
            $update_result = executeQuery($update_query);
            
            if ($update_result) {
                echo json_encode(['success' => true, 'message' => 'Settings updated successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update settings']);
            }
        }
    } else {
        // Create new household if it doesn't exist
        if (!empty($provider)) {
            $provider_escaped = mysqli_real_escape_string($conn, $provider);
            $provider_query = "SELECT provider_id FROM ELECTRICITY_PROVIDER WHERE provider_name = '$provider_escaped'";
            $provider_result = executeQuery($provider_query);

            if (!$provider_result || mysqli_num_rows($provider_result) === 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid provider']);
                exit;
            }

            $provider_row = mysqli_fetch_assoc($provider_result);
            $provider_id = mysqli_real_escape_string($conn, $provider_row['provider_id']);
        } else {
            // Get first provider as default if none specified
            $provider_query = "SELECT provider_id FROM ELECTRICITY_PROVIDER LIMIT 1";
            $provider_result = executeQuery($provider_query);
            if ($provider_result && mysqli_num_rows($provider_result) > 0) {
                $provider_row = mysqli_fetch_assoc($provider_result);
                $provider_id = mysqli_real_escape_string($conn, $provider_row['provider_id']);
            } else {
                echo json_encode(['success' => false, 'error' => 'No provider available']);
                exit;
            }
        }
        
        $location_escaped = mysqli_real_escape_string($conn, $location);
        $monthly_budget_escaped = mysqli_real_escape_string($conn, $monthly_budget);
        
        $insert_query = "INSERT INTO HOUSEHOLD (user_id, provider_id, city, monthly_budget) VALUES ('$user_id_escaped', '$provider_id', '$location_escaped', '$monthly_budget_escaped')";
        $insert_result = executeQuery($insert_query);
        
        if ($insert_result) {
            echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save settings']);
        }
    }

    $conn->close();
?>
