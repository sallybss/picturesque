<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db_class.php';
require __DIR__ . '/../includes/auth_class.php';
require __DIR__ . '/../includes/profile_repository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../profile.php'); exit; }
if (!check_csrf($_POST['csrf'] ?? null)) { set_flash('err','Invalid request.'); header('Location: ../profile.php'); exit; }

$me  = Auth::requireUserOrRedirect('../auth/login.php');
$act = $_POST['action'] ?? 'upload';

$profiles = new ProfileRepository();

if ($act === 'reset') {
    $profiles->setCoverPhoto($me, null);
    set_flash('ok','Cover reset.');
    header('Location: ../profile.php'); exit;
}

$file = $_FILES['cover'] ?? null;
if (!$file || empty($file['name'])) { set_flash('err','Choose an image.'); header('Location: ../profile.php'); exit; }

$ok = ['image/jpeg','image/png','image/webp','image/gif'];
if (!in_array($file['type'] ?? '', $ok, true)) { set_flash('err','Use JPG/PNG/WEBP/GIF.'); header('Location: ../profile.php'); exit; }
if (!empty($file['size']) && $file['size'] > 5*1024*1024) { set_flash('err','Max 5MB.'); header('Location: ../profile.php'); exit; }

$dir = dirname(__DIR__) . '/uploads/';
if (!is_dir($dir)) { mkdir($dir, 0775, true); }

$ext    = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$fname  = 'cover_' . $me . '_' . time() . '.' . $ext;
$target = $dir . $fname;

if (!move_uploaded_file($file['tmp_name'], $target)) { set_flash('err','Upload failed.'); header('Location: ../profile.php'); exit; }

$profiles->setCoverPhoto($me, $fname);

set_flash('ok','Cover updated.');
header('Location: ../profile.php'); exit;
