<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db_class.php';
require __DIR__ . '/../includes/auth_class.php';
require __DIR__ . '/../includes/picture_repository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../profile.php'); exit; }
if (!check_csrf($_POST['csrf'] ?? null)) { set_flash('err','Invalid request.'); header('Location: ../profile.php'); exit; }

$me = Auth::requireUserOrRedirect('../auth/login.php');

$pid = (int)($_POST['picture_id'] ?? 0);
if ($pid <= 0) { set_flash('err','Invalid picture.'); header('Location: ../profile.php'); exit; }

$repo = new PictureRepository();
$url = $repo->getUrlIfOwned($pid, $me);
if (!$url) { set_flash('err','Not found.'); header('Location: ../profile.php'); exit; }

try {
    $repo->deleteCascadeOwned($pid, $me);
} catch (Throwable $e) {
    set_flash('err','Could not delete. Try again.');
    header('Location: ../profile.php'); exit;
}

$file = __DIR__ . '/../uploads/' . basename($url);
if (is_file($file)) { @unlink($file); }

set_flash('ok','Picture deleted.');
header('Location: ../profile.php'); exit;
