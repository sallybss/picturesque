<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/admin_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../admin.php'); exit; }
if (!csrf_check($_POST['csrf'] ?? null)) { set_flash('err','Invalid CSRF token.'); header('Location: ../admin.php'); exit; }

$me        = (int)($_SESSION['profile_id'] ?? 0);
$pictureId = (int)($_POST['picture_id'] ?? 0);
if ($pictureId <= 0) { set_flash('err','Missing picture.'); header('Location: ../admin.php'); exit; }

$conn = db();
require_admin($conn, $me);

$sel = $conn->prepare("SELECT picture_url, profile_id FROM pictures WHERE picture_id=?");
$sel->bind_param('i', $pictureId);
$sel->execute();
$sel->bind_result($picUrl, $ownerId);
$found = $sel->fetch();
$sel->close();

if (!$found) { $conn->close(); set_flash('err','Picture not found.'); header('Location: ../admin.php'); exit; }

$del = $conn->prepare("DELETE FROM pictures WHERE picture_id=?");
$del->bind_param('i', $pictureId);
$del->execute();
$del->close();
$conn->close();

$path = dirname(__DIR__) . '/uploads/' . $picUrl;
if (is_file($path)) { @unlink($path); }

set_flash('ok','Picture deleted.');
header('Location: ../admin_user_posts.php?id=' . (int)$ownerId);
exit;
