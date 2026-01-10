<?php
    session_start();
    require_once __DIR__ . '/../../../connect.php';

    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email']);

        $stmt = $conn->prepare("SELECT user_id, cp_number FROM USER WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = "Email not found.";
        } else {
            $user = $result->fetch_assoc();
            $_SESSION['fp_user_id'] = $user['user_id'];
            $_SESSION['fp_email'] = $email;
            $_SESSION['fp_has_phone'] = !empty($user['cp_number']);

            header("Location: choose_reset_method.php");
            exit;
        }
    }
?>

<h2>Forgot Password</h2>

<form method="POST">
    <input type="email" name="email" placeholder="Enter your email" required>
    <button type="submit">Continue</button>
</form>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>
