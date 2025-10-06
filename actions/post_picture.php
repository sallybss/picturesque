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
$allowed = ['image/jpeg','image/png','image/gif','image/webp'];
if (!in_array($photo['type'], $allowed)) {
  set_flash('err','Only JPG/PNG/GIF/WEBP allowed.');
  header('Location: ../create.php'); exit;
}
if ($photo['error'] !== UPLOAD_ERR_OK) {
  set_flash('err','Upload failed (code '.$photo['error'].').');
  header('Location: ../create.php'); exit;
}

/* --- Force the correct uploads folder (absolute path) --- */
$uploadDir  = '/Applications/XAMPP/xamppfiles/htdocs/picturesque/uploads/'; 
$publicPath = 'uploads/'; // what we prepend in <img src="uploads/...">

/* Create folder if missing */
if (!is_dir($uploadDir)) {
  if (!mkdir($uploadDir, 0775, true)) {
    set_flash('err', 'Could not create uploads folder at: '.$uploadDir);
    header('Location: ../create.php'); exit;
  }
}

/* Check writability */
if (!is_writable($uploadDir)) {
  set_flash('err', 'Uploads folder is not writable: '.$uploadDir.' — On macOS/XAMPP, run: sudo chmod -R 775 '.$uploadDir.' and ensure Apache (daemon/_www) owns it.');
  header('Location: ../create.php'); exit;
}

/* Generate safe filename */
$ext = strtolower(pathinfo($photo['name'], PATHINFO_EXTENSION));
$fname  = time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
$target = $uploadDir . $fname;

/* Move file */
if (!move_uploaded_file($photo['tmp_name'], $target)) {
  set_flash('err','Could not save the file. Target: '.$target);
  header('Location: ../create.php'); exit;
}

/* Save DB row */
$conn = db();
$stmt = $conn->prepare("INSERT INTO pictures (profile_id, picture_title, picture_description, picture_url) VALUES (?, ?, ?, ?)");
$relPath = $fname; // only filename in DB, prepend uploads/ in HTML
$stmt->bind_param('isss', $_SESSION['profile_id'], $title, $desc, $relPath);
$stmt->execute();
$stmt->close(); 
$conn->close();

set_flash('ok','Picture posted!');
header('Location: ../index.php');