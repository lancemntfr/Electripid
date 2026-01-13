<?php
    session_start();
    header('Content-Type: application/json');

    require_once __DIR__ . '/../../connect.php';

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $user_id_escaped = mysqli_real_escape_string($conn, $user_id);

    // Get household_id for the user
    $household_query = "SELECT household_id FROM HOUSEHOLD WHERE user_id = '$user_id_escaped'";
    $household_result = executeQuery($household_query);

    if (!$household_result || mysqli_num_rows($household_result) === 0) {
        echo json_encode(['success' => false, 'error' => 'Household not found']);
        exit;
    }

    $household_row = mysqli_fetch_assoc($household_result);
    $household_id = mysqli_real_escape_string($conn, $household_row['household_id']);

    $appliance_query = "SELECT * FROM APPLIANCE WHERE household_id = '$household_id' ORDER BY appliance_id DESC";
    $appliance_result = executeQuery($appliance_query);

    $appliances = [];
    if ($appliance_result) {
        while ($row = mysqli_fetch_assoc($appliance_result)) {
            $appliances[] = $row;
        }
    }

    echo json_encode([
        'success' => true,
        'appliances' => $appliances
    ]);

    $conn->close();
?>