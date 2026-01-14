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

    if (
        !$data ||
        empty($data['appliance_id']) ||
        empty($data['name']) ||
        empty($data['power']) ||
        empty($data['hours']) ||
        empty($data['usage_per_week'])
    ) {
        echo json_encode(['success' => false, 'error' => 'All fields are required']);
        exit;
    }

    $appliance_id = intval($data['appliance_id']);
    $appliance_name = mysqli_real_escape_string($conn, trim($data['name']));
    $power_kwh = floatval($data['power']) / 1000;
    $hours_per_day = floatval($data['hours']);
    $usage_per_week = floatval($data['usage_per_week']);
    $rate = floatval($data['rate'] ?? 12.00);

    // Recalculate monthly kWh and estimated cost
    $monthly_kwh = $power_kwh * $hours_per_day * $usage_per_week * 4.33;
    $estimated_cost = $monthly_kwh * $rate;

    $user_id_escaped = mysqli_real_escape_string($conn, $user_id);
    $appliance_id_escaped = mysqli_real_escape_string($conn, $appliance_id);

    // Ensure the appliance belongs to the current user's household
    $check_query = "
        SELECT a.appliance_id
        FROM APPLIANCE a
        JOIN HOUSEHOLD h ON a.household_id = h.household_id
        WHERE a.appliance_id = '$appliance_id_escaped'
          AND h.user_id = '$user_id_escaped'
        LIMIT 1
    ";

    $check_result = executeQuery($check_query);
    if (!$check_result || mysqli_num_rows($check_result) === 0) {
        echo json_encode(['success' => false, 'error' => 'Appliance not found or access denied']);
        exit;
    }

    $update_query = "
        UPDATE APPLIANCE
        SET appliance_name = '$appliance_name',
            power_kwh = '$power_kwh',
            hours_per_day = '$hours_per_day',
            usage_per_week = '$usage_per_week',
            estimated_cost = '$estimated_cost'
        WHERE appliance_id = '$appliance_id_escaped'
    ";

    if (executeQuery($update_query)) {
        echo json_encode(['success' => true, 'message' => 'Appliance updated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update appliance']);
    }

    $conn->close();
?>

