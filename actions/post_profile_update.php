<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';

if (empty($_SESSION['profile_id'])) { header('Location: ../auth/login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../profile_edit.php'); exit; }

$me   = (int)$_SESSION['profile_id'];
$name = trim($_POST['display_name'] ?? '');
$mail = trim($_POST['email'] ?? '');          
$file = $_FILES['avatar'] ?? null;

if ($name === '') {
  set_flash('err', 'Display name is required.');
  header('Location: ../profile_edit.php'); exit;
}

if ($mail !== '') {
  if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
    set_flash('err', 'Enter a valid email or leave it blank.'); 
    header('Location: ../profile_edit.php'); exit;
  }
}

$newAvatar = null;
if ($file && !empty($file['name'])) {
  $ok = ['image/jpeg','image/png','image/webp','image/gif'];
  if (!in_array($file['type'], $ok, true)) { set_flash('err','Only JPG, PNG, WEBP or GIF.'); header('Location: ../profile_edit.php'); exit; }
  if ($file['error'] !== UPLOAD_ERR_OK)      { set_flash('err','Upload failed.'); header('Location: ../profile_edit.php'); exit; }

  $dir = dirname(__DIR__) . '/uploads/';
  if (!is_dir($dir)) mkdir($dir, 0775, true);

  $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  $nameFile = 'avt_' . $me . '_' . time() . '.' . $ext;
  if (!move_uploaded_file($file['tmp_name'], $dir . $nameFile)) {
    set_flash('err','Could not save avatar.'); header('Location: ../profile_edit.php'); exit;
  }
  $newAvatar = $nameFile;
}

$conn = db();

if ($mail !== '') {
  $chk = $conn->prepare('SELECT 1 FROM profiles WHERE email = ? AND profile_id <> ?');
  $chk->bind_param('si', $mail, $me);
  $chk->execute(); $dup = (bool)$chk->get_result()->fetch_row();
  $chk->close();
  if ($dup) {
    $conn->close();
    set_flash('err','That email is already in use.');
    header('Location: ../profile_edit.php'); exit;
  }
}

if ($newAvatar && $mail !== '') {
  $stmt = $conn->prepare('UPDATE profiles SET display_name=?, email=?, avatar_photo=? WHERE profile_id=?');
  $stmt->bind_param('sssi', $name, $mail, $newAvatar, $me);
} elseif ($newAvatar && $mail === '') {
  $stmt = $conn->prepare('UPDATE profiles SET display_name=?, email=NULL, avatar_photo=? WHERE profile_id=?');
  $stmt->bind_param('ssi', $name, $newAvatar, $me);
} elseif (!$newAvatar && $mail !== '') {
  $stmt = $conn->prepare('UPDATE profiles SET display_name=?, email=? WHERE profile_id=?');
  $stmt->bind_param('ssi', $name, $mail, $me);
} else {
  $stmt = $conn->prepare('UPDATE profiles SET display_name=?, email=NULL WHERE profile_id=?');
  $stmt->bind_param('si', $name, $me);
}

$stmt->execute();
$stmt->close();
$conn->close();

$_SESSION['display_name'] = $name;

set_flash('ok', 'Profile updated!');
header('Location: ../profile.php'); exit;
