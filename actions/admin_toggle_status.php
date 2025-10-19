<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/admin_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../admin.php'); exit; }
if (!csrf_check($_POST['csrf'] ?? null)) { set_flash('err','Invalid CSRF token.'); header('Location: ../admin.php'); exit; }

$me       = (int)($_SESSION['profile_id'] ?? 0);
$targetId = (int)($_POST['profile_id'] ?? 0);
if ($targetId <= 0) { set_flash('err','Missing user.'); header('Location: ../admin.php'); exit; }

$conn = db();
require_admin($conn, $me);

$stmt = $conn->prepare("SELECT role, status FROM profiles WHERE profile_id=?");
$stmt->bind_param('i', $targetId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) { set_flash('err','User not found.'); header('Location: ../admin.php'); exit; }
if ($targetId === $me) { set_flash('err','You cannot change your own status.'); header('Location: ../admin.php'); exit; }
if (($user['role'] ?? 'user') === 'admin') { set_flash('err','You cannot change another admin\'s status.'); header('Location: ../admin.php'); exit; }

$new = ($user['status'] === 'blocked') ? 'active' : 'blocked';

$upd = $conn->prepare("UPDATE profiles SET status=? WHERE profile_id=?");
$upd->bind_param('si', $new, $targetId);
$upd->execute();
$upd->close();
$conn->close();

set_flash('ok', 'Status changed to ' . $new . '.');
header('Location: ../admin.php'); exit;
