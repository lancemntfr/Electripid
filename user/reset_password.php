<?php
session_start();
require_once __DIR__ . '/../connect.php';

if (
    !isset($_SESSION['fp_user_id']) ||
    !isset($_SESSION['fp_verified']) ||
    $_SESSION['fp_verified'] !== true
) {
    header("Location: forgot_password.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirm)) {
        $error = "All fields are required.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $user_id = $_SESSION['fp_user_id'];

        // Update password
        $stmt = $conn->prepare("
            UPDATE USER SET password=? WHERE user_id=?
        ");
        $stmt->bind_param("si", $hashed, $user_id);
        $stmt->execute();

        // Invalidate all reset codes
        $stmt = $conn->prepare("
            DELETE FROM VERIFICATION
            WHERE user_id=? AND password_reset_code IS NOT NULL
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Clear sessions
        unset($_SESSION['fp_user_id']);
        unset($_SESSION['fp_verified']);
        unset($_SESSION['fp_method']);

        header("Location: login.php?reset=success");
        exit;
    }
}
?>

<h2>Reset Password</h2>

<form method="POST">
    <label>New Password</label>
    <input type="password" name="password" required minlength="6">

    <label>Confirm Password</label>
    <input type="password" name="confirm_password" required minlength="6">

    <button type="submit">Change Password</button>
</form>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>
