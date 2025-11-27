<?php
require_once __DIR__ . '/../../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../../auth/login.php');
}

if (!check_csrf($_POST['csrf'] ?? null)) {
    set_flash('err', 'Invalid request.');
    redirect('../../auth/login.php');
}

$emailRaw = trim($_POST['login_email'] ?? '');
$pass     = (string)($_POST['password'] ?? '');

if ($emailRaw === '' || $pass === '') {
    set_flash('err', 'Please enter both email and password.');
    redirect('../../auth/login.php');
}

// Normalize for tracking login attempts
$email = mb_strtolower($emailRaw);
$ip    = $_SERVER['REMOTE_ADDR'] ?? '';

$mysqli = DB::get();

// ----------------- BRUTE FORCE PROTECTION -----------------
$maxAttempts   = 5;   // max failed attempts
$windowMinutes = 10;  // time window in minutes

// Count failed attempts for this email in last X minutes
$checkSql = "
    SELECT COUNT(*) AS c
    FROM login_attempts
    WHERE email = ?
      AND success = 0
      AND created_at >= (NOW() - INTERVAL ? MINUTE)
";

$check = $mysqli->prepare($checkSql);
if ($check) {
    $check->bind_param('si', $email, $windowMinutes);
    $check->execute();
    $row = $check->get_result()->fetch_assoc() ?: ['c' => 0];
    $check->close();
    $failCount = (int)$row['c'];
} else {
    $failCount = 0;
}

if ($failCount >= $maxAttempts) {
    // Store lock info in session so login.php can disable form
    $_SESSION['login_lock_email'] = $email;
    $_SESSION['login_lock_until'] = time() + ($windowMinutes * 60);

    set_flash(
        'err',
        "Too many failed login attempts. Please try again in about {$windowMinutes} minutes."
    );
    redirect('../../auth/login.php');
}
// ----------------- END BRUTE FORCE CHECK -----------------


// Load user by email (your repo probably expects raw email)
$profiles = new ProfileRepository();
$user     = $profiles->findAuthByEmail($emailRaw);

$isValid = $user && password_verify($pass, $user['password_hash']);

// Log this attempt (success or fail)
$logSql = "
    INSERT INTO login_attempts (email, ip_address, success)
    VALUES (?, ?, ?)
";
$log = $mysqli->prepare($logSql);
if ($log) {
    $successInt = $isValid ? 1 : 0;
    $log->bind_param('ssi', $email, $ip, $successInt);
    $log->execute();
    $log->close();
}

// If invalid credentials
if (!$isValid) {
    set_flash('err', 'Invalid email or password.');
    redirect('../../auth/login.php');
}

// If account not active
if (($user['status'] ?? '') !== 'active') {
    set_flash('err', 'Your account is not active.');
    redirect('../../auth/login.php');
}

// Successful login → clear any previous lock
unset($_SESSION['login_lock_email'], $_SESSION['login_lock_until']);

$_SESSION['profile_id']   = (int)$user['profile_id'];
$_SESSION['display_name'] = (string)$user['display_name'];
$_SESSION['role']         = (string)$user['role'];

set_flash('ok', 'Welcome back, ' . htmlspecialchars($user['display_name']) . '!');
redirect('../../index.php');