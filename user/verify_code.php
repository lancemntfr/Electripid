<?php
    session_start();
    require_once __DIR__ . '/../connect.php';
    require_once __DIR__ . '/phpmailer.php';

    if (!isset($_SESSION['pending_user_id'])) {
        header('Location: register.php');
        exit;
    }

    $user_id = $_SESSION['pending_user_id'];
    $error = '';
    $success = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Check if user clicked verify
        if (isset($_POST['verify'])) {
            $code = trim($_POST['code']);

            if (empty($code)) {
                $error = "Please enter the verification code.";
            } else {
                $stmt = $conn->prepare("SELECT verification_id FROM VERIFICATION WHERE user_id=? AND verification_code=? AND is_verified=0 AND expires_at>NOW()");
                $stmt->bind_param("is", $user_id, $code);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 0) {
                    $error = "Invalid or expired code.";
                } else {
                    // Mark verified
                    $stmt = $conn->prepare("UPDATE VERIFICATION SET is_verified=1 WHERE user_id=?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();

                    // Activate account
                    $stmt = $conn->prepare("UPDATE USER SET acc_status='active' WHERE user_id=?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();

                    unset($_SESSION['pending_user_id']); // remove pending user
                    header("Location: login.php?verified=1");
                    exit;
                }
            }
        }

        // Check if user clicked resend
        if (isset($_POST['resend'])) {

            // Get user email
            $result = executeQuery("SELECT email FROM USER WHERE user_id='$user_id'");
            $user = mysqli_fetch_assoc($result);
            $email = $user['email'];

            // Generate new code
            $verification_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires_at = date("Y-m-d H:i:s", strtotime("+15 minutes"));

            // Update VERIFICATION table
            $stmt = $conn->prepare("UPDATE VERIFICATION SET verification_code=?, expires_at=?, is_verified=0 WHERE user_id=?");
            $stmt->bind_param("ssi", $verification_code, $expires_at, $user_id);
            $stmt->execute();

            // Send email
            sendVerificationEmail($email, $verification_code);
            $success = "A new verification code has been sent to your email.";
        }
    }
?>

<h2>Verify Your Email</h2>

<?php if(!empty($error)): ?>
    <div style="color:red; margin-top:10px;"><?= $error ?></div>
<?php endif; ?>

<?php if(!empty($success)): ?>
    <div style="color:green; margin-top:10px;"><?= $success ?></div>
<?php endif; ?>

<form method="POST" style="margin-top:15px;">
    <label>Enter 6-digit verification code:</label>
    <input type="text" name="code" maxlength="6" pattern="\d{6}" required>
    <button type="submit" name="verify">Verify</button>
</form>

<form method="POST" style="margin-top:10px;">
    <button type="submit" name="resend">Resend Code</button>
</form>
