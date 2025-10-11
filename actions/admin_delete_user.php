<?php
session_start();
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/admin_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../admin.php'); exit; }
if (!hash_equals(csrf_token(), $_POST['csrf'] ?? '')) {
  set_flash('err','Invalid CSRF token.'); header('Location: ../admin.php'); exit;
}

$targetId = (int)($_POST['profile_id'] ?? 0);
$me       = (int)($_SESSION['profile_id'] ?? 0);

if ($targetId <= 0) { set_flash('err','Missing user.'); header('Location: ../admin.php'); exit; }

$conn = db();
require_admin($conn, $me);

// Safety rails: cannot delete yourself or another admin
$stmt = $conn->prepare("SELECT role FROM profiles WHERE profile_id=?");
$stmt->bind_param('i', $targetId);
$stmt->execute();
$role = $stmt->get_result()->fetch_column();
$stmt->close();

if (!$role) { set_flash('err','User not found.'); header('Location: ../admin.php'); exit; }
if ($targetId === $me) { set_flash('err','You cannot delete yourself.'); header('Location: ../admin.php'); exit; }
if ($role === 'admin') { set_flash('err','You cannot delete another admin.'); header('Location: ../admin.php'); exit; }

// Delete profile (FKs will cascade to pictures/comments/likes/picture_category)
$del = $conn->prepare("DELETE FROM profiles WHERE profile_id=?");
$del->bind_param('i', $targetId);
$del->execute();
$del->close();
$conn->close();

set_flash('ok','User deleted.');
header('Location: ../admin.php'); exit;
