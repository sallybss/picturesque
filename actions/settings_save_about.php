<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/admin_guard.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['profile_id'])) { header('Location: ../auth/login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../settings.php'); exit; }
if (!check_csrf($_POST['csrf'] ?? '')) { set_flash('err','Invalid CSRF.'); header('Location: ../settings.php'); exit; }

$me   = (int)$_SESSION['profile_id'];
$conn = db();
require_admin($conn, $me); // admin only

$title   = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
if ($title === '' || $content === '') {
  set_flash('err','Title and content are required.');
  header('Location: ../settings.php'); exit;
}

$imagePathToSave = null;
$upload = $_FILES['image'] ?? null;

if ($upload && !empty($upload['name'])) {
  $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
  if (!in_array($upload['type'], $allowed) || $upload['error'] !== UPLOAD_ERR_OK) {
    set_flash('err','Upload must be a valid JPG/PNG/WEBP/GIF.');
    header('Location: ../settings.php'); exit;
  }

  $dir = __DIR__ . '/../uploads/pages';
  if (!is_dir($dir)) { mkdir($dir, 0777, true); }
  $ext  = pathinfo($upload['name'], PATHINFO_EXTENSION);
  $name = 'about_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
  $dest = $dir . '/' . $name;

  if (!move_uploaded_file($upload['tmp_name'], $dest)) {
    set_flash('err','Could not save the image.');
    header('Location: ../settings.php'); exit;
  }
  $imagePathToSave = 'pages/' . $name; // store relative to /uploads
}

// Upsert
if ($imagePathToSave) {
  $stmt = $conn->prepare("
    INSERT INTO pages (slug, title, content, image_path, updated_by)
    VALUES ('about', ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE title=VALUES(title), content=VALUES(content),
                            image_path=VALUES(image_path), updated_by=VALUES(updated_by)
  ");
  $stmt->bind_param('sssi', $title, $content, $imagePathToSave, $me);
} else {
  $stmt = $conn->prepare("
    INSERT INTO pages (slug, title, content, updated_by)
    VALUES ('about', ?, ?, ?)
    ON DUPLICATE KEY UPDATE title=VALUES(title), content=VALUES(content), updated_by=VALUES(updated_by)
  ");
  $stmt->bind_param('ssi', $title, $content, $me);
}
$stmt->execute();
$stmt->close();
$conn->close();

set_flash('ok','About page saved.');
header('Location: ../settings.php');
exit;