<?php
require_once __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../auth/register.php'); exit; }

$email = trim($_POST['login_email'] ?? '');
$name  = trim($_POST['display_name'] ?? '');
$pass  = (string)($_POST['password'] ?? '');

if (!check_csrf($_POST['csrf'] ?? null)) { set_flash('err','Invalid request.'); header('Location: ../auth/register.php'); exit; }

if ($email === '' || $name === '' || $pass === '') {
  set_flash('err', 'Please fill all fields.');
  header('Location: ../auth/register.php'); exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  set_flash('err', 'Enter a valid email.');
  header('Location: ../auth/register.php'); exit;
}
if (strlen($pass) < 8) {
  set_flash('err', 'Password must be at least 8 characters.');
  header('Location: ../auth/register.php'); exit;
}

$profiles = new ProfileRepository();

if ($profiles->loginEmailExists($email)) {
  set_flash('err', 'Email already registered.');
  header('Location: ../auth/register.php'); exit;
}

$hash = password_hash($pass, PASSWORD_DEFAULT);
$newId = $profiles->createUser($email, $name, $hash);

$_SESSION['profile_id']   = $newId;
$_SESSION['display_name'] = $name;
$_SESSION['role']         = 'user';

set_flash('ok', 'Account created. You are signed in.');
header('Location: ../index.php'); exit;
