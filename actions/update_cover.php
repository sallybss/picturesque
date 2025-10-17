<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';

if (empty($_SESSION['profile_id'])) { header('Location: ../auth/login.php'); exit; }
if (!check_csrf($_POST['csrf'] ?? null)) { set_flash('err','Invalid token.'); header('Location: ../profile.php'); exit; }

$me     = (int)$_SESSION['profile_id'];
$action = $_POST['action'] ?? 'upload';

$conn = db();

if ($action === 'reset') {
  // Just null out the cover_photo so the default fallback is used
  $stmt = $conn->prepare("UPDATE profiles SET cover_photo = NULL WHERE profile_id = ?");
  $stmt->bind_param('i', $me);
  $stmt->execute();
  $stmt->close();
  $conn->close();
  set_flash('ok','Cover reset to default.');
  header('Location: ../profile.php');
  exit;
}

// Otherwise handle upload
$file = $_FILES['cover'] ?? null;
if (!$file || empty($file['name'])) {
  set_flash('err','No file selected.');
  header('Location: ../profile.php'); exit;
}

$allowed = ['image/jpeg','image/png','image/webp','image/gif'];
if (!in_array($file['type'], $allowed)) {
  set_flash('err','Cover must be an image (jpg, png, webp, gif).');
  header('Location: ../profile.php'); exit;
}

// Optional: basic size limit (5MB)
if (!empty($file['size']) && $file['size'] > 5 * 1024 * 1024) {
  set_flash('err','File is too large (max 5MB).');
  header('Location: ../profile.php'); exit;
}

$ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$name = 'cover_' . $me . '_' . time() . '.' . $ext;
$target = __DIR__ . '/../uploads/' . $name;

if (!move_uploaded_file($file['tmp_name'], $target)) {
  set_flash('err','Upload failed.');
  header('Location: ../profile.php'); exit;
}

// Save filename
$stmt = $conn->prepare("UPDATE profiles SET cover_photo = ? WHERE profile_id = ?");
$stmt->bind_param('si', $name, $me);
$stmt->execute();
$stmt->close();
$conn->close();

set_flash('ok','Cover photo updated!');
header('Location: ../profile.php');
exit;