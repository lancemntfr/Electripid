<?php
    session_start();
    require_once __DIR__ . '/../../../connect.php';

    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email']);

        $escaped_email = mysqli_real_escape_string($conn, $email);
        $result = executeQuery("SELECT user_id, cp_number FROM USER WHERE email='$escaped_email'");

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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Electripid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e3f2fd 0%, white 100%);
        }
        .reset-container {
            max-width: 500px;
            width: 100%;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <h2 class="mb-2">Reset Password</h2>
        <p class="text-muted mb-4">Enter your account email to continue.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Email address <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Continue</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
