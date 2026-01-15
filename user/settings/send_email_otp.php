<?php
    session_start();
    header('Content-Type: application/json');
    
    require_once __DIR__ . '/../../connect.php';
    require_once __DIR__ . '/../verification/email/phpmailer.php';
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
        exit;
    }
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['email'])) {
        echo json_encode(['success' => false, 'error' => 'Email is required']);
        exit;
    }
    
    $email = trim($data['email']);
    $user_id = $_SESSION['user_id'];
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Invalid email format']);
        exit;
    }
    
    // Generate 6-digit verification code
    $verification_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires_at = date("Y-m-d H:i:s", strtotime("+15 minutes"));
    
    // Delete old unverified email OTPs for this user
    $delete_stmt = $conn->prepare("DELETE FROM VERIFICATION WHERE user_id=? AND verification_type='email' AND is_verified=0");
    $delete_stmt->bind_param("i", $user_id);
    $delete_stmt->execute();
    
    // Insert new verification code
    $stmt = $conn->prepare("INSERT INTO VERIFICATION (user_id, verification_type, verification_code, expires_at) VALUES (?, 'email', ?, ?)");
    $stmt->bind_param("iss", $user_id, $verification_code, $expires_at);
    
    if ($stmt->execute()) {
        // Send email
        $type = 'verification';
        $email_sent = sendVerificationEmail($email, $verification_code, $type);
        
        if ($email_sent) {
            echo json_encode(['success' => true, 'message' => 'Verification code sent to your email']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to send email. Please try again.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create verification code']);
    }
?>
