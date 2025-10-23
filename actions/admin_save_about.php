<?php
require_once __DIR__ . '/../includes/init.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../admin_about.php'); exit; }
if (!check_csrf($_POST['csrf'] ?? null)) { set_flash('err','Invalid CSRF.'); header('Location: ../admin_about.php'); exit; }

$me = Auth::requireAdminOrRedirect('../index.php');

$title   = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
if ($title === '' || $content === '') { set_flash('err','Title and content are required.'); header('Location: ../admin_about.php'); exit; }

$imagePath = null;
$f = $_FILES['image'] ?? null;

if ($f && !empty($f['name'])) {
  $ok = ['image/jpeg','image/png','image/webp','image/gif'];
  if (!in_array($f['type'], $ok)) { set_flash('err','Image must be JPG/PNG/WEBP/GIF.'); header('Location: ../admin_about.php'); exit; }
  if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { set_flash('err','Upload failed.'); header('Location: ../admin_about.php'); exit; }
  if (($f['size'] ?? 0) > 6*1024*1024) { set_flash('err','Image too large (max 6MB).'); header('Location: ../admin_about.php'); exit; }

  $dir = __DIR__ . '/../uploads/pages';
  if (!is_dir($dir)) { mkdir($dir, 0777, true); }

  $ext  = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
  $name = 'about_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
  $dest = $dir . '/' . $name;

  if (!move_uploaded_file($f['tmp_name'], $dest)) { set_flash('err','Could not save the image.'); header('Location: ../admin_about.php'); exit; }
  $imagePath = 'pages/' . $name;
}

$pages = new PagesRepository();
$pages->upsertAbout($title, $content, $imagePath, $me);

set_flash('ok','About page saved.');
header('Location: ../admin_about.php'); 
exit;
