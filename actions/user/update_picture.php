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

if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
    set_flash('err','Upload failed. Please try again.');
    redirect('../../profile.php');
}

if (!empty($file['size']) && $file['size'] > 5 * 1024 * 1024) {
    set_flash('err','Max 5MB.');
    redirect('../../profile.php');
}

if (!is_uploaded_file($file['tmp_name'])) {
    set_flash('err','Invalid upload.');
    redirect('../../profile.php');
}

$allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);

if (!isset($allowed[$mime])) {
    set_flash('err','Use JPG/PNG/WEBP/GIF.');
    redirect('../../profile.php');
}

$ext   = $allowed[$mime];
$dir   = dirname(__DIR__) . '/../uploads/';
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}

$fname  = "cover_{$me}_" . time() . '.' . $ext;
$target = $dir . $fname;

if (!move_uploaded_file($file['tmp_name'], $target)) {
    set_flash('err','Upload failed.');
    redirect('../../profile.php');
}

@chmod($target, 0644);

$profiles->setCoverPhoto($me, $fname);

set_flash('ok','Cover updated.');
redirect('../../profile.php');
