<?php
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require_once __DIR__ . '/../phpmailer/src/Exception.php';
    require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/../phpmailer/src/SMTP.php';

    function sendVerificationEmail($email, $code) {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username = getenv('EMAIL_USER');
            $mail->Password = getenv('EMAIL_APP_PASSWORD');
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom(getenv('EMAIL_USER'), 'Electripid');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Electripid Verification Code';
            $mail->Body    = "
                <h3>Welcome to Electripid</h3>
                <p>Please enter the following 6-digit verification code on the website to verify your account:</p>
                <h2 style='letter-spacing:5px;'>$code</h2>
                <p>This code expires in 15 minutes.</p>
            ";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email could not be sent: {$mail->ErrorInfo}");
            return false;
        }
    }
?>