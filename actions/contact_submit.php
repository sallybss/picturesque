<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../contact.php'); exit; }
if (!csrf_check($_POST['csrf'] ?? null))  { set_flash('err','Invalid request.'); header('Location: ../contact.php'); exit; }

$me      = isset($_SESSION['profile_id']) ? (int)$_SESSION['profile_id'] : null;
$name    = trim($_POST['name']    ?? '');
$company = trim($_POST['company'] ?? '');
$email   = trim($_POST['email']   ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $subject === '' || $message === '') { set_flash('err','Please fill all required fields.'); header('Location: ../contact.php'); exit; }
if (!filter_var($email, FILTER_VALIDATE_EMAIL))                           { set_flash('err','Enter a valid email.');           header('Location: ../contact.php'); exit; }

$ip = $_SERVER['REMOTE_ADDR'] ?? null;

$conn = db();
$stmt = $conn->prepare("
  INSERT INTO contact_messages (profile_id, name, email, company, subject, message, ip)
  VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param('issssss', $me, $name, $email, $company, $subject, $message, $ip);
$stmt->execute();
$stmt->close();
$conn->close();

set_flash('ok','Thank you! Your message was sent.');
header('Location: ../contact.php'); exit;
