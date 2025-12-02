<?php
require_once __DIR__ . '/../../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../../auth/register.php');
}

if (!check_csrf($_POST['csrf'] ?? null)) {
    set_flash('err', 'Invalid request.');
    redirect('../../auth/register.php');
}

$captchaAnswer = trim($_POST['register_captcha'] ?? '');
$expected      = isset($_SESSION['register_captcha_answer'])
    ? (int)$_SESSION['register_captcha_answer']
    : null;

unset($_SESSION['register_captcha_answer']);

if ($expected === null || $captchaAnswer === '' || (int)$captchaAnswer !== $expected) {
    set_flash('err', 'Captcha failed. Please try again.');
    redirect('../../auth/register.php');
}

$email = mb_substr(trim($_POST['login_email'] ?? ''), 0, 255);
$name  = mb_substr(trim($_POST['display_name'] ?? ''), 0, 50);
$pass  = (string)($_POST['password'] ?? '');

if ($email === '' || $name === '' || $pass === '') {
    set_flash('err', 'Please fill all fields.');
    redirect('../../auth/register.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('err', 'Enter a valid email.');
    redirect('../../auth/register.php');
}

if (strlen($pass) < 8) {
    set_flash('err', 'Password must be at least 8 characters.');
    redirect('../../auth/register.php');
}

$profiles = new ProfileRepository();

if ($profiles->loginEmailExists($email)) {
    set_flash('err', 'Email already registered.');
    redirect('../../auth/register.php');
}

$hash  = password_hash($pass, PASSWORD_DEFAULT);
$newId = $profiles->createUser($email, $name, $hash);

$_SESSION['profile_id']   = $newId;
$_SESSION['display_name'] = $name;
$_SESSION['role']         = 'user';

set_flash('ok', 'Account created. You are signed in.');
redirect('../../index.php');
