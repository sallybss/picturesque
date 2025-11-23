<?php
require_once __DIR__ . '/../../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../../contact.php');
}

if (!check_csrf($_POST['csrf'] ?? null)) {
    set_flash('err', 'Invalid request.');
    redirect('../../contact.php');
}

$me = Auth::requireUserOrRedirect('../auth/login.php');

$profiles = new ProfileRepository();
$meRow    = $profiles->getById($me);

$name  = trim($meRow['display_name'] ?? '');
$email = trim($meRow['email'] ?? $meRow['login_email'] ?? '');

$company = trim($_POST['company'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

$subject = mb_substr($subject, 0, 100);
$message = mb_substr($message, 0, 500);

if ($name === '' || $email === '' || $subject === '' || $message === '') {
    set_flash('err', 'Please fill out all required fields.');
    redirect('../../contact.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('err', 'Enter a valid email.');
    redirect('../../contact.php');
}

$ip   = $_SERVER['REMOTE_ADDR'] ?? null;
$repo = new ContactRepository();
$repo->create($me, $name, $email, $company, $subject, $message, $ip);

set_flash('ok', 'Thank you! Your message was sent.');
redirect('../../contact.php');
