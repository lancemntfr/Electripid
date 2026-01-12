<?php
    session_start();
    require_once __DIR__ . '/../../../connect.php';
    require_once __DIR__ . '/phpmailer.php';
    require_once __DIR__ . '/resend_code.php';

    if (!isset($_SESSION['pending_registration'])) {
        header('Location: ../../register.php');
        exit;
    }

    $error = '';
    $success = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Verify button clicked
        if (isset($_POST['verify'])) {
            $code = trim($_POST['code']);

            if (empty($code)) {
                $error = "Please enter the verification code.";
            } else {
                // Check code in VERIFICATION table
                $stmt = $conn->prepare("SELECT verification_id, expires_at FROM VERIFICATION WHERE verification_code=? AND verification_type='email' AND is_verified=0");
                $stmt->bind_param("s", $code);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $row = $result->fetch_assoc();
                    if (strtotime($row['expires_at']) >= time()) {
                        // Valid → insert user
                        $pending_data = $_SESSION['pending_registration'];

                        $stmt_insert = $conn->prepare("INSERT INTO USER (fname, lname, email, cp_number, city, barangay, password, role, acc_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'user', 'active')");
                        $stmt_insert->bind_param(
                            "sssssss",
                            $pending_data['fname'],
                            $pending_data['lname'],
                            $pending_data['email'],
                            $pending_data['cp_number'],
                            $pending_data['city'],
                            $pending_data['barangay'],
                            $pending_data['password']
                        );
                        $stmt_insert->execute();
                        $user_id = $conn->insert_id;

                        // Update VERIFICATION table
                        $stmt_update = $conn->prepare("UPDATE VERIFICATION SET user_id=?, is_verified=1 WHERE verification_id=?");
                        $stmt_update->bind_param("ii", $user_id, $row['verification_id']);
                        $stmt_update->execute();

                        // Clear session
                        unset($_SESSION['pending_registration']);

                        header("Location: ../../login.php?verified=1");
                        exit;

                    } else {
                        $error = "Verification code expired.";
                    }
                } else {
                    $error = "Invalid verification code.";
                }
            }
        }

        // Resend button clicked
        if (isset($_POST['resend'])) {
            $result = resendVerificationCode();
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Verify Email</title>
</head>
<body>
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
</body>
</html>
