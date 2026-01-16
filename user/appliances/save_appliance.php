<?php
    session_start();
    header('Content-Type: application/json');
    require_once __DIR__ . '/../../connect.php';
    require_once __DIR__ . '/../notification/send_email_alert.php';

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
        // After successful appliance addition, check budget and trigger notifications if needed
        checkAndTriggerBudgetNotification($household_id, $conn);

        echo json_encode(['success' => true, 'message' => 'Appliance added successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add appliance']);
    }

    $conn->close();

function checkAndTriggerBudgetNotification($household_id, $conn) {
    // Optimized query to get budget and total cost in one call
    $household_id_escaped = mysqli_real_escape_string($conn, $household_id);

    $query = "
        SELECT h.monthly_budget, h.user_id,
               COALESCE(SUM(a.estimated_cost), 0) as total_monthly_cost
        FROM HOUSEHOLD h
        LEFT JOIN APPLIANCE a ON h.household_id = a.household_id
        WHERE h.household_id = '$household_id_escaped'
        GROUP BY h.household_id, h.monthly_budget, h.user_id
    ";

    $result = executeQuery($query);
    if (!$result || mysqli_num_rows($result) === 0) {
        return;
    }

    $row = mysqli_fetch_assoc($result);
    $monthly_budget = floatval($row['monthly_budget']);
    $user_id = intval($row['user_id']);
    $total_monthly_cost = floatval($row['total_monthly_cost']);

    // Early return if no valid budget
    if ($monthly_budget <= 0) {
        return;
    }

    $difference = $total_monthly_cost - $monthly_budget;

    // Only trigger notification if budget is exceeded
    if ($difference > 0) {
        $percentage = ($total_monthly_cost / $monthly_budget) * 100;
        $is_warning = $difference <= $monthly_budget * 0.1;

        $title = $is_warning ? 'Budget Warning' : 'Budget Alert';
        $message = $is_warning
            ? "You have exceeded your budget by ₱" . number_format($difference, 2) . ". Consider reducing appliance usage or adjusting your budget in Settings."
            : "You have significantly exceeded your budget by ₱" . number_format($difference, 2) . " (" . number_format($percentage, 1) . "% over). Please reduce appliance usage or increase your budget in Settings to avoid unexpected costs.";

        // Set session context for background notification processing
        $_SESSION['user_id'] = $user_id;

        createImmediateBudgetNotification($user_id, $title, $message, $conn);
    }
}

function createImmediateBudgetNotification($user_id, $title, $message, $conn) {
    // Optimized: Use single query to check and insert, avoiding race conditions
    $user_id_escaped = mysqli_real_escape_string($conn, $user_id);
    $title_escaped = mysqli_real_escape_string($conn, $title);
    $message_escaped = mysqli_real_escape_string($conn, $message);

    // Insert notification only if no unread budget notification exists for this user
    $insert_query = "
        INSERT INTO NOTIFICATION (user_id, notification_type, channel, related_type, title, message, status)
        SELECT '$user_id_escaped', 'budget', 'in-app', 'budget', '$title_escaped', '$message_escaped', 'sent'
        FROM DUAL
        WHERE NOT EXISTS (
            SELECT 1 FROM NOTIFICATION
            WHERE user_id = '$user_id_escaped'
            AND notification_type = 'budget'
            AND is_read = FALSE
            LIMIT 1
        )
    ";

    $insert_result = executeQuery($insert_query);

    // Only trigger external notifications if we actually inserted a new notification
    if ($insert_result && mysqli_affected_rows($conn) > 0) {
        // Schedule external notifications to run asynchronously after response is sent
        $alert_type = strpos($title, 'Alert') !== false ? 'alert' : 'warning';

        // Store notification data for background processing
        $notification_data = [
            'user_id' => $user_id,
            'alert_type' => $alert_type,
            'title' => $title,
            'message' => $message,
            'budget_data' => [
                'monthly_budget' => 0,
                'current_cost' => 0,
                'exceeded_amount' => 0,
                'percentage' => 0
            ]
        ];

        // Register shutdown function to process notifications after response is sent
        register_shutdown_function('sendBudgetNotificationsAsync', $notification_data);
    }
}

function sendBudgetNotificationsAsync($notification_data) {
    // Disconnect from main database connection to avoid conflicts
    if (isset($GLOBALS['conn'])) {
        $GLOBALS['conn']->close();
        unset($GLOBALS['conn']);
    }

    try {
        // Start fresh database connection for background processing
        $bg_conn = new mysqli("localhost", "root", "", "electripid");
        if ($bg_conn->connect_error) {
            throw new Exception("Database connection failed: " . $bg_conn->connect_error);
        }
        $bg_conn->set_charset("utf8mb4");

        // Set global connection for notification functions
        $GLOBALS['conn'] = $bg_conn;

        // Set session context for notification functions
        $_SESSION['user_id'] = $notification_data['user_id'];

        // Trigger external notifications (email/SMS) if preferences allow
        $alert_sent = sendBudgetAlert(
            $notification_data['user_id'],
            $notification_data['alert_type'],
            $notification_data['budget_data']
        );

        if ($alert_sent) {
            error_log("Budget notifications sent successfully for user " . $notification_data['user_id']);
        } else {
            error_log("Failed to send some budget notifications for user " . $notification_data['user_id']);
        }

    } catch (Exception $e) {
        error_log("Error sending budget notifications: " . $e->getMessage());
    } finally {
        // Clean up background database connection
        if (isset($GLOBALS['conn'])) {
            $GLOBALS['conn']->close();
            unset($GLOBALS['conn']);
        }
    }
}
?>
