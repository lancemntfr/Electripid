<?php
    /**
     * Function to resend verification code for pending registration
     * Requires: session must be started and phpmailer.php must be included
     * @return array Returns array with 'success' boolean and 'message' string
     */
    function resendVerificationCode() {
        if (!isset($_SESSION['pending_registration'])) {
            return ['success' => false, 'message' => 'No registration pending verification.'];
        }

        $pending_data = $_SESSION['pending_registration'];

        // Generate new 6-digit code
        $new_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires_at = date("Y-m-d H:i:s", strtotime("+15 minutes"));

        // Update session with new code
        $_SESSION['pending_registration']['verification_code'] = $new_code;
        $_SESSION['pending_registration']['expires_at'] = $expires_at;

        $type = 'verification';
        $email_sent = sendVerificationEmail($pending_data['email'], $new_code, $type);

        if ($email_sent) {
            return ['success' => true, 'message' => 'A new verification code has been sent to your email.'];
        } else {
            return ['success' => false, 'message' => 'Failed to send verification email. Please try again.'];
        }
    }

    if (basename($_SERVER['PHP_SELF']) === 'resend_code.php' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        session_start();
        require_once __DIR__ . '/../../../connect.php';
        require_once __DIR__ . '/phpmailer.php';
        
        $result = resendVerificationCode();
        echo $result['message'];
        exit;
    }
?>
