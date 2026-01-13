<?php
    // Prevent any output before JSON
    ob_start();
    session_start();
    header('Content-Type: application/json');

    require_once __DIR__ . '/../../connect.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
        exit;
    }

    if (!isset($_SESSION['user_id'])) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
        exit;
    }

    $appliance_name = trim($data['name'] ?? '');
    $power_watts = floatval($data['power'] ?? 0);
    $hours_per_day = floatval($data['hours'] ?? 0);
    $usage_per_week = floatval($data['usage_per_week'] ?? 0);
    $rate = floatval($data['rate'] ?? 12.00);

    if (empty($appliance_name) || $power_watts <= 0 || $hours_per_day <= 0 || $usage_per_week <= 0) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'All fields are required and must be greater than 0']);
        exit;
    }

    $user_id = mysqli_real_escape_string($conn, $user_id);
    $household_query = "SELECT household_id FROM HOUSEHOLD WHERE user_id = '$user_id'";
    $household_result = executeQuery($household_query);

    if (!$household_result || mysqli_num_rows($household_result) === 0) {
        $default_provider_query = "SELECT provider_id FROM ELECTRICITY_PROVIDER LIMIT 1";
        $provider_result = executeQuery($default_provider_query);
        
        if (!$provider_result || mysqli_num_rows($provider_result) === 0) {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'No electricity provider found. Please set up your household first.']);
            exit;
        }
        
        $provider_row = mysqli_fetch_assoc($provider_result);
        $provider_id = mysqli_real_escape_string($conn, $provider_row['provider_id']);
        
        $create_household = "INSERT INTO HOUSEHOLD (user_id, provider_id) VALUES ('$user_id', '$provider_id')";
        $create_result = executeQuery($create_household);
        
        if (!$create_result) {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Failed to create household']);
            exit;
        }
        
        $household_id = mysqli_insert_id($conn);
    } else {
        $household_row = mysqli_fetch_assoc($household_result);
        $household_id = $household_row['household_id'];
    }

    $power_kwh = ($power_watts / 1000);
    $monthly_kwh = $power_kwh * $hours_per_day * $usage_per_week * 4.33;
    $estimated_cost = $monthly_kwh * $rate;

    $appliance_name = mysqli_real_escape_string($conn, $appliance_name);
    $household_id = mysqli_real_escape_string($conn, $household_id);
    $power_kwh = mysqli_real_escape_string($conn, $power_kwh);
    $hours_per_day = mysqli_real_escape_string($conn, $hours_per_day);
    $usage_per_week = mysqli_real_escape_string($conn, $usage_per_week);
    $estimated_cost = mysqli_real_escape_string($conn, $estimated_cost);

    $insert_query = "INSERT INTO APPLIANCE (household_id, appliance_name, power_kwh, hours_per_day, usage_per_week, estimated_cost) VALUES ('$household_id', '$appliance_name', '$power_kwh', '$hours_per_day', '$usage_per_week', '$estimated_cost')";
    $insert_result = executeQuery($insert_query);

    if ($insert_result) {
        $appliance_id = mysqli_insert_id($conn);
        
        // Create notification for appliance added
        $notif_title = "Appliance Added";
        $notif_message = "You have successfully added '" . $appliance_name . "' to your appliances list.";
        $notif_query = "INSERT INTO NOTIFICATION (user_id, notification_type, channel, related_id, related_type, title, message, status) VALUES ('$user_id', 'alert', 'in-app', '$appliance_id', 'general', '$notif_title', '$notif_message', 'sent')";
        executeQuery($notif_query);
        
        ob_clean();
        echo json_encode([
            'success' => true, 
            'message' => 'Appliance added successfully',
            'appliance_id' => $appliance_id
        ]);
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Failed to add appliance. Please try again.']);
    }

    $conn->close();
    exit;
?>
