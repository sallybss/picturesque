<?php
session_start();
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db_class.php';
require __DIR__ . '/../includes/profile_repository.php';

$email = trim($_POST['login_email'] ?? '');
$pass  = $_POST['password'] ?? '';

if (!check_csrf($_POST['csrf'] ?? null)) { set_flash('err','Invalid request.'); header('Location: ../auth/login.php'); exit; }

if ($email === '' || $pass === '') {
  set_flash('err', 'Please enter both email and password.');
  header('Location: ../auth/login.php'); exit;
}

$profiles = new ProfileRepository();
$user = $profiles->findAuthByEmail($email);

if (!$user || !password_verify($pass, $user['password_hash'])) {
  set_flash('err', 'Invalid email or password.');
  header('Location: ../auth/login.php'); exit;
}

if (($user['status'] ?? '') !== 'active') {
  set_flash('err', 'Your account is not active.');
  header('Location: ../auth/login.php'); exit;
}

$_SESSION['profile_id']   = (int)$user['profile_id'];
$_SESSION['display_name'] = (string)$user['display_name'];
$_SESSION['role']         = (string)$user['role'];

set_flash('ok', 'Welcome back, ' . htmlspecialchars($user['display_name']) . '!');
header('Location: ../index.php'); exit;
