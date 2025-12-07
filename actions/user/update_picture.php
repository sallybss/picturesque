<?php
require_once __DIR__ . '/../../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../../profile.php');
}
if (!check_csrf($_POST['csrf'] ?? null)) {
    set_flash('err', 'Invalid request.');
    redirect('../../profile.php');
}

$me = Auth::requireUserOrRedirect('../../auth/login.php');

$pid = (int)($_POST['picture_id'] ?? 0);
if ($pid <= 0) {
    set_flash('err', 'Invalid picture.');
    redirect('../../profile.php');
}

$pictures = new PictureRepository();
$pic = $pictures->getEditableByOwner($pid, $me);
if (!$pic) {
    set_flash('err', 'Picture not found or not yours.');
    redirect('../../profile.php');
}


$COOLDOWN  = 300;
$MAX_EDITS = 2;
$now       = time();

$picKey = 'pic_' . $pid;
$state = $_SESSION['edit_pic_rate'][$picKey] ?? [
    'window_start'  => 0,
    'count'         => 0,
    'blocked_until' => 0,
];

if ($state['blocked_until'] > $now) {
    $remaining = $state['blocked_until'] - $now;
    $mins      = max(1, ceil($remaining / 60));

    set_flash(
        'err',
        'You edited this post too many times. Please wait about ' . $mins . ' minute(s) before editing again.'
    );
    redirect('../../edit_picture.php?id=' . $pid);
}

if ($now - $state['window_start'] > $COOLDOWN) {
    $state['window_start'] = $now;
    $state['count']        = 0;
}

$state['count']++;

if ($state['count'] > $MAX_EDITS) {
    $state['blocked_until'] = $now + $COOLDOWN;
    $_SESSION['edit_pic_rate'][$picKey] = $state;

    $mins = ceil($COOLDOWN / 60);
    set_flash(
        'err',
        'You reached the limit of ' . $MAX_EDITS . ' edits in 5 minutes. Please wait about ' . $mins . ' minute(s).'
    );
    redirect('../../edit_picture.php?id=' . $pid);
}

$_SESSION['edit_pic_rate'][$picKey] = $state;

$title       = trim($_POST['title'] ?? '');
$desc        = trim($_POST['desc'] ?? '');
$category_id = (int)($_POST['category_id'] ?? 0);
$resetImage  = trim($_POST['reset_image'] ?? '');

if ($title === '' || $category_id <= 0) {
    set_flash('err', 'Title and category are required.');
    redirect('../../edit_picture.php?id=' . $pid);
}

$file        = $_FILES['photo'] ?? null;
$newFilename = null;

if ($file && !empty($file['name'])) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        set_flash('err', 'Upload failed. Please try again.');
        redirect('../../edit_picture.php?id=' . $pid);
    }

    if (!empty($file['size']) && $file['size'] > 10 * 1024 * 1024) {
        set_flash('err', 'Max 10MB.');
        redirect('../../edit_picture.php?id=' . $pid);
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
        set_flash('err', 'Use JPG/PNG/WEBP/GIF.');
        redirect('../../edit_picture.php?id=' . $pid);
    }

    $ext   = $allowed[$mime];
    $dir   = dirname(__DIR__) . '/../uploads/';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $newFilename = "picture_{$me}_" . time() . '.' . $ext;
    $target      = $dir . $newFilename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        set_flash('err', 'Upload failed.');
        redirect('../../edit_picture.php?id=' . $pid);
    }

    @chmod($target, 0644);
}

if ($resetImage === '1' && $newFilename === null) {
    $pictures->clearImage($pid, $me);
    $filenameToSave = null;
} elseif ($newFilename !== null) {
    $filenameToSave = $newFilename;
} else {
    $filenameToSave = null;
}


$pictures->updateOwned($pid, $me, $title, $desc, $filenameToSave, $category_id);

set_flash('ok', 'Post updated.');
redirect('../../profile.php');
