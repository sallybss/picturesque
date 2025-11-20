<?php
require_once __DIR__ . '/../../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../../profile_settings.php');
}
if (!check_csrf($_POST['csrf'] ?? null)) {
    set_flash('err','Invalid request.');
    redirect('../../profile_settings.php');
}

$me = Auth::requireUserOrRedirect('../../auth/login.php');

$email = trim($_POST['login_email'] ?? '');
$cur   = (string)($_POST['current_password'] ?? '');
$pass  = (string)($_POST['new_password'] ?? '');
$conf  = (string)($_POST['confirm_password'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  set_flash('err','Enter a valid email.');
  redirect('../../profile_settings.php');
}

$profiles = new ProfileRepository();
$user = $profiles->getLoginEmailAndHash($me);

if (!$user) {
    set_flash('err','User not found.');
    redirect('../../profile_settings.php');
}

if (strcasecmp($email, (string)$user['login_email']) !== 0) {
    if ($profiles->loginEmailInUseByOther($email, $me)) {
        set_flash('err','Email already in use.');
        redirect('../../profile_settings.php');
    }
}

$changePwd = ($pass !== '' || $conf !== '');

if ($changePwd) {
    if (strlen($pass) < 8) { set_flash('err','Password must be at least 8 chars.'); redirect('../../profile_settings.php'); }
    if ($pass !== $conf)   { set_flash('err','Passwords do not match.'); redirect('../../profile_settings.php'); }
    if ($cur === '' || !password_verify($cur, (string)$user['password_hash'])) {
        set_flash('err','Current password is incorrect.');
        redirect('../../profile_settings.php');
    }
}

if ($changePwd) {
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $profiles->updateLoginEmailAndPassword($me, $email, $hash);
} else {
    $profiles->updateLoginEmail($me, $email);
}

set_flash('ok','Settings updated.');
redirect('../../profile_settings.php');
