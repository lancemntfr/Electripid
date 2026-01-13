<?php
    require_once __DIR__ . '/../../../connect.php';
    require_once __DIR__ . '/commands.php';

    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);

    if (!isset($data['from'], $data['text'])) {
        http_response_code(400);
        exit;
    }

    $from    = trim($data['from']);
    $message = trim($data['text']);

    if (strpos($from, '09') === 0) {
        $from = '+63' . substr($from, 1);
    }

    $lockFile = sys_get_temp_dir() . '/sms_lock_' . md5($from);
    if (file_exists($lockFile) && (time() - filemtime($lockFile)) < 5) {
        exit;
    }
    touch($lockFile);

    handleSMSCommand($from, $message);

    echo "OK";
?>