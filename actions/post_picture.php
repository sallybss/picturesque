<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';

if (empty($_SESSION['profile_id'])) { 
  header('Location: ../auth/login.php'); 
  exit; 
}
if (!isset($_POST['submit'])) { 
  header('Location: ../create.php'); 
  exit; 
}

$title = trim($_POST['picture_title'] ?? '');
$desc  = trim($_POST['picture_description'] ?? '');

if ($title === '' || empty($_FILES['photo']['name'])) {
  set_flash('err','Please select a photo and enter a title.');
  header('Location: ../create.php'); exit;
}

$photo = $_FILES['photo'];

/* --- Safer MIME check --- */
$finfo   = new finfo(FILEINFO_MIME_TYPE);
$mime    = $finfo->file($photo['tmp_name']);
$allowed = ['image/jpeg','image/png','image/gif','image/webp'];
if (!in_array($mime, $allowed, true)) {
  set_flash('err','Only JPG/PNG/GIF/WEBP allowed.');
  header('Location: ../create.php'); exit;
}
if ($photo['error'] !== UPLOAD_ERR_OK) {
  set_flash('err','Upload failed (code '.$photo['error'].').');
  header('Location: ../create.php'); exit;
}

/* --- Dynamic paths (your app lives in …/picturesque/picturesque) --- */
$root      = realpath(__DIR__ . '/..');           // …/picturesque/picturesque
$uploadDir = $root . '/uploads/';                 // …/picturesque/picturesque/uploads/

/* Create folder if missing */
if (!is_dir($uploadDir)) {
  if (!mkdir($uploadDir, 0775, true)) {
    set_flash('err', 'Could not create uploads folder at: '.$uploadDir);
    header('Location: ../create.php'); exit;
  }
}

/* Check writability */
if (!is_writable($uploadDir)) {
  set_flash('err', 'Uploads folder is not writable: '.$uploadDir.
    ' — fix with: sudo chown -R '.$_SERVER['USER'].' "'.$uploadDir.'" && chmod -R 755 "'.$uploadDir.'"');
  header('Location: ../create.php'); exit;
}

/* Generate safe filename */
$extMap = [
  'image/jpeg' => 'jpg',
  'image/png'  => 'png',
  'image/gif'  => 'gif',
  'image/webp' => 'webp'
];
$ext    = $extMap[$mime] ?? strtolower(pathinfo($photo['name'], PATHINFO_EXTENSION));
$fname  = time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
$target = $uploadDir . $fname;

/* Move file */
if (!move_uploaded_file($photo['tmp_name'], $target)) {
  set_flash('err','Could not save the file. Target: '.$target);
  header('Location: ../create.php'); exit;
}

/* Save DB row (store only the filename) */
$conn = db();
$stmt = $conn->prepare("
  INSERT INTO pictures (profile_id, picture_title, picture_description, picture_url)
  VALUES (?, ?, ?, ?)
");
$relPath = $fname; // only filename in DB
$stmt->bind_param('isss', $_SESSION['profile_id'], $title, $desc, $relPath);
$stmt->execute();
$stmt->close(); 
$conn->close();

set_flash('ok','Picture posted!');
header('Location: ../index.php');