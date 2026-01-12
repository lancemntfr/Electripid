<?php
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/commands.php';

// 🔧 SMSGate sends these (check app settings)
$from = $_GET['from'] ?? '';
$message = trim($_GET['message'] ?? '');

if (!$from || !$message) exit;

handleSMSCommand($from, $message);
?>