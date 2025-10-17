<?php
// actions/update_credentials.php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['profile_id'])) { header('Location: ../auth/login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../profile_settings.php'); exit; }
if (!check_csrf($_POST['csrf'] ?? '')) { set_flash('err','Invalid CSRF.'); header('Location: ../profile_settings.php'); exit; }

$me = (int)$_SESSION['profile_id'];
$conn = db();

/* Load current user */
$stmt = $conn->prepare("SELECT login_email, password_hash FROM profiles WHERE profile_id=? LIMIT 1");
$stmt->bind_param('i', $me);
$stmt->execute();
$meRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$meRow) { set_flash('err','User not found.'); header('Location: ../profile_settings.php'); exit; }

$newEmail   = trim($_POST['login_email'] ?? '');
$curPass    = (string)($_POST['current_password'] ?? '');
$newPass    = (string)($_POST['new_password'] ?? '');
$confirm    = (string)($_POST['confirm_password'] ?? '');

if ($newEmail === '' || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
  set_flash('err','Please enter a valid email.');
  header('Location: ../profile_settings.php'); exit;
}

/* If changing password, validate inputs */
$changePassword = ($newPass !== '' || $confirm !== '');
if ($changePassword) {
  if (strlen($newPass) < 8) {
    set_flash('err','New password must be at least 8 characters.'); header('Location: ../profile_settings.php'); exit;
  }
  if ($newPass !== $confirm) {
    set_flash('err','Passwords do not match.'); header('Location: ../profile_settings.php'); exit;
  }
  if ($curPass === '' || !password_verify($curPass, $meRow['password_hash'])) {
    set_flash('err','Current password is incorrect.'); header('Location: ../profile_settings.php'); exit;
  }
}

/* If email changes, ensure it’s unique */
if (strcasecmp($newEmail, $meRow['login_email']) !== 0) {
  $stmt = $conn->prepare("SELECT 1 FROM profiles WHERE login_email=? AND profile_id<>? LIMIT 1");
  $stmt->bind_param('si', $newEmail, $me);
  $stmt->execute();
  $exists = (bool)$stmt->get_result()->fetch_row();
  $stmt->close();
  if ($exists) {
    set_flash('err','That email is already in use.'); header('Location: ../profile_settings.php'); exit;
  }
}

/* Build update */
if ($changePassword) {
  $hash = password_hash($newPass, PASSWORD_DEFAULT);
  $stmt = $conn->prepare("UPDATE profiles SET login_email=?, password_hash=? WHERE profile_id=?");
  $stmt->bind_param('ssi', $newEmail, $hash, $me);
} else {
  $stmt = $conn->prepare("UPDATE profiles SET login_email=? WHERE profile_id=?");
  $stmt->bind_param('si', $newEmail, $me);
}
$stmt->execute();
$stmt->close();
$conn->close();

set_flash('ok','Your settings have been updated.');
header('Location: ../profile_settings.php');
exit;