<?php
    /**
     * Function to resend verification code for pending registration
     * Requires: session must be started and phpmailer.php must be included
     * @return array Returns array with 'success' boolean and 'message' string
     */
    function resendVerificationCode() {
        if (isset($_SESSION['pending_registration'])) {
            // Handle registration resend
            $pending_data = $_SESSION['pending_registration'];

            // Generate new 6-digit code
            $new_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires_at = date("Y-m-d H:i:s", strtotime("+15 minutes"));

            // Update database with new code if verification_id exists
            if (isset($pending_data['verification_id'])) {
                $verification_id = $pending_data['verification_id'];
                $stmt = $GLOBALS['conn']->prepare("UPDATE VERIFICATION SET verification_code=?, expires_at=? WHERE verification_id=? AND is_verified=0");
                $stmt->bind_param("ssi", $new_code, $expires_at, $verification_id);
                $stmt->execute();
            }

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
        } elseif (isset($_SESSION['email_verification'])) {
            // Handle existing user email verification resend
            $user_id = $_SESSION['email_verification']['user_id'];
            $email = $_SESSION['email_verification']['email'];

            // Generate new 6-digit code
            $new_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires_at = date("Y-m-d H:i:s", strtotime("+15 minutes"));

            // Update database with new code
            $stmt = $GLOBALS['conn']->prepare("UPDATE VERIFICATION SET verification_code=?, expires_at=? WHERE user_id=? AND verification_type='email' AND is_verified=0");
            $stmt->bind_param("ssi", $new_code, $expires_at, $user_id);
            $stmt->execute();

            // Update session with new code
            $_SESSION['email_verification']['verification_code'] = $new_code;
            $_SESSION['email_verification']['expires_at'] = $expires_at;

            $type = 'verification';
            $email_sent = sendVerificationEmail($email, $new_code, $type);

            if ($email_sent) {
                return ['success' => true, 'message' => 'A new verification code has been sent to your email.'];
            } else {
                return ['success' => false, 'message' => 'Failed to send verification email. Please try again.'];
            }
        } else {
            return ['success' => false, 'message' => 'No verification session found.'];
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
