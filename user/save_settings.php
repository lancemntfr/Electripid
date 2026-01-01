<?php
    session_start();
    header('Content-Type: application/json');

    require_once '../connect.php';

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

    $provider = mysqli_real_escape_string($conn, $provider);
    $provider_query = "SELECT provider_id FROM ELECTRICITY_PROVIDER WHERE provider_name = '$provider'";
    $provider_result = executeQuery($provider_query);

    if (!$provider_result || mysqli_num_rows($provider_result) === 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid provider']);
        exit;
    }

    $provider_row = mysqli_fetch_assoc($provider_result);
    $provider_id = $provider_row['provider_id'];

    $user_id = mysqli_real_escape_string($conn, $user_id);
    $check_query = "SELECT household_id FROM HOUSEHOLD WHERE user_id = '$user_id'";
    $household_result = executeQuery($check_query);

    if ($household_result && mysqli_num_rows($household_result) > 0) {
        $household_row = mysqli_fetch_assoc($household_result);
        $household_id = mysqli_real_escape_string($conn, $household_row['household_id']);
        $provider_id = mysqli_real_escape_string($conn, $provider_id);
        $location = mysqli_real_escape_string($conn, $location);
        $monthly_budget = mysqli_real_escape_string($conn, $monthly_budget);
        
        $update_query = "UPDATE HOUSEHOLD SET provider_id = '$provider_id', city = '$location', monthly_budget = '$monthly_budget' WHERE household_id = '$household_id'";
        $update_result = executeQuery($update_query);
        
        if ($update_result) {
            echo json_encode(['success' => true, 'message' => 'Settings updated successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update settings']);
        }
    } else {
        $provider_id = mysqli_real_escape_string($conn, $provider_id);
        $location = mysqli_real_escape_string($conn, $location);
        $monthly_budget = mysqli_real_escape_string($conn, $monthly_budget);
        
        $insert_query = "INSERT INTO HOUSEHOLD (user_id, provider_id, city, monthly_budget) VALUES ('$user_id', '$provider_id', '$location', '$monthly_budget')";
        $insert_result = executeQuery($insert_query);
        
        if ($insert_result) {
            echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save settings']);
        }
    }

    $conn->close();
?>