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
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['email']) || empty($data['code'])) {
        echo json_encode(['success' => false, 'error' => 'Email and code are required']);
        exit;
    }
    
    $email = trim($data['email']);
    $code = trim($data['code']);
    $user_id = $_SESSION['user_id'];
    
    // Verify code
    $stmt = $conn->prepare("SELECT verification_id, expires_at FROM VERIFICATION WHERE user_id=? AND verification_type='email' AND verification_code=? AND is_verified=0");
    $stmt->bind_param("is", $user_id, $code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        
        if (strtotime($row['expires_at']) >= time()) {
            // Valid code - mark as verified
            $verification_id = $row['verification_id'];
            $update_stmt = $conn->prepare("UPDATE VERIFICATION SET is_verified=1 WHERE verification_id=?");
            $update_stmt->bind_param("i", $verification_id);
            $update_stmt->execute();
            
            // Update user email if different
            $current_email_query = "SELECT email FROM USER WHERE user_id = ?";
            $current_email_stmt = $conn->prepare($current_email_query);
            $current_email_stmt->bind_param("i", $user_id);
            $current_email_stmt->execute();
            $current_email_result = $current_email_stmt->get_result();
            
            if ($current_email_result->num_rows > 0) {
                $current_user = $current_email_result->fetch_assoc();
                if ($current_user['email'] !== $email) {
                    // Update email in USER table
                    $email_escaped = mysqli_real_escape_string($conn, $email);
                    $user_id_escaped = mysqli_real_escape_string($conn, $user_id);
                    $update_email_query = "UPDATE USER SET email = '$email_escaped' WHERE user_id = '$user_id_escaped'";
                    executeQuery($update_email_query);
                    
                    // Update session
                    $_SESSION['email'] = $email;
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Email verified successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Verification code expired']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid verification code']);
    }
?>
