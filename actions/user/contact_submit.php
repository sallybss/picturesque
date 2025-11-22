<?php
require_once __DIR__ . '/../../includes/init.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../contact.php'); exit; }
if (!check_csrf($_POST['csrf'] ?? null))  { set_flash('err','Invalid request.'); header('Location: ../contact.php'); exit; }

$me = Auth::requireUserOrRedirect('../auth/login.php');

$name = trim($meRow['display_name']);
$email = trim($meRow['email']);
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');
$subject = mb_substr($subject, 0, 100);
$message = mb_substr($message, 0, 500);
if ($subject === '' || $message === '') {
    set_flash('err', 'Please fill out all required fields.');
    redirect('../../contact.php');
    exit;
}

if ($name === '' || $email === '' || $subject === '' || $message === '') { set_flash('err','Please fill all required fields.'); header('Location: ../contact.php'); exit; }
if (!filter_var($email, FILTER_VALIDATE_EMAIL))                           { set_flash('err','Enter a valid email.');           header('Location: ../contact.php'); exit; }

$ip = $_SERVER['REMOTE_ADDR'] ?? null;

$repo = new ContactRepository();
$repo->create($me, $name, $email, $company, $subject, $message, $ip);

set_flash('ok','Thank you! Your message was sent.');
header('Location: ../contact.php'); 
exit;
