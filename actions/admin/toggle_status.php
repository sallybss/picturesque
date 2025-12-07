<?php
require_once __DIR__ . '/../../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !check_csrf($_POST['csrf'] ?? null)) {
    set_flash('err','Invalid request.');
    redirect('../../admin.php');
}

$me = Auth::requireAdminOrRedirect('../index.php');

$targetId = (int)($_POST['profile_id'] ?? 0);
if ($targetId <= 0) {
    set_flash('err','Missing user.');
    redirect('../../admin.php');
}

$profiles = new ProfileRepository();
$u = $profiles->getRoleAndStatus($targetId);

if (!$u) {
    set_flash('err','User not found.');
    redirect('../../admin.php');
}

if ($targetId === $me) {
    set_flash('err','You cannot change your own status.');
    redirect('../../admin.php');
}

if (strtolower($u['role']) === 'admin') {
    set_flash('err','You cannot change another admin’s status.');
    redirect('../../admin.php');
}

$newStatus = ($u['status'] === 'blocked') ? 'active' : 'blocked';
$profiles->updateStatus($targetId, $newStatus);

set_flash('ok', "Status changed to {$newStatus}.");
redirect('../../admin.php');
