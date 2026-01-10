<?php
    session_start();

    if (!isset($_SESSION['fp_user_id'])) {
        header("Location: forgot_password.php");
        exit;
    }

    $hasPhone = $_SESSION['fp_has_phone'];
?>

<h2>Reset Password</h2>
<p>Choose how you want to receive your verification code:</p>

<form method="POST" action="send_reset_code.php">
    <button type="submit" name="method" value="email">📧 Email</button>

    <?php if ($hasPhone): ?>
        <button type="submit" name="method" value="sms">📱 SMS</button>
    <?php endif; ?>
</form>
