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

    $forecast_type = $data['forecast_type'] ?? 'monthly';
    $predicted_kwh = floatval($data['predicted_kwh'] ?? 0);
    $predicted_cost = floatval($data['predicted_cost'] ?? 0);
    $source_type = $data['source_type'] ?? 'appliances';
    $forecast_date = $data['forecast_date'] ?? date('Y-m-d');

    if ($predicted_kwh <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid forecast data']);
        exit;
    }

    if (!in_array($forecast_type, ['weekly', 'monthly'])) {
        $forecast_type = 'monthly';
    }

    if (!in_array($source_type, ['readings', 'appliances'])) {
        $source_type = 'appliances';
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
    $forecast_type = mysqli_real_escape_string($conn, $forecast_type);
    $predicted_kwh = mysqli_real_escape_string($conn, $predicted_kwh);
    $predicted_cost = mysqli_real_escape_string($conn, $predicted_cost);
    $source_type = mysqli_real_escape_string($conn, $source_type);
    $forecast_date = mysqli_real_escape_string($conn, $forecast_date);

    $insert_query = "INSERT INTO FORECAST (household_id, forecast_type, predicted_kwh, predicted_cost, source_type, forecast_date) VALUES ('$household_id', '$forecast_type', '$predicted_kwh', '$predicted_cost', '$source_type', '$forecast_date')";
    $insert_result = executeQuery($insert_query);

    if ($insert_result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Forecast saved successfully',
            'forecast_id' => mysqli_insert_id($conn)
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save forecast']);
    }

    $conn->close();
?>
