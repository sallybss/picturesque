<?php
require_once __DIR__ . '/../../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !check_csrf($_POST['csrf'] ?? null)) {
    set_flash('err','Invalid request.');
    redirect('../../profile.php'); 
}

$me = Auth::requireUserOrRedirect('../../auth/login.php');  

$pid = (int)($_POST['picture_id'] ?? 0);
if ($pid <= 0) {
    set_flash('err','Invalid picture.');
    redirect('../../profile.php');
}

$repo = new PictureRepository();
$url  = $repo->getUrlIfOwned($pid, $me);
if (!$url) {
    set_flash('err','Not found.');
    redirect('../../profile.php');
}

try {
    $repo->deleteCascadeOwned($pid, $me);
} catch (Throwable $e) {
    set_flash('err','Could not delete. Try again.');
    redirect('../../profile.php');
}

$uploadDir = dirname(__DIR__) . '/../uploads/';
$file      = $uploadDir . basename($url);
if (is_file($file)) {
    @unlink($file);
}

set_flash('ok','Picture deleted.');
redirect('../../profile.php');
exit;
