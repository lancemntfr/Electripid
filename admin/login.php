<?php
ini_set('session.cookie_path', '/');
session_start();

require_once '../connect.php';

$error_message = '';

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: /admin/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error_message = 'Please fill in all required fields.';
    } else {

        $stmt = $conn->prepare(
            "SELECT * FROM USER 
             WHERE email = ? 
             AND role = 'admin' 
             AND acc_status = 'active'"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($admin = $result->fetch_assoc()) {

            if (password_verify($password, $admin['password'])) {

                session_regenerate_id(true);

                $_SESSION['user_id'] = $admin['id'] ?? 0;
                $_SESSION['fname']   = $admin['fname'];
                $_SESSION['lname']   = $admin['lname'];
                $_SESSION['email']   = $admin['email'];
                $_SESSION['role']    = 'admin';

                header('Location: /admin/dashboard.php');
                exit;
            }
        }

        $error_message = 'Invalid admin credentials.';
    }
}



<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="../assets/css/user.css">
</head>

<body class="min-vh-100 d-flex align-items-center justify-content-center p-3">

<div class="login-card bg-white p-5 w-100" style="max-width: 500px;">
    <div class="text-center mb-4">
        <div class="logo-icon rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4 text-white bg-dark">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <h2 class="text-dark">Admin Panel</h2>
        <p class="text-muted">Sign in as system administrator</p>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" required placeholder="admin@electripid.com">
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required placeholder="admin123">
        </div>

        <button type="submit" class="btn btn-dark w-100 py-2">
            <i class="bi bi-shield-lock me-2"></i> Login as Admin
        </button>
    </form>

    <div class="text-center mt-3">
        <a href="../user/login.php" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Back to User Login
        </a>
    </div>
</div>

</body>
</html>
