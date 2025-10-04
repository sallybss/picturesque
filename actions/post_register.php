<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';

$login_email  = trim($_POST['login_email'] ?? '');
$display_name = trim($_POST['display_name'] ?? '');
$password     = $_POST['password'] ?? '';

if ($login_email === '' || $display_name === '' || $password === '') {
  set_flash('err', 'Please fill all fields.');
  header('Location: ../auth/register.php'); exit;
}

try {
  $conn = db();

  // is email used?
  $chk = $conn->prepare('SELECT profile_id FROM profiles WHERE login_email = ?');
  $chk->bind_param('s', $login_email);
  $chk->execute(); $chk->store_result();
  if ($chk->num_rows > 0) { $chk->close(); set_flash('err','Email already registered.'); header('Location: ../auth/register.php'); exit; }
  $chk->close();

  $hash = password_hash($password, PASSWORD_DEFAULT);
  $ins  = $conn->prepare('INSERT INTO profiles (login_email, password_hash, display_name) VALUES (?, ?, ?)');
  $ins->bind_param('sss', $login_email, $hash, $display_name);
  $ins->execute();
  $_SESSION['profile_id'] = $ins->insert_id;
  $_SESSION['display_name'] = $display_name;
  $ins->close(); $conn->close();

  set_flash('ok','Account created. You are signed in.');
  header('Location: ../index.php'); exit;
} catch (Throwable $e) {
  set_flash('err','Database error: '.$e->getMessage());
  header('Location: ../auth/register.php'); exit;
}