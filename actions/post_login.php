<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';

$email = trim($_POST['login_email'] ?? '');
$pass  = $_POST['password'] ?? '';

if ($email === '' || $pass === '') {
  set_flash('err','Please enter email and password.');
  header('Location: ../auth/login.php'); exit;
}

try {
  $conn = db();
  $stmt = $conn->prepare('SELECT profile_id, display_name, password_hash FROM profiles WHERE login_email = ?');
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  $stmt->close(); $conn->close();

  if (!$row || !password_verify($pass, $row['password_hash'])) {
    set_flash('err','Invalid email or password.');
    header('Location: ../auth/login.php'); exit;
  }

  $_SESSION['profile_id']   = (int)$row['profile_id'];
  $_SESSION['display_name'] = $row['display_name'];
  header('Location: ../index.php'); exit;
} catch (Throwable $e) {
  set_flash('err','Database error: '.$e->getMessage());
  header('Location: ../auth/login.php'); exit;
}