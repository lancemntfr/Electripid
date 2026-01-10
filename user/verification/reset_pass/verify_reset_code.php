<?php
    session_start();
    require_once __DIR__ . '/../../../connect.php';

    $error = '';

    if (!isset($_SESSION['fp_user_id'])) {
        header("Location: forgot_password.php");
        exit;
    }

    $user_id = $_SESSION['fp_user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $code = trim($_POST['code']);

        $stmt = $conn->prepare("
            SELECT verification_id FROM VERIFICATION
            WHERE user_id=? 
            AND password_reset_code=? 
            AND expires_at > NOW()
        ");
        $stmt->bind_param("is", $user_id, $code);
        $stmt->execute();

        if ($stmt->get_result()->num_rows === 0) {
            $error = "Invalid or expired code.";
        } else {
            $_SESSION['fp_verified'] = true;
            header("Location: reset_password.php");
            exit;
        }
    }
?>

<h2>Enter Password Reset Code</h2>

<form method="POST">
    <input type="text" name="code" maxlength="6" pattern="\d{6}" required>
    <button type="submit">Verify</button>
</form>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>
