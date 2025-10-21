<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db_class.php';
require __DIR__ . '/../includes/auth_class.php';
require __DIR__ . '/../includes/picture_repository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../create.php'); exit; }
if (isset($_POST['csrf']) && !check_csrf($_POST['csrf'])) { set_flash('err','Invalid request.'); header('Location: ../create.php'); exit; }

$me = Auth::requireUserOrRedirect('../auth/login.php');

$title = trim($_POST['picture_title'] ?? $_POST['title'] ?? '');
$desc  = trim($_POST['picture_description'] ?? $_POST['desc'] ?? '');
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
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
  set_flash('err', 'Upload failed.');
  header('Location: ../create.php'); exit;
}

$uploadDir = dirname(__DIR__) . '/uploads/';
if (!is_dir($uploadDir)) { mkdir($uploadDir, 0775, true); }

$extMap = [
  'image/jpeg' => 'jpg',
  'image/png'  => 'png',
  'image/gif'  => 'gif',
  'image/webp' => 'webp'
];
$ext  = $extMap[$mime] ?? strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$name = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$dest = $uploadDir . $name;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
  set_flash('err', 'Could not save the file.');
  header('Location: ../create.php'); exit;
}

$repo = new PictureRepository();
$repo->create($me, $title, $desc, $name);

set_flash('ok', 'Picture posted!');
header('Location: ../index.php'); exit;
