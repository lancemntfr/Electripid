<?php
// paypal/paypal.php (Debug Version)

// Start output buffering
ob_start();

// Enable error logging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

// Log to file for debugging
$logFile = __DIR__ . '/paypal_debug.log';
function logDebug($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

logDebug("=== PayPal Request Started ===");
logDebug("Request URI: " . $_SERVER['REQUEST_URI']);
logDebug("Request Method: " . $_SERVER['REQUEST_METHOD']);

// Load dependencies
try {
    require_once __DIR__ . '/config.php';
    logDebug("config.php loaded");
} catch (Exception $e) {
    logDebug("Error loading config.php: " . $e->getMessage());
    ob_clean();
    echo json_encode(['error' => 'Configuration error: ' . $e->getMessage()]);
    exit;
}

try {
    require_once __DIR__ . '/../connect.php';
    logDebug("connect.php loaded");
} catch (Exception $e) {
    logDebug("Error loading connect.php: " . $e->getMessage());
    ob_clean();
    echo json_encode(['error' => 'Database connection error']);
    exit;
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$action = $_GET['action'] ?? '';
$input = file_get_contents('php://input');
logDebug("Action: $action");
logDebug("Raw Input: $input");

$input = json_decode($input, true);
logDebug("Decoded Input: " . json_encode($input));

/* ===============================
   CREATE ORDER
=============================== */
if ($action === 'create') {
    logDebug("CREATE ORDER action");
    
    $amount = $input['amount'] ?? null;
    logDebug("Amount received: $amount");
    
    if (!$amount || !is_numeric($amount) || $amount <= 0) {
        logDebug("Invalid amount");
        ob_clean();
        echo json_encode(['error' => 'Invalid amount']);
        exit;
    }

    logDebug("Getting PayPal access token...");
    $token = getAccessToken();
    
    if (!$token) {
        logDebug("Failed to get access token");
        ob_clean();
        echo json_encode(['error' => 'PayPal authentication failed']);
        exit;
    }
    
    logDebug("Access token obtained: " . substr($token, 0, 20) . "...");

    $orderData = [
        'intent' => 'CAPTURE',
        'purchase_units' => [[
            'amount' => [
                'currency_code' => 'USD',
                'value' => number_format((float)$amount, 2, '.', '')
            ],
            'description' => 'Donation to Electripid'
        ]],
        'application_context' => [
            'brand_name' => 'Electripid',
            'shipping_preference' => 'NO_SHIPPING'
        ]
    ];
    
    logDebug("Order data: " . json_encode($orderData));

    $ch = curl_init(PAYPAL_API_BASE . '/v2/checkout/orders');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            "Authorization: Bearer $token"
        ],
        CURLOPT_POSTFIELDS => json_encode($orderData)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    logDebug("HTTP Code: $httpCode");
    logDebug("cURL Error: $curlError");
    logDebug("Response: $response");

    $result = json_decode($response, true);

    if ($httpCode !== 201 || !isset($result['id'])) {
        logDebug("Order creation failed");
        ob_clean();
        echo json_encode([
            'error' => 'Order creation failed',
            'details' => $result['message'] ?? 'Unknown error',
            'http_code' => $httpCode
        ]);
        exit;
    }

    logDebug("Order created successfully: " . $result['id']);
    ob_clean();
    echo json_encode(['id' => $result['id']]);
    exit;
}

/* ===============================
   CAPTURE PAYMENT
=============================== */
if ($action === 'capture') {
    logDebug("CAPTURE action");
    
    $orderID = $input['orderID'] ?? null;
    $phpAmount = $input['phpAmount'] ?? null;
    
    logDebug("Order ID: $orderID");
    logDebug("PHP Amount: $phpAmount");
    
    if (!$orderID) {
        logDebug("Missing order ID");
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Missing order ID']);
        exit;
    }

    $token = getAccessToken();
    if (!$token) {
        logDebug("Failed to get access token for capture");
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Authentication failed']);
        exit;
    }

    $ch = curl_init(PAYPAL_API_BASE . "/v2/checkout/orders/$orderID/capture");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            "Authorization: Bearer $token"
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    logDebug("Capture HTTP Code: $httpCode");
    logDebug("Capture Response: $response");

    $result = json_decode($response, true);

    if ($httpCode !== 201 || ($result['status'] ?? '') !== 'COMPLETED') {
        logDebug("Capture failed");
        ob_clean();
        echo json_encode([
            'success' => false,
            'error' => 'Capture failed',
            'details' => $result['message'] ?? 'Unknown error'
        ]);
        exit;
    }

    /* Save to database */
    $user_id = $_SESSION['user_id'] ?? null;
    logDebug("User ID from session: $user_id");
    
    if ($user_id && isset($result['purchase_units'][0]['payments']['captures'][0])) {
        $capture = $result['purchase_units'][0]['payments']['captures'][0];
        $amountUSD = $capture['amount']['value'];
        $reference = $capture['id'];
        
        $finalAmount = $phpAmount ?? ($amountUSD * 59);
        
        logDebug("Saving to database - User: $user_id, Amount: $finalAmount, Reference: $reference");
        
        $stmt = $conn->prepare(
            "INSERT INTO DONATION 
            (user_id, amount, donation_method, donation_status, reference, donation_date)
            VALUES (?, ?, 'PayPal', 'completed', ?, NOW())"
        );
        
        if ($stmt) {
            $stmt->bind_param("ids", $user_id, $finalAmount, $reference);
            $success = $stmt->execute();
            
            if ($success) {
                logDebug("Database insert successful");
                $donation_id = $conn->insert_id;
                
                // Create notification
                $notif_stmt = $conn->prepare(
                    "INSERT INTO NOTIFICATION 
                    (user_id, notification_type, channel, related_id, related_type, title, message, status)
                    VALUES (?, 'alert', 'in-app', ?, 'donation', 'Thank You for Your Donation!', ?, 'sent')"
                );
                
                if ($notif_stmt) {
                    $notif_message = "Thank you for donating ₱" . number_format($finalAmount, 2) . " to Electripid!";
                    $notif_stmt->bind_param("iis", $user_id, $donation_id, $notif_message);
                    $notif_stmt->execute();
                    $notif_stmt->close();
                    logDebug("Notification created");
                }
            } else {
                logDebug("Database insert failed: " . $stmt->error);
            }
            
            $stmt->close();
        } else {
            logDebug("Failed to prepare statement: " . $conn->error);
        }
    } else {
        logDebug("No user_id in session or invalid capture data");
    }

    logDebug("Capture completed successfully");
    ob_clean();
    echo json_encode([
        'success' => true,
        'orderID' => $orderID,
        'captureID' => $result['purchase_units'][0]['payments']['captures'][0]['id'] ?? null
    ]);
    exit;
}

/* ===============================
   INVALID REQUEST
=============================== */
logDebug("Invalid action: $action");
ob_clean();
echo json_encode(['error' => 'Invalid action']);
exit;