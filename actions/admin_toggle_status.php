<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db_class.php';
require __DIR__ . '/../includes/auth_class.php';
require __DIR__ . '/../includes/profile_repository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../admin.php'); exit; }
if (!check_csrf($_POST['csrf'] ?? null)) { set_flash('err','Invalid CSRF token.'); header('Location: ../admin.php'); exit; }

$me = Auth::requireAdminOrRedirect('../index.php');

$targetId = (int)($_POST['profile_id'] ?? 0);
if ($targetId <= 0) { set_flash('err','Missing user.'); header('Location: ../admin.php'); exit; }

$profiles = new ProfileRepository();
$u = $profiles->getRoleAndStatus($targetId);

if (!$u) { set_flash('err','User not found.'); header('Location: ../admin.php'); exit; }
if ($targetId === $me) { set_flash('err','You cannot change your own status.'); header('Location: ../admin.php'); exit; }
if (strtolower($u['role'] ?? 'user') === 'admin') { set_flash('err','You cannot change another admin\'s status.'); header('Location: ../admin.php'); exit; }

$new = ($u['status'] === 'blocked') ? 'active' : 'blocked';
$profiles->updateStatus($targetId, $new);

set_flash('ok', 'Status changed to ' . $new . '.');
header('Location: ../admin.php');
exit;
