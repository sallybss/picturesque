<?php
require_once __DIR__ . '/../../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../../profile_edit.php');
}

if (!check_csrf($_POST['csrf'] ?? null)) {
    set_flash('err', 'Invalid request.');
    redirect('../../profile_edit.php');
}

$me   = Auth::requireUserOrRedirect('../../auth/login.php');
$name = mb_substr(trim($_POST['display_name'] ?? ''), 0, 50);
$mail = trim($_POST['email'] ?? '');
$file = $_FILES['avatar'] ?? null;

if ($name === '') {
  set_flash('err', 'Display name is required.');
  redirect('../../profile_edit.php');
}

if ($mail !== '' && !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
  set_flash('err', 'Enter a valid email or leave it blank.');
  redirect('../../profile_edit.php');
}

$newAvatar = null;

if ($file && !empty($file['name'])) {
    $allowedMime = ['image/jpeg','image/png','image/webp','image/gif'];
    $allowedExt  = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        set_flash('err','Upload failed.');
        redirect('../../profile_edit.php');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
    if ($finfo) {
        finfo_close($finfo);
    }

    if (!in_array($mime, $allowedMime, true)) {
        set_flash('err','Only JPG, PNG, WEBP or GIF.');
        redirect('../../profile_edit.php');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        set_flash('err','Only JPG, PNG, WEBP or GIF.');
        redirect('../../profile_edit.php');
    }

    if (!empty($file['size']) && $file['size'] > 5 * 1024 * 1024) {
        set_flash('err','Max 5MB.');
        redirect('../../profile_edit.php');
    }

    $dir = dirname(__DIR__) . '/../uploads/';
    if (!is_dir($dir)) { mkdir($dir, 0775, true); }

    $nameFile = "avt_{$me}_" . time() . ".$ext";

    if (!move_uploaded_file($file['tmp_name'], $dir . $nameFile)) {
        set_flash('err','Could not save avatar.');
        redirect('../../profile_edit.php');
    }

    $newAvatar = $nameFile;
}


$profiles = new ProfileRepository();

if ($mail !== '' && $profiles->emailInUseByOther($mail, $me)) {
  set_flash('err','That email is already in use.');
  redirect('../../profile_edit.php');
}

$profiles->updateProfileWithEmailAndAvatar($me, $name, ($mail === '' ? null : $mail), $newAvatar);

$_SESSION['display_name'] = $name;

set_flash('ok', 'Profile updated!');
redirect('../../profile.php');
