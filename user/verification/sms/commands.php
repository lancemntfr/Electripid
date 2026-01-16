<?php
    require_once __DIR__ . '/../../../connect.php';
    require_once __DIR__ . '/send_sms.php';

    function calculateRealTimeConsumption($user_id) {
        global $conn;

        $user_id_escaped = mysqli_real_escape_string($conn, $user_id);

        // Get household_id for the user
        $household_query = "SELECT household_id FROM HOUSEHOLD WHERE user_id = '$user_id_escaped'";
        $household_result = executeQuery($household_query);

        if (!$household_result || mysqli_num_rows($household_result) === 0) {
            return 0.0; // No household found
        }

        $household_row = mysqli_fetch_assoc($household_result);
        $household_id = mysqli_real_escape_string($conn, $household_row['household_id']);

        // Get all appliances for this household
        $appliance_query = "SELECT appliance_name, power_kwh, hours_per_day, usage_per_week FROM APPLIANCE WHERE household_id = '$household_id'";
        $appliance_result = executeQuery($appliance_query);

        if (!$appliance_result) {
            return 0.0; // No appliances found
        }

        $totalConsumption = 0.0;
        $current_hour = date('H'); // Current hour (0-23)

        while ($appliance = mysqli_fetch_assoc($appliance_result)) {
            $power_kwh = floatval($appliance['power_kwh']);
            $hours_per_day = floatval($appliance['hours_per_day']);
            $usage_per_week = floatval($appliance['usage_per_week']);

            // Calculate probability of appliance being on at current time
            // This is a simplified model - in reality you'd have more sophisticated logic
            $usage_probability = 0.0;

            $appliance_name = strtolower($appliance['appliance_name']);

            // Always-on appliances (refrigerator, etc.)
            if (strpos($appliance_name, 'fridge') !== false || strpos($appliance_name, 'refrigerator') !== false) {
                $usage_probability = 0.9; // 90% of the time
            }
            // Lighting (more likely during evening/night)
            elseif (strpos($appliance_name, 'light') !== false || strpos($appliance_name, 'lamp') !== false) {
                if ($current_hour >= 18 || $current_hour <= 6) { // 6 PM to 6 AM
                    $usage_probability = 0.7;
                } else {
                    $usage_probability = 0.3;
                }
            }
            // TV/Entertainment (evening hours)
            elseif (strpos($appliance_name, 'tv') !== false || strpos($appliance_name, 'computer') !== false || strpos($appliance_name, 'laptop') !== false) {
                if ($current_hour >= 17 && $current_hour <= 23) { // 5 PM to 11 PM
                    $usage_probability = 0.6;
                } else {
                    $usage_probability = 0.1;
                }
            }
            // Kitchen appliances (meal times)
            elseif (strpos($appliance_name, 'microwave') !== false || strpos($appliance_name, 'oven') !== false ||
                    strpos($appliance_name, 'stove') !== false || strpos($appliance_name, 'coffee') !== false) {
                if (($current_hour >= 6 && $current_hour <= 9) || ($current_hour >= 17 && $current_hour <= 21)) { // Breakfast/lunch/dinner hours
                    $usage_probability = 0.4;
                } else {
                    $usage_probability = 0.05;
                }
            }
            // Air conditioning/Heating (temperature dependent - simplified)
            elseif (strpos($appliance_name, 'ac') !== false || strpos($appliance_name, 'air') !== false ||
                    strpos($appliance_name, 'fan') !== false || strpos($appliance_name, 'heater') !== false) {
                $usage_probability = 0.5; // Assume 50% chance
            }
            // Other appliances - base probability on usage patterns
            else {
                // Calculate based on typical daily usage
                $daily_usage_hours = $hours_per_day * ($usage_per_week / 7);
                if ($daily_usage_hours > 0) {
                    $usage_probability = min(0.8, $daily_usage_hours / 24); // Max 80% probability
                } else {
                    $usage_probability = 0.1; // Low probability for rarely used items
                }
            }

            // Calculate current consumption for this appliance
            if ($usage_probability > 0) {
                $current_consumption = $power_kwh * $usage_probability;
                $totalConsumption += $current_consumption;
            }
        }

        // Round to 2 decimal places
        $totalConsumption = round($totalConsumption, 2);
        return $totalConsumption;
    }

    function calculateBudgetForecast($user_id) {
        global $conn;

        $user_id_escaped = mysqli_real_escape_string($conn, $user_id);

        // Get household and budget info
        $household_query = "
            SELECT h.household_id, h.monthly_budget,
                   COALESCE(SUM(a.estimated_cost), 0) as total_monthly_cost
            FROM HOUSEHOLD h
            LEFT JOIN APPLIANCE a ON h.household_id = a.household_id
            WHERE h.user_id = '$user_id_escaped'
            GROUP BY h.household_id, h.monthly_budget
        ";
        $household_result = executeQuery($household_query);

        if (!$household_result || mysqli_num_rows($household_result) === 0) {
            return [
                'message' => "📊 Budget Forecast:\nUnable to calculate forecast.\nPlease set up your household and budget."
            ];
        }

        $household_data = mysqli_fetch_assoc($household_result);
        $monthly_budget = floatval($household_data['monthly_budget']);
        $current_monthly_cost = floatval($household_data['total_monthly_cost']);

        if ($monthly_budget <= 0) {
            return [
                'message' => "📊 Budget Forecast:\nMonthly budget not set.\nPlease configure your budget in settings."
            ];
        }

        $remaining_budget = $monthly_budget - $current_monthly_cost;
        $percentage_used = ($current_monthly_cost / $monthly_budget) * 100;

        // Calculate days remaining in month
        $days_in_month = date('t');
        $current_day = date('j');
        $days_remaining = $days_in_month - $current_day + 1;

        // Daily budget remaining
        $daily_budget_remaining = $remaining_budget / max(1, $days_remaining);

        $message = "📊 Budget Forecast:\n";
        $message .= "Monthly Budget: ₱" . number_format($monthly_budget, 2) . "\n";
        $message .= "Current Usage: ₱" . number_format($current_monthly_cost, 2) . "\n";
        $message .= "Remaining: ₱" . number_format($remaining_budget, 2) . "\n";
        $message .= "Days Left: {$days_remaining}\n";

        if ($percentage_used >= 100) {
            $message .= "⚠️ Budget exceeded by ₱" . number_format(abs($remaining_budget), 2);
        } elseif ($percentage_used >= 80) {
            $message .= "⚠️ " . round($percentage_used, 1) . "% used. Conserve energy!";
        } else {
            $message .= "✅ " . round($percentage_used, 1) . "% used. Good progress!";
        }

        return ['message' => $message];
    }

    function handleSMSCommand($phone, $text) {
        global $conn;

        $cmd = strtolower(trim($text));

        // Debug logging
        error_log("SMS Command received - Phone: $phone, Text: '$text', Processed Cmd: '$cmd'");

        $lockFile = sys_get_temp_dir() . '/sms_' . md5($phone . '|' . $cmd);

        if (file_exists($lockFile) && (time() - filemtime($lockFile)) < 5) {
            return;
        }
        touch($lockFile);

        if (preg_match('/otp/i', $cmd) && preg_match('/[0-9]{6}/', $cmd)) {
            return;
        }

        $escaped_phone = mysqli_real_escape_string($conn, $phone);
        $result = executeQuery("SELECT user_id FROM USER WHERE cp_number='$escaped_phone'");

        if ($result->num_rows === 0) {
            sendSMS($phone, "Number not registered.\nPlease register first.");
            return;
        }

        // Get user data once for all operations
        $user = mysqli_fetch_assoc($result);
        $user_id = $user['user_id'];

        // Only process exact single digit commands
        if (in_array($cmd, ['1', '2', '3'])) {
            if ($cmd === '1') {
            // Calculate budget forecast
            $forecast_data = calculateBudgetForecast($user_id);

            $sms_message = "📱 SMS: {$text}";
            $stmt = $conn->prepare("INSERT INTO CHATBOT (user_id, sender, message_text, timestamp) VALUES (?, 'user', ?, NOW())");
            $stmt->bind_param("is", $user_id, $sms_message);
            $stmt->execute();

            $response = $forecast_data['message'];
            $stmt = $conn->prepare("INSERT INTO CHATBOT (user_id, sender, message_text, timestamp) VALUES (?, 'bot', ?, NOW())");
            $stmt->bind_param("is", $user_id, $response);
            $stmt->execute();

            sendSMS($phone, $response);
            return;
        } elseif ($cmd === '2') {
            // Calculate real-time consumption based on user's appliances
            $currentConsumption = calculateRealTimeConsumption($user_id);

            $sms_message = "📱 SMS: {$text}";
            $stmt = $conn->prepare("INSERT INTO CHATBOT (user_id, sender, message_text, timestamp) VALUES (?, 'user', ?, NOW())");
            $stmt->bind_param("is", $user_id, $sms_message);
            $stmt->execute();

            $response = "⚡ Real-time Consumption:\nCurrent usage is {$currentConsumption} kWh.";
            $stmt = $conn->prepare("INSERT INTO CHATBOT (user_id, sender, message_text, timestamp) VALUES (?, 'bot', ?, NOW())");
            $stmt->bind_param("is", $user_id, $response);
            $stmt->execute();

            sendSMS($phone, $response);
            return;
        } elseif ($cmd === '3') {
            // Random energy saving tips
            $tips = [
                "💡 Turn off lights when not in use to save up to 10% on your electricity bill.",
                "🔌 Unplug appliances when not in use - they still consume power even when turned off.",
                "🌡️ Set your thermostat 1-2 degrees lower in winter and higher in summer for savings.",
                "🍳 Use energy-efficient appliances with high star ratings to reduce consumption.",
                "🧺 Wash clothes in cold water and air dry when possible.",
                "💻 Enable power-saving mode on computers and other devices."
            ];

            $randomTip = $tips[array_rand($tips)];

            $sms_message = "📱 SMS: {$text}";
            $stmt = $conn->prepare("INSERT INTO CHATBOT (user_id, sender, message_text, timestamp) VALUES (?, 'user', ?, NOW())");
            $stmt->bind_param("is", $user_id, $sms_message);
            $stmt->execute();

            $response = "💡 Energy Tip:\n{$randomTip}";
            $stmt = $conn->prepare("INSERT INTO CHATBOT (user_id, sender, message_text, timestamp) VALUES (?, 'bot', ?, NOW())");
            $stmt->bind_param("is", $user_id, $response);
            $stmt->execute();

            sendSMS($phone, $response);
            return;
            }
        } else {
            // Default case - unrecognized command
            $sms_message = "📱 SMS: {$text}";
            $stmt = $conn->prepare("INSERT INTO CHATBOT (user_id, sender, message_text, timestamp) VALUES (?, 'user', ?, NOW())");
            $stmt->bind_param("is", $user_id, $sms_message);
            $stmt->execute();

            $response = "Reply:\n1 - Forecast\n2 - Real-time Consumption\n3 - Energy Tip";
            $stmt = $conn->prepare("INSERT INTO CHATBOT (user_id, sender, message_text, timestamp) VALUES (?, 'bot', ?, NOW())");
            $stmt->bind_param("is", $user_id, $response);
            $stmt->execute();

            sendSMS($phone, $response);
        }
    }
?>