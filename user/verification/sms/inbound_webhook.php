<?php
    require_once __DIR__ . '/../../../connect.php';
    require_once __DIR__ . '/commands.php';

    $raw  = file_get_contents("php://input");
    $data = json_decode($raw, true);

    if (!isset($data['from'], $data['text'])) {
        exit;
    }

    $from    = trim($data['from']);
    $message = trim($data['text']);

    if (strpos($from, '09') === 0) {
        $from = '+63' . substr($from, 1);
    }

    // 🚫 Prevent instant self-reply loops (3 seconds window)
    $lockFile = sys_get_temp_dir() . '/sms_' . md5($from);
    $now = time();

    if (file_exists($lockFile) && ($now - filemtime($lockFile)) < 3) {
        exit;
    }

    // Update lock
    touch($lockFile);

    // Hand over to command handler
    handleSMSCommand($from, $message);

    echo "OK";
?>