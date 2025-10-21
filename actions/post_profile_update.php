<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db_class.php';
require __DIR__ . '/../includes/auth_class.php';
require __DIR__ . '/../includes/profile_repository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../profile_edit.php'); exit; }

$me   = Auth::requireUserOrRedirect('../auth/login.php');
$name = trim($_POST['display_name'] ?? '');
$mail = trim($_POST['email'] ?? '');
$file = $_FILES['avatar'] ?? null;

if ($name === '') {
  set_flash('err', 'Display name is required.');
  header('Location: ../profile_edit.php'); exit;
}

if ($mail !== '' && !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
  set_flash('err', 'Enter a valid email or leave it blank.');
  header('Location: ../profile_edit.php'); exit;
}

$newAvatar = null;
if ($file && !empty($file['name'])) {
  $ok = ['image/jpeg','image/png','image/webp','image/gif'];
  if (!in_array($file['type'] ?? '', $ok, true)) { set_flash('err','Only JPG, PNG, WEBP or GIF.'); header('Location: ../profile_edit.php'); exit; }
  if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { set_flash('err','Upload failed.'); header('Location: ../profile_edit.php'); exit; }

  $dir = dirname(__DIR__) . '/uploads/';
  if (!is_dir($dir)) { mkdir($dir, 0775, true); }

  $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  $nameFile = 'avt_' . $me . '_' . time() . '.' . $ext;
  if (!move_uploaded_file($file['tmp_name'], $dir . $nameFile)) {
    set_flash('err','Could not save avatar.');
    header('Location: ../profile_edit.php'); exit;
  }
  $newAvatar = $nameFile;
}

$profiles = new ProfileRepository();

if ($mail !== '' && $profiles->emailInUseByOther($mail, $me)) {
  set_flash('err','That email is already in use.');
  header('Location: ../profile_edit.php'); exit;
}

$profiles->updateProfileWithEmailAndAvatar($me, $name, ($mail === '' ? null : $mail), $newAvatar);

$_SESSION['display_name'] = $name;

set_flash('ok', 'Profile updated!');
header('Location: ../profile.php'); exit;
