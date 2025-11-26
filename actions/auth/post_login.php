<?php
require_once __DIR__ . '/../../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../../auth/login.php');
}

if (!check_csrf($_POST['csrf'] ?? null)) {
    set_flash('err', 'Invalid request.');
    redirect('../../auth/login.php');
}

$captchaInput  = trim((string)($_POST['login_captcha'] ?? ''));
$captchaAnswer = $_SESSION['login_captcha_answer'] ?? null;
unset($_SESSION['login_captcha_answer']);

if ($captchaAnswer === null || $captchaInput === '' || (int)$captchaInput !== (int)$captchaAnswer) {
    set_flash('err', 'Captcha incorrect. Please try again.');
    redirect('../../auth/login.php');
}

$email = trim($_POST['login_email'] ?? '');
$pass  = (string)($_POST['password'] ?? '');

if ($email === '' || $pass === '') {
    set_flash('err', 'Please enter both email and password.');
    redirect('../../auth/login.php');
}

$profiles = new ProfileRepository();
$user     = $profiles->findAuthByEmail($email);

if (!$user || !password_verify($pass, $user['password_hash'])) {
    set_flash('err', 'Invalid email or password.');
    redirect('../../auth/login.php');
}

if (($user['status'] ?? '') !== 'active') {
    set_flash('err', 'Your account is not active.');
    redirect('../../auth/login.php');
}

$_SESSION['profile_id']   = (int)$user['profile_id'];
$_SESSION['display_name'] = (string)$user['display_name'];
$_SESSION['role']         = (string)$user['role'];

set_flash('ok', 'Welcome back, ' . htmlspecialchars($user['display_name']) . '!');
redirect('../../index.php');