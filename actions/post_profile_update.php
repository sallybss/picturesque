<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';

if (empty($_SESSION['profile_id'])) { header('Location: ../auth/login.php'); exit; }
if (!isset($_POST['submit'])) { header('Location: ../profile_edit.php'); exit; }

$me   = (int)$_SESSION['profile_id'];
$name = trim($_POST['display_name'] ?? '');
$mail = trim($_POST['email'] ?? '');

if ($name === '') {
  set_flash('err','Display name is required.');
  header('Location: ../profile_edit.php'); exit;
}

/* optional avatar upload */
$avatarFile = $_FILES['avatar'] ?? null;
$avatarNameToSave = null;

if ($avatarFile && !empty($avatarFile['name'])) {
  $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
  if (!in_array($avatarFile['type'], $allowed)) {
    set_flash('err','Avatar must be JPG/PNG/GIF/WEBP.');
    header('Location: ../profile_edit.php'); exit;
  }
  if ($avatarFile['error'] !== UPLOAD_ERR_OK) {
    set_flash('err','Upload failed (code '.$avatarFile['error'].').');
    header('Location: ../profile_edit.php'); exit;
  }

  $rootDir    = dirname(__DIR__);
  $uploadDir  = $rootDir . '/uploads/';
  if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0775, true); }
  if (!is_writable($uploadDir)) {
    set_flash('err', 'Uploads folder not writable: '.$uploadDir);
    header('Location: ../profile_edit.php'); exit;
  }

  $ext = pathinfo($avatarFile['name'], PATHINFO_EXTENSION);
  $fname = 'avt_'.$me.'_'.time().'.'.$ext;
  $target = $uploadDir.$fname;

  if (!move_uploaded_file($avatarFile['tmp_name'], $target)) {
    set_flash('err','Could not save avatar.');
    header('Location: ../profile_edit.php'); exit;
  }

  $avatarNameToSave = $fname; // store filename only
}

/* update DB */
$conn = db();

if ($avatarNameToSave) {
  $stmt = $conn->prepare("UPDATE profiles SET display_name=?, email=?, avatar_photo=? WHERE profile_id=?");
  $stmt->bind_param('sssi', $name, $mail, $avatarNameToSave, $me);
} else {
  $stmt = $conn->prepare("UPDATE profiles SET display_name=?, email=? WHERE profile_id=?");
  $stmt->bind_param('ssi', $name, $mail, $me);
}

$stmt->execute();
$stmt->close(); $conn->close();

/* keep session display_name in sync for header/userbox */
$_SESSION['display_name'] = $name;

set_flash('ok','Profile updated!');
header('Location: ../profile.php');