<?php
require_once __DIR__ . '/../../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../../auth/login.php');
}

if (!check_csrf($_POST['csrf'] ?? null)) {
    set_flash('err', 'Invalid request.');
    redirect('../../auth/login.php');
}

$token            = trim($_POST['token'] ?? '');
$password         = $_POST['password'] ?? '';
$passwordConfirm  = $_POST['password_confirm'] ?? '';

if ($token === '') {
    set_flash('err', 'Invalid reset link.');
    redirect('../../auth/login.php');
}

if ($password === '' || $passwordConfirm === '') {
    set_flash('err', 'Please fill out both password fields.');
    redirect('../../auth/reset_password.php?token=' . urlencode($token));
}

if ($password !== $passwordConfirm) {
    set_flash('err', 'Passwords do not match.');
    redirect('../../auth/reset_password.php?token=' . urlencode($token));
}

if (strlen($password) < 8 || strlen($password) > 32) {
    set_flash('err', 'Password must be between 8 and 32 characters.');
    redirect('../../auth/reset_password.php?token=' . urlencode($token));
}

// Validate token
$resets = new PasswordResetRepository();
$row = $resets->findValidByToken($token);

if (!$row) {
    set_flash('err', 'This reset link is invalid or has expired.');
    redirect('../../auth/forgot_password.php');
}

$userId = (int)$row['user_id'];

// Update password in DB
$hash = password_hash($password, PASSWORD_DEFAULT);

$mysqli = DB::get();
$stmt = $mysqli->prepare("UPDATE profiles SET password_hash = ? WHERE profile_id = ?");
$stmt->bind_param('si', $hash, $userId);
$stmt->execute();
$stmt->close();

// Mark token as used
$resets->markUsed((int)$row['id']);

set_flash('ok', 'Your password has been reset. You can now sign in.');
redirect('../../auth/login.php');