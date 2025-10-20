<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';

if (empty($_SESSION['profile_id'])) { header('Location: ../auth/login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../profile.php'); exit; }
if (!check_csrf($_POST['csrf'] ?? null)) { set_flash('err','Invalid form.'); header('Location: ../profile.php'); exit; }

$me    = (int)$_SESSION['profile_id'];
$pid   = (int)($_POST['picture_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$desc  = trim($_POST['desc'] ?? '');

if ($pid <= 0 || $title === '') {
  set_flash('err','Invalid form.'); header('Location: ../edit_picture.php?id='.$pid); exit;
}

$conn = db();

$stmt = $conn->prepare('SELECT picture_url FROM pictures WHERE picture_id=? AND profile_id=?');
$stmt->bind_param('ii', $pid, $me);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) { $conn->close(); set_flash('err','Picture not found or not yours.'); header('Location: ../profile.php'); exit; }

$filename = $row['picture_url'];

if (!empty($_FILES['photo']['name'])) {
  $file = $_FILES['photo'];

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime  = $finfo->file($file['tmp_name']);
  $ok    = ['image/jpeg','image/png','image/gif','image/webp'];

  if (!in_array($mime, $ok, true)) { $conn->close(); set_flash('err','Only JPG/PNG/GIF/WEBP allowed.'); header('Location: ../edit_picture.php?id='.$pid); exit; }
  if ($file['error'] !== UPLOAD_ERR_OK) { $conn->close(); set_flash('err','Upload failed.'); header('Location: ../edit_picture.php?id='.$pid); exit; }
  if ($file['size'] > 10*1024*1024) { $conn->close(); set_flash('err','Max size is 10MB.'); header('Location: ../edit_picture.php?id='.$pid); exit; }

  $extMap = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
  $ext    = $extMap[$mime] ?? strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  $name   = time().'_'.bin2hex(random_bytes(4)).'.'.$ext;

  $dir = dirname(__DIR__).'/uploads/';
  if (!is_dir($dir)) { mkdir($dir, 0775, true); }

  if (!move_uploaded_file($file['tmp_name'], $dir.$name)) {
    $conn->close(); set_flash('err','Could not save file.'); header('Location: ../edit_picture.php?id='.$pid); exit;
  }

  if ($filename && is_file($dir.$filename)) { @unlink($dir.$filename); }
  $filename = $name;
}

$upd = $conn->prepare('UPDATE pictures SET picture_title=?, picture_description=?, picture_url=? WHERE picture_id=? AND profile_id=?');
$upd->bind_param('sssii', $title, $desc, $filename, $pid, $me);
$upd->execute();
$upd->close();
$conn->close();

set_flash('ok','Picture updated.');
header('Location: ../profile.php'); exit;
