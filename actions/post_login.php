<?php
session_start();
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';

$email = trim($_POST['login_email'] ?? '');
$pass  = $_POST['password'] ?? '';

if ($email === '' || $pass === '') {
  set_flash('err', 'Please enter both email and password.');
  header('Location: ../auth/login.php'); exit;
}

$conn = db();
$stmt = $conn->prepare("
  SELECT profile_id, display_name, role, password_hash, status
  FROM profiles
  WHERE login_email = ?
  LIMIT 1
");
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$user || !password_verify($pass, $user['password_hash'])) {
  set_flash('err', 'Invalid email or password.');
  header('Location: ../auth/login.php'); exit;
}

if ($user['status'] !== 'active') {
  set_flash('err', 'Your account is not active.');
  header('Location: ../auth/login.php'); exit;
}

$_SESSION['profile_id']   = (int)$user['profile_id'];
$_SESSION['display_name'] = $user['display_name'];
$_SESSION['role']         = $user['role'];

set_flash('ok', 'Welcome back, ' . htmlspecialchars($user['display_name']) . '!');
header('Location: ../index.php'); exit;
