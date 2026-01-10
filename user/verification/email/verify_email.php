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
        $pending_data = $_SESSION['pending_registration'];

        // Check if user clicked verify
        if (isset($_POST['verify'])) {
            $code = trim($_POST['code']);

            if (empty($code)) {
                $error = "Please enter the verification code.";
            } else {
                // Check if code matches and is not expired
                if ($code === $pending_data['verification_code'] && time() < $pending_data['expires_at']) {
                    
                    $fname = mysqli_real_escape_string($conn, $pending_data['fname']);
                    $lname = mysqli_real_escape_string($conn, $pending_data['lname']);
                    $email = mysqli_real_escape_string($conn, $pending_data['email']);
                    $cp_number = mysqli_real_escape_string($conn, $pending_data['cp_number']);
                    $city = mysqli_real_escape_string($conn, $pending_data['city']);
                    $barangay = mysqli_real_escape_string($conn, $pending_data['barangay']);
                    $password = $pending_data['password']; // Already hashed
                    $provider_id = intval($pending_data['provider_id']);

                    $insert_user_query = "INSERT INTO USER (fname, lname, email, cp_number, city, barangay, password, role, acc_status) VALUES ('$fname', '$lname', '$email', '$cp_number', '$city', '$barangay', '$password', 'user', 'active')";
                    $user_result = executeQuery($insert_user_query);

                    if ($user_result) {
                        $user_id = mysqli_insert_id($conn);

                        // Create household
                        $insert_household_query = "INSERT INTO HOUSEHOLD (user_id, provider_id) VALUES ('$user_id', '$provider_id')";
                        executeQuery($insert_household_query);

                        // Clear pending registration session
                        unset($_SESSION['pending_registration']);

                        header("Location: ../../login.php?verified=1");
                        exit;
                    } else {
                        $error = "Account creation failed. Please try again.";
                    }
                } else {
                    $error = "Invalid or expired code.";
                }
            }
        }

        // Check if user clicked resend
        if (isset($_POST['resend'])) {
            $result = resendVerificationCode();
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
    
    // Refresh pending_data at the end to ensure we have latest data
    $pending_data = $_SESSION['pending_registration'];
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
