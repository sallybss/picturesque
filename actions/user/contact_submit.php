<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../libs/PHPMailer/src/SMTP.php';

$mailConfig = require __DIR__ . '/../../includes/core/mail_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../../contact.php');
}

if (!check_csrf($_POST['csrf'] ?? null)) {
    set_flash('err', 'Invalid request.');
    redirect('../../contact.php');
}

$me = Auth::requireUserOrRedirect('../../auth/login.php');

$now      = time();
$COOLDOWN = 180;

$lastSent = (int)($_SESSION['contact_last_sent'] ?? 0);

if ($lastSent > 0 && ($now - $lastSent) < $COOLDOWN) {
    $remaining = $COOLDOWN - ($now - $lastSent);

    $_SESSION['contact_rate_limit_until'] = $now + $remaining;

    $mins = max(1, ceil($remaining / 60));
    set_flash(
        'err',
        'You can only send one message every 3 minutes. Please wait about ' . $mins . ' minute(s) and try again.'
    );
    redirect('../../contact.php');
}

$profiles = new ProfileRepository();
$meRow    = $profiles->getById($me);

$name  = trim($meRow['display_name'] ?? '');
$email = trim($meRow['email'] ?? ($meRow['login_email'] ?? ''));

$company = mb_substr(trim($_POST['company'] ?? ''), 0, 100);
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

$subject = str_replace(["\r", "\n"], ' ', $subject);

$subject = mb_substr($subject, 0, 100);
$message = mb_substr($message, 0, 500);


if ($subject === '' || $message === '') {
    set_flash('err', 'Please fill out all required fields.');
    redirect('../../contact.php');
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('err', 'Enter a valid email.');
    redirect('../../contact.php');
}

$ip   = $_SERVER['REMOTE_ADDR'] ?? null;
$repo = new ContactRepository();
$repo->create($me, $name, $email, $company, $subject, $message, $ip);

$_SESSION['contact_last_sent'] = $now;
unset($_SESSION['contact_rate_limit_until']);

$smtpHost  = $mailConfig['smtp_host']  ?? '';
$smtpUser  = $mailConfig['smtp_user']  ?? '';
$smtpPass  = $mailConfig['smtp_pass']  ?? '';
$smtpPort  = $mailConfig['smtp_port']  ?? 587;
$toAddress = $mailConfig['to_address'] ?? $smtpUser;

$fromEmail = $smtpUser;
$fromName  = 'Picturesque Contact Form';

if ($smtpHost && $smtpUser && $smtpPass && $toAddress) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtpPort;

        $mail->setFrom($fromEmail, $fromName);

        $mail->addAddress($toAddress);

        if ($email !== '') {
            $mail->addReplyTo($email, $name ?: $email);
        }

        $mail->Subject = 'Picturesque contact form: ' . $subject;

        $body  = "New contact form submission from Picturesque\n\n";
        $body .= "Name:    {$name}\n";
        $body .= "Email:   {$email}\n";
        if ($company !== '') {
            $body .= "Company: {$company}\n";
        }
        if ($ip) {
            $body .= "IP:      {$ip}\n";
        }
        $body .= "\nMessage:\n{$message}\n";

        $mail->Body = $body;

        $mail->send();
        set_flash('ok', 'Thank you! Your message was sent.');
    } catch (Exception $e) {
        error_log('PHPMailer error: ' . $mail->ErrorInfo);
        set_flash('err', 'Your message was saved, but could not be emailed right now.');
    }
} else {
    set_flash('ok', 'Thank you! Your message was saved.');
}

redirect('../../contact.php');