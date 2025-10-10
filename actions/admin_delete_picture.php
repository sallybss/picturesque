<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/admin_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../admin.php'); exit; }
if (!hash_equals(csrf_token(), $_POST['csrf'] ?? '')) {
  set_flash('err','Invalid CSRF token.'); header('Location: ../admin.php'); exit;
}

$me        = (int)($_SESSION['profile_id'] ?? 0);
$pictureId = (int)($_POST['picture_id'] ?? 0);

if ($pictureId <= 0) { set_flash('err','Missing picture.'); header('Location: ../admin.php'); exit; }

$conn = db();
require_admin($conn, $me);

// get file name so we can optionally remove the file from /uploads
$sel = $conn->prepare("SELECT picture_url FROM pictures WHERE picture_id=?");
$sel->bind_param('i', $pictureId);
$sel->execute();
$picUrl = $sel->get_result()->fetch_column();
$sel->close();

if (!$picUrl) { set_flash('err','Picture not found.'); header('Location: ../admin.php'); exit; }

// delete db row (likes/comments/categories)
$del = $conn->prepare("DELETE FROM pictures WHERE picture_id=?");
$del->bind_param('i', $pictureId);
$del->execute();
$del->close();
$conn->close();


$path = dirname(__DIR__) . '/uploads/' . $picUrl;
if (is_file($path)) { @unlink($path); }

set_flash('ok','Picture deleted.');

$ref = $_SERVER['HTTP_REFERER'] ?? '../admin.php';
header('Location: ../admin_user_posts.php?id=' . $ownerId);
exit;
