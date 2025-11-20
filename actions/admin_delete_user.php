<?php
require_once __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !check_csrf($_POST['csrf'] ?? null)) {
    set_flash('err','Invalid CSRF token.');
    redirect('../admin.php');
}

$me = Auth::requireAdminOrRedirect('../index.php');

$targetId = (int)($_POST['profile_id'] ?? 0);
if ($targetId <= 0) {
    set_flash('err','Missing user.');
    redirect('../admin.php');
}

$profiles = new ProfileRepository();

$targetRole = $profiles->getRole($targetId);
if ($targetRole === null) {
    set_flash('err','User not found.');
    redirect('../admin.php');
}
if ($targetId === $me) {
    set_flash('err','You cannot delete yourself.');
    redirect('../admin.php');
}
if (strtolower($targetRole) === 'admin') {
    set_flash('err','You cannot delete another admin.');
    redirect('../admin.php');
}

$profiles->deleteUserCascade($targetId);

set_flash('ok','User and all their content deleted.');
redirect('../admin.php');
exit;
