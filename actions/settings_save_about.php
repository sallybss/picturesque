<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/admin_guard.php';

if (empty($_SESSION['profile_id'])) { header('Location: ../auth/login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../settings.php'); exit; }
if (!check_csrf($_POST['csrf'] ?? '')) { set_flash('err','Invalid request.'); header('Location: ../settings.php'); exit; }

$me   = (int)$_SESSION['profile_id'];
$conn = db();
require_admin($conn, $me);

$title   = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');

if ($title === '' || $content === '') {
  set_flash('err','Title and content are required.');
  header('Location: ../settings.php'); exit;
}

$img = null;
$up  = $_FILES['image'] ?? null;

if ($up && !empty($up['name'])) {
  $ok = ['image/jpeg','image/png','image/webp','image/gif'];
  if (!in_array($up['type'] ?? '', $ok, true) || ($up['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    set_flash('err','Please upload JPG/PNG/WEBP/GIF.'); header('Location: ../settings.php'); exit;
  }
  $dir = __DIR__ . '/../uploads/pages';
  if (!is_dir($dir)) mkdir($dir, 0777, true);

  $ext  = strtolower(pathinfo($up['name'], PATHINFO_EXTENSION));
  $name = 'about_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
  if (!move_uploaded_file($up['tmp_name'], $dir . '/' . $name)) {
    set_flash('err','Could not save image.'); header('Location: ../settings.php'); exit;
  }
  $img = 'pages/' . $name;
}

/* Upsert the About page */
if ($img) {
  $stmt = $conn->prepare("
    INSERT INTO pages (slug, title, content, image_path, updated_by)
    VALUES ('about', ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE title=VALUES(title), content=VALUES(content),
                            image_path=VALUES(image_path), updated_by=VALUES(updated_by)
  ");
  $stmt->bind_param('sssi', $title, $content, $img, $me);
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
header('Location: ../settings.php'); exit;
