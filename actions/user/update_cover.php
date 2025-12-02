<?php
require_once __DIR__ . '/../../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../../profile.php');
}
if (!check_csrf($_POST['csrf'] ?? null)) {
    set_flash('err','Invalid request.');
    redirect('../../profile.php');
}

$me  = Auth::requireUserOrRedirect('../../auth/login.php');
$act = $_POST['action'] ?? 'upload';

$profiles = new ProfileRepository();

if ($act === 'reset') {
    $profiles->setCoverPhoto($me, null);
    set_flash('ok','Cover reset.');
    redirect('../../profile.php');
}

$file = $_FILES['cover'] ?? null;
if (!$file || empty($file['name'])) {
    set_flash('err','Choose an image.');
    redirect('../../profile.php');
}

if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    set_flash('err','Upload failed.');
    redirect('../../profile.php');
}

$allowedMime = ['image/jpeg','image/png','image/webp','image/gif'];
$allowedExt  = ['jpg','jpeg','png','webp','gif'];

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
if ($finfo) {
    finfo_close($finfo);
}

if (!in_array($mime, $allowedMime, true)) {
    set_flash('err','Use JPG/PNG/WEBP/GIF.');
    redirect('../../profile.php');
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt, true)) {
    set_flash('err','Use JPG/PNG/WEBP/GIF.');
    redirect('../../profile.php');
}

if (!empty($file['size']) && $file['size'] > 5*1024*1024) {
    set_flash('err','Max 5MB.');
    redirect('../../profile.php');
}

$dir = dirname(__DIR__) . '/../uploads/';
if (!is_dir($dir)) { mkdir($dir, 0775, true); }

$fname  = "cover_{$me}_" . time() . ".$ext";
$target = $dir . $fname;

if (!move_uploaded_file($file['tmp_name'], $target)) {
    set_flash('err','Upload failed.');
    redirect('../../profile.php');
}

$profiles->setCoverPhoto($me, $fname);

set_flash('ok','Cover updated.');
redirect('../../profile.php');
