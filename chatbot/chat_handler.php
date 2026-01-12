<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

// Start session with longer lifetime
ini_set('session.gc_maxlifetime', 86400); // 24 hours
session_set_cookie_params(86400); // 24 hours
session_start();

require_once '../connect.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    ob_clean();
    die(json_encode([
        'success' => false, 
        'error' => 'Session expired. Please refresh the page and login again.'
    ]));
}

$input = json_decode(file_get_contents("php://input"), true);
$userMessage = trim($input['message'] ?? '');

if ($userMessage === '') {
    ob_clean();
    die(json_encode(['success' => false, 'error' => 'Empty message']));
}

$user_id = (int) $_SESSION['user_id'];

// Verify user exists in database
$checkStmt = $conn->prepare("SELECT user_id FROM USER WHERE user_id = ? LIMIT 1");
$checkStmt->bind_param("i", $user_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    ob_clean();
    die(json_encode([
        'success' => false, 
        'error' => 'User not found. Please login again.'
    ]));
}

// Build message context with system prompt
$messages = [[
    "role" => "system",
    "content" => "You are Electripid AI Assistant, an expert in Philippine electricity costs and energy efficiency. Answer questions directly and concisely in under 100 words. Use Philippine peso (₱) for costs. Meralco's average rate is ₱11-13/kWh. Be helpful and practical."
]];

// Get recent chat history (last 6 messages for context)
try {
    $historyStmt = $conn->prepare("SELECT sender, message_text FROM CHATBOT WHERE user_id = ? ORDER BY timestamp DESC LIMIT 6");
    $historyStmt->bind_param("i", $user_id);
    $historyStmt->execute();
    $result = $historyStmt->get_result();
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    $history = array_reverse($history);
    
    // Add history to context
    foreach ($history as $msg) {
        $messages[] = [
            "role" => $msg['sender'] === 'user' ? 'user' : 'assistant',
            "content" => $msg['message_text']
        ];
    }
} catch (Exception $e) {
    // Continue without history
}

// Add the current user message
$messages[] = [
    "role" => "user",
    "content" => $userMessage
];

// Save user message to database
try {
    $stmt = $conn->prepare("INSERT INTO CHATBOT (user_id, sender, message_text, timestamp) VALUES (?, 'user', ?, NOW())");
    $stmt->bind_param("is", $user_id, $userMessage);
    $stmt->execute();
} catch (Exception $e) {
    // Continue even if save fails
}

// Prepare Ollama request
$data = [
    "model" => "gemma3:4b",
    "messages" => $messages,
    "stream" => false,
    "options" => [
        "temperature" => 0.7,
        "num_predict" => 200,
        "top_p" => 0.9,
        "repeat_penalty" => 1.2
    ]
];

// Call Ollama API
$ch = curl_init("http://localhost:11434/api/chat");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_TIMEOUT => 120,
    CURLOPT_CONNECTTIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_errno($ch);

if ($curlError) {
    curl_close($ch);
    ob_clean();
    die(json_encode([
        'success' => false, 
        'error' => 'Ollama connection failed. Make sure Ollama is running (ollama serve).'
    ]));
}

curl_close($ch);

if ($httpCode !== 200) {
    ob_clean();
    die(json_encode([
        'success' => false, 
        'error' => 'Ollama returned HTTP ' . $httpCode . '. Check if the model is available.'
    ]));
}

$result = json_decode($response, true);

if (!isset($result['message']['content'])) {
    ob_clean();
    die(json_encode([
        'success' => false, 
        'error' => 'Invalid AI response format'
    ]));
}

$botReply = trim($result['message']['content']);

// Remove common repetitive phrases
$botReply = preg_replace('/^(Okay,? |Alright,? |Sure,? )?let\'?s talk (about |energy|saving).*?!/i', '', $botReply);
$botReply = preg_replace('/I\'?m Electripid.*?electricity\.?/i', '', $botReply);
$botReply = trim($botReply);

// Save bot response
try {
    $stmt = $conn->prepare("INSERT INTO CHATBOT (user_id, sender, message_text, timestamp) VALUES (?, 'bot', ?, NOW())");
    $stmt->bind_param("is", $user_id, $botReply);
    $stmt->execute();
} catch (Exception $e) {
    // Continue even if save fails
}

ob_clean();
die(json_encode([
    'success' => true,
    'reply' => $botReply
]));
?>