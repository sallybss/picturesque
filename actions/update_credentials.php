<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';

if (empty($_SESSION['profile_id'])) { header('Location: ../auth/login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../profile_settings.php'); exit; }
if (!check_csrf($_POST['csrf'] ?? null)) { set_flash('err','Invalid request.'); header('Location: ../profile_settings.php'); exit; }

$me = (int)$_SESSION['profile_id'];
$email = trim($_POST['login_email'] ?? '');
$cur   = (string)($_POST['current_password'] ?? '');
$pass  = (string)($_POST['new_password'] ?? '');
$conf  = (string)($_POST['confirm_password'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  set_flash('err','Enter a valid email.'); header('Location: ../profile_settings.php'); exit;
}

$conn = db();

$stmt = $conn->prepare('SELECT login_email, password_hash FROM profiles WHERE profile_id=? LIMIT 1');
$stmt->bind_param('i', $me);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) { set_flash('err','User not found.'); header('Location: ../profile_settings.php'); exit; }

if (strcasecmp($email, $user['login_email']) !== 0) {
  $chk = $conn->prepare('SELECT 1 FROM profiles WHERE login_email=? AND profile_id<>? LIMIT 1');
  $chk->bind_param('si', $email, $me);
  $chk->execute();
  $taken = (bool)$chk->get_result()->fetch_row();
  $chk->close();
  if ($taken) { set_flash('err','Email already in use.'); header('Location: ../profile_settings.php'); exit; }
}

$changePwd = ($pass !== '' || $conf !== '');

if ($changePwd) {
  if (strlen($pass) < 8) { set_flash('err','Password must be at least 8 chars.'); header('Location: ../profile_settings.php'); exit; }
  if ($pass !== $conf)   { set_flash('err','Passwords do not match.'); header('Location: ../profile_settings.php'); exit; }
  if ($cur === '' || !password_verify($cur, $user['password_hash'])) {
    set_flash('err','Current password is incorrect.'); header('Location: ../profile_settings.php'); exit;
  }
}

if ($changePwd) {
  $hash = password_hash($pass, PASSWORD_DEFAULT);
  $upd = $conn->prepare('UPDATE profiles SET login_email=?, password_hash=? WHERE profile_id=?');
  $upd->bind_param('ssi', $email, $hash, $me);
} else {
  $upd = $conn->prepare('UPDATE profiles SET login_email=? WHERE profile_id=?');
  $upd->bind_param('si', $email, $me);
}
$upd->execute();
$upd->close();
$conn->close();

set_flash('ok','Settings updated.');
header('Location: ../profile_settings.php'); exit;
