<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db_class.php';
require __DIR__ . '/../includes/auth_class.php';
require __DIR__ . '/../includes/picture_repository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../admin.php'); exit; }
if (!check_csrf($_POST['csrf'] ?? null)) { set_flash('err','Invalid CSRF token.'); header('Location: ../admin.php'); exit; }

$me = Auth::requireAdminOrRedirect('../index.php');

$pictureId = (int)($_POST['picture_id'] ?? 0);
if ($pictureId <= 0) { set_flash('err','Missing picture.'); header('Location: ../admin.php'); exit; }

$repo = new PictureRepository();
$row = $repo->getOwnerAndUrl($pictureId);
if (!$row) { set_flash('err','Picture not found.'); header('Location: ../admin.php'); exit; }

$repo->deleteById($pictureId);

$path = dirname(__DIR__) . '/uploads/' . $row['picture_url'];
if (is_file($path)) { @unlink($path); }

set_flash('ok','Picture deleted.');
header('Location: ../admin_user_posts.php?id=' . (int)$row['owner_id']);
exit;
