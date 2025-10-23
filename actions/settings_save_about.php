<?php
require_once __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../settings.php'); exit; }
if (!check_csrf($_POST['csrf'] ?? '')) { set_flash('err','Invalid request.'); header('Location: ../settings.php'); exit; }

$me = Auth::requireAdminOrRedirect('../index.php');

$title   = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');

if ($title === '' || $content === '') {
  set_flash('err','Title and content are required.');
  header('Location: ../settings.php'); exit;
}

$imagePath = null;
$up = $_FILES['image'] ?? null;

if ($up && !empty($up['name'])) {
  $ok = ['image/jpeg','image/png','image/webp','image/gif'];
  if (!in_array($up['type'] ?? '', $ok, true)) { set_flash('err','Please upload JPG/PNG/WEBP/GIF.'); header('Location: ../settings.php'); exit; }
  if (($up['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { set_flash('err','Upload failed.'); header('Location: ../settings.php'); exit; }

  $dir = __DIR__ . '/../uploads/pages';
  if (!is_dir($dir)) { mkdir($dir, 0777, true); }

  $ext  = strtolower(pathinfo($up['name'], PATHINFO_EXTENSION));
  $name = 'about_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
  $dest = $dir . '/' . $name;

  if (!move_uploaded_file($up['tmp_name'], $dest)) { set_flash('err','Could not save image.'); header('Location: ../settings.php'); exit; }
  $imagePath = 'pages/' . $name;
}

$pages = new PagesRepository();
$pages->upsertAbout($title, $content, $imagePath, $me);

set_flash('ok','About page saved.');
header('Location: ../settings.php'); exit;
