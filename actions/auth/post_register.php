<?php
require_once __DIR__ . '/../../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../../auth/register.php');
}

if (!check_csrf($_POST['csrf'] ?? null)) {
    set_flash('err', 'Invalid request.');
    redirect('../../auth/register.php');
}

$email = trim($_POST['login_email'] ?? '');
$name  = trim($_POST['display_name'] ?? '');
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
