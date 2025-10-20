<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';

if (empty($_SESSION['profile_id'])) { header('Location: ../auth/login.php'); exit; }
if (!check_csrf($_POST['csrf'] ?? null)) { set_flash('err','Invalid request.'); header('Location: ../profile.php'); exit; }

$me = (int)$_SESSION['profile_id'];
$act = $_POST['action'] ?? 'upload';

$conn = db();

if ($act === 'reset') {
  $stmt = $conn->prepare('UPDATE profiles SET cover_photo = NULL WHERE profile_id=?');
  $stmt->bind_param('i', $me);
  $stmt->execute();
  $stmt->close(); $conn->close();

  set_flash('ok','Cover reset.');
  header('Location: ../profile.php'); exit;
}

$file = $_FILES['cover'] ?? null;
if (!$file || empty($file['name'])) { set_flash('err','Choose an image.'); header('Location: ../profile.php'); exit; }

$ok = ['image/jpeg','image/png','image/webp','image/gif'];
if (!in_array($file['type'], $ok, true)) { set_flash('err','Use JPG/PNG/WEBP/GIF.'); header('Location: ../profile.php'); exit; }
if (!empty($file['size']) && $file['size'] > 5*1024*1024) { set_flash('err','Max 5MB.'); header('Location: ../profile.php'); exit; }

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$fname = 'cover_' . $me . '_' . time() . '.' . $ext;
$dest = dirname(__DIR__) . '/uploads/' . $fname;

if (!move_uploaded_file($file['tmp_name'], $dest)) { set_flash('err','Upload failed.'); header('Location: ../profile.php'); exit; }

$stmt = $conn->prepare('UPDATE profiles SET cover_photo=? WHERE profile_id=?');
$stmt->bind_param('si', $fname, $me);
$stmt->execute();
$stmt->close(); $conn->close();

set_flash('ok','Cover updated.');
header('Location: ../profile.php'); exit;
