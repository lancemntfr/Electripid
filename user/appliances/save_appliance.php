<?php
    session_start();
    header('Content-Type: application/json');
    require_once __DIR__ . '/../../connect.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || empty($data['name']) || empty($data['power']) || empty($data['hours']) || empty($data['usage_per_week'])) {
        echo json_encode(['success' => false, 'error' => 'All fields are required']);
        exit;
    }

    // Get or create household
    $user_id = mysqli_real_escape_string($conn, $user_id);
    $household_query = "SELECT household_id FROM HOUSEHOLD WHERE user_id = '$user_id'";
    $household_result = executeQuery($household_query);

    if (!$household_result || mysqli_num_rows($household_result) === 0) {
        // Create household with default provider
        $default_provider_query = "SELECT provider_id FROM ELECTRICITY_PROVIDER LIMIT 1";
        $provider_result = executeQuery($default_provider_query);
        $provider_row = mysqli_fetch_assoc($provider_result);
        $provider_id = mysqli_real_escape_string($conn, $provider_row['provider_id']);
        
        $create_household = "INSERT INTO HOUSEHOLD (user_id, provider_id) VALUES ('$user_id', '$provider_id')";
        executeQuery($create_household);
        $household_id = mysqli_insert_id($conn);
    } else {
        $household_row = mysqli_fetch_assoc($household_result);
        $household_id = $household_row['household_id'];
    }

    // Calculate values
    $appliance_name = mysqli_real_escape_string($conn, trim($data['name']));
    $power_kwh = floatval($data['power']) / 1000;
    $hours_per_day = floatval($data['hours']);
    $usage_per_week = floatval($data['usage_per_week']);
    $rate = floatval($data['rate'] ?? 12.00);

    $monthly_kwh = $power_kwh * $hours_per_day * $usage_per_week * 4.33;
    $estimated_cost = $monthly_kwh * $rate;

    // Insert appliance
    $insert_query = "INSERT INTO APPLIANCE (household_id, appliance_name, power_kwh, hours_per_day, usage_per_week, estimated_cost)
                     VALUES ('$household_id', '$appliance_name', '$power_kwh', '$hours_per_day', '$usage_per_week', '$estimated_cost')";

    if (executeQuery($insert_query)) {
        echo json_encode(['success' => true, 'message' => 'Appliance added successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add appliance']);
    }

    $conn->close();
?>
