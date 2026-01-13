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

    $energy_kwh = floatval($data['energy_kwh'] ?? 0);
    $voltage = floatval($data['voltage'] ?? 220);
    $current = floatval($data['current'] ?? 0);
    $power = floatval($data['power'] ?? 0);

    if ($energy_kwh <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid energy reading']);
        exit;
    }

    $user_id = mysqli_real_escape_string($conn, $user_id);
    $household_query = "SELECT household_id FROM HOUSEHOLD WHERE user_id = '$user_id'";
    $household_result = executeQuery($household_query);

    if (!$household_result || mysqli_num_rows($household_result) === 0) {
        echo json_encode(['success' => false, 'error' => 'Household not found']);
        exit;
    }

    $household_row = mysqli_fetch_assoc($household_result);
    $household_id = $household_row['household_id'];

    $household_id = mysqli_real_escape_string($conn, $household_id);
    $voltage = mysqli_real_escape_string($conn, $voltage);
    $current = mysqli_real_escape_string($conn, $current);
    $power = mysqli_real_escape_string($conn, $power);
    $energy_kwh = mysqli_real_escape_string($conn, $energy_kwh);

    $insert_query = "INSERT INTO ELECTRICITY_READINGS (household_id, voltage, current, power, energy_kwh, recorded_at) VALUES ('$household_id', '$voltage', '$current', '$power', '$energy_kwh', NOW())";
    $insert_result = executeQuery($insert_query);

    if ($insert_result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Reading saved successfully',
            'reading_id' => mysqli_insert_id($conn)
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save reading']);
    }

    $conn->close();
?>
