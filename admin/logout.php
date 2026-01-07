<?php
session_start();

// Store the role before destroying session
$user_role = $_SESSION['role'] ?? 'user';

// Clear remember me cookie if exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Destroy all session data
session_unset();
session_destroy();

// Redirect based on previous role
if ($user_role === 'admin') {
    header('Location: ../user/login.php');
} else {
    header('Location: login.php');
}
exit;
?>