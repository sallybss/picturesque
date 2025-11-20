<?php
require_once __DIR__ . '/../../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../../profile.php');
}

if (!check_csrf($_POST['csrf'] ?? null)) {
    set_flash('err', 'Invalid form.');
    redirect('../../profile.php');
}

$me    = Auth::requireUserOrRedirect('../../auth/login.php');
$pid   = (int)($_POST['picture_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$desc  = trim($_POST['desc'] ?? '');
$reset = !empty($_POST['reset_image']);

$catId = (int)($_POST['category_id'] ?? 0);

if ($pid <= 0 || $title === '' || $catId <= 0) {
    set_flash('err', 'Invalid form.');
    redirect("../../edit_picture.php?id=$pid");
}

$catsRepo = new CategoriesRepository();
if (!$catsRepo->isActive($catId)) {
    set_flash('err', 'Invalid category.');
    redirect("../../edit_picture.php?id=$pid");
}

$repo = new PictureRepository();
$old  = $repo->getUrlIfOwned($pid, $me);

if ($old === null) {
    set_flash('err', 'Picture not found or not yours.');
    redirect('../../profile.php');
}

$uploadDir = dirname(__DIR__) . '/../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

$newFilename = null;

if ($reset && empty($_FILES['photo']['name'])) {
    if ($old && is_file($uploadDir . $old)) {
        @unlink($uploadDir . $old);
    }
    $repo->updateOwned($pid, $me, $title, $desc, null, $catId);
    set_flash('ok', 'Picture updated.');
    redirect('../../profile.php');
}

if (!empty($_FILES['photo']['name'])) {
    $file = $_FILES['photo'];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];

    if (!in_array($mime, $allowed, true)) {
        set_flash('err', 'Only JPG/PNG/GIF/WEBP allowed.');
        redirect("../../edit_picture.php?id=$pid");
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        set_flash('err', 'Upload failed.');
        redirect("../../edit_picture.php?id=$pid");
    }

    if (($file['size'] ?? 0) > 10 * 1024 * 1024) {
        set_flash('err', 'Max size is 10MB.');
        redirect("../../edit_picture.php?id=$pid");
    }

    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    $ext  = $extMap[$mime] ?? pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $uploadDir . $name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        set_flash('err', 'Could not save file.');
        redirect("../../edit_picture.php?id=$pid");
    }

    if ($old && is_file($uploadDir . $old)) {
        @unlink($uploadDir . $old);
    }

    $newFilename = $name;
}

$repo->updateOwned($pid, $me, $title, $desc, $newFilename, $catId);

set_flash('ok', 'Picture updated.');
redirect('../../profile.php');
