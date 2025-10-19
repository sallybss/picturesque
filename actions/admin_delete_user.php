<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/admin_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../admin.php'); exit; }
if (!csrf_check($_POST['csrf'] ?? null)) { set_flash('err','Invalid CSRF token.'); header('Location: ../admin.php'); exit; }

$targetId = (int)($_POST['profile_id'] ?? 0);
$me       = (int)($_SESSION['profile_id'] ?? 0);
if ($targetId <= 0) { set_flash('err','Missing user.'); header('Location: ../admin.php'); exit; }

$conn = db();
require_admin($conn, $me);

$stmt = $conn->prepare("SELECT role FROM profiles WHERE profile_id=?");
$stmt->bind_param('i', $targetId);
$stmt->execute();
$stmt->bind_result($role);
$found = $stmt->fetch();
$stmt->close();

if (!$found) { $conn->close(); set_flash('err','User not found.'); header('Location: ../admin.php'); exit; }
if ($targetId === $me) { $conn->close(); set_flash('err','You cannot delete yourself.'); header('Location: ../admin.php'); exit; }
if (strtolower($role) === 'admin') { $conn->close(); set_flash('err','You cannot delete another admin.'); header('Location: ../admin.php'); exit; }

$del = $conn->prepare("DELETE FROM profiles WHERE profile_id=?");
$del->bind_param('i', $targetId);
$del->execute();
$del->close();
$conn->close();

set_flash('ok','User deleted.');
header('Location: ../admin.php'); exit;
