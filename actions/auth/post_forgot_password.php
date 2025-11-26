<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../includes/init.php';

require_once __DIR__ . '/../../libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../libs/PHPMailer/src/SMTP.php';

$mailConfig = require __DIR__ . '/../../includes/core/mail_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../../auth/forgot_password.php');
}

if (!check_csrf($_POST['csrf'] ?? null)) {
    set_flash('err', 'Invalid request.');
    redirect('../../auth/forgot_password.php');
}

$email = trim($_POST['login_email'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('ok', 'If that email exists, a reset link has been sent.');
    redirect('../../auth/forgot_password.php');
}

$profiles = new ProfileRepository();
$user = $profiles->findAuthByEmail($email); 

if (!$user) {
    set_flash('ok', 'If that email exists, a reset link has been sent.');
    redirect('../../auth/forgot_password.php');
}

// Create token
$token = bin2hex(random_bytes(32));
$resets = new PasswordResetRepository();
$resets->createToken((int)$user['profile_id'], $token, 3600); // it is valid for 1 hour

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

// If you have BASE_PATH set in env.php (e.g. https://picturesque.dk), use it;
// otherwise fall back to localhost development URL.
if (defined('BASE_PATH') && BASE_PATH !== '') {
    $base = rtrim(BASE_PATH, '/');
} else {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = $scheme . '://' . $host . '/picturesque/picturesque';
}

$resetLink = $base . '/auth/reset_password.php?token=' . urlencode($token);

$smtpHost  = $mailConfig['smtp_host']  ?? '';
$smtpUser  = $mailConfig['smtp_user']  ?? '';
$smtpPass  = $mailConfig['smtp_pass']  ?? '';
$smtpPort  = $mailConfig['smtp_port']  ?? 587;

if ($smtpHost && $smtpUser && $smtpPass) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtpPort;

        $mail->setFrom($smtpUser, 'Picturesque');
        $mail->addAddress($email);

        $mail->Subject = 'Reset your Picturesque password';
        $mail->Body    =
"Hi,

We received a request to reset your password for your Picturesque account.

To choose a new password, click the link below (valid for 1 hour):

{$resetLink}

If you did not request this, you can safely ignore this email.

- Picturesque";

        $mail->send();
    } catch (Exception $e) {
        error_log('PHPMailer forgot password error: ' . $mail->ErrorInfo);
    }
}

set_flash('ok', 'If that email exists, a reset link has been sent.');
redirect('../../auth/forgot_password.php');