<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';

if (empty($_SESSION['profile_id'])) { 
  header('Location: ../auth/login.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../create.php'); exit;
}

$title = trim($_POST['picture_title'] ?? '');
$desc  = trim($_POST['picture_description'] ?? '');
$file  = $_FILES['photo'] ?? null;

if ($title === '' || !$file || empty($file['name'])) {
  set_flash('err', 'Please select a photo and enter a title.');
  header('Location: ../create.php'); exit;
}

$finfo   = new finfo(FILEINFO_MIME_TYPE);
$mime    = $finfo->file($file['tmp_name']);
$allowed = ['image/jpeg','image/png','image/gif','image/webp'];

if (!in_array($mime, $allowed, true)) {
  set_flash('err', 'Only JPG/PNG/GIF/WEBP allowed.');
  header('Location: ../create.php'); exit;
}
if ($file['error'] !== UPLOAD_ERR_OK) {
  set_flash('err', 'Upload failed.');
  header('Location: ../create.php'); exit;
}

$uploadDir = dirname(__DIR__) . '/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

$extMap = [
  'image/jpeg' => 'jpg',
  'image/png'  => 'png',
  'image/gif'  => 'gif',
  'image/webp' => 'webp'
];
$ext    = $extMap[$mime] ?? strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$name   = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$path   = $uploadDir . $name;

if (!move_uploaded_file($file['tmp_name'], $path)) {
  set_flash('err', 'Could not save the file.');
  header('Location: ../create.php'); exit;
}

$conn = db();
$stmt = $conn->prepare("
  INSERT INTO pictures (profile_id, picture_title, picture_description, picture_url)
  VALUES (?, ?, ?, ?)
");
$uid = (int)$_SESSION['profile_id'];
$stmt->bind_param('isss', $uid, $title, $desc, $name);
$stmt->execute();
$stmt->close();
$conn->close();

set_flash('ok', 'Picture posted!');
header('Location: ../index.php'); exit;
