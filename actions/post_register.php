<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../auth/register.php'); exit;
}

$email = trim($_POST['login_email'] ?? '');
$name  = trim($_POST['display_name'] ?? '');
$pass  = $_POST['password'] ?? '';

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

$conn = db();
/* check for existing email */
$chk = $conn->prepare('SELECT profile_id FROM profiles WHERE login_email=?');
$chk->bind_param('s', $email);
$chk->execute(); $chk->store_result();
if ($chk->num_rows > 0) {
  $chk->close(); $conn->close();
  set_flash('err', 'Email already registered.');
  header('Location: ../auth/register.php'); exit;
}
$chk->close();

/* create account */
$hash = password_hash($pass, PASSWORD_DEFAULT);
$ins  = $conn->prepare('INSERT INTO profiles (login_email, password_hash, display_name, role, status) VALUES (?, ?, ?, "user", "active")');
$ins->bind_param('sss', $email, $hash, $name);
$ins->execute();

$_SESSION['profile_id']   = (int)$ins->insert_id;
$_SESSION['display_name'] = $name;
$_SESSION['role']         = 'user';

$ins->close();
$conn->close();

set_flash('ok', 'Account created. You are signed in.');
header('Location: ../index.php'); exit;
