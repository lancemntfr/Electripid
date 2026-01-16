<?php
session_start();

// Clear remember me cookie if exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Destroy all session data
session_unset();
session_destroy();

// Always redirect to the unified login page
header('Location: ../user/login.php');
exit;
?>