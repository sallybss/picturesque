<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../contact.php');
  exit;
}

// CSRF validation
$csrf = $_POST['csrf'] ?? null;
if (!check_csrf($csrf)) {
  set_flash('err', 'Invalid request. Please try again.');
  header('Location: ../contact.php');
  exit;
}

// profile_id is optional (for guests)
$me = isset($_SESSION['profile_id']) ? (int)$_SESSION['profile_id'] : null;

// Collect data
$name    = trim($_POST['name'] ?? '');
$company = trim($_POST['company'] ?? '');
$email   = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $subject === '' || $message === '') {
  set_flash('err', 'Please fill in all required fields.');
  header('Location: ../contact.php');
  exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  set_flash('err', 'Please enter a valid email address.');
  header('Location: ../contact.php');
  exit;
}

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

// ✅ Success message
set_flash('ok', 'Thank you! Your message has been sent successfully.');

// Redirect back to the contact page
header('Location: ../contact.php');
exit;