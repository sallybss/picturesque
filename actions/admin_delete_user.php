<?php
require_once __DIR__ . '/../includes/init.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../admin.php'); exit; }
if (!check_csrf($_POST['csrf'] ?? null)) { set_flash('err','Invalid CSRF token.'); header('Location: ../admin.php'); exit; }

$me = Auth::requireAdminOrRedirect('../index.php');

$targetId = (int)($_POST['profile_id'] ?? 0);
if ($targetId <= 0) { set_flash('err','Missing user.'); header('Location: ../admin.php'); exit; }

$profiles = new ProfileRepository();

$targetRole = $profiles->getRole($targetId);
if ($targetRole === null) { set_flash('err','User not found.'); header('Location: ../admin.php'); exit; }
if ($targetId === $me) { set_flash('err','You cannot delete yourself.'); header('Location: ../admin.php'); exit; }
if (strtolower($targetRole) === 'admin') { set_flash('err','You cannot delete another admin.'); header('Location: ../admin.php'); exit; }

$profiles->deleteById($targetId);

set_flash('ok','User deleted.');
header('Location: ../admin.php'); 
exit;
