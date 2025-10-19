<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';

if (empty($_SESSION['profile_id'])) { header('Location: ../auth/login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../profile.php'); exit; }
if (!csrf_check($_POST['csrf'] ?? null)) { set_flash('err','Invalid request.'); header('Location: ../profile.php'); exit; }

$me  = (int)$_SESSION['profile_id'];
$pid = (int)($_POST['picture_id'] ?? 0);
if ($pid <= 0) { set_flash('err','Invalid picture.'); header('Location: ../profile.php'); exit; }

$conn = db();

$stmt = $conn->prepare("SELECT picture_url FROM pictures WHERE picture_id=? AND profile_id=? LIMIT 1");
$stmt->bind_param('ii', $pid, $me);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) { $conn->close(); set_flash('err','Not found.'); header('Location: ../profile.php'); exit; }

$conn->begin_transaction();
try {
  $d1 = $conn->prepare("DELETE FROM comments WHERE picture_id=?");
  $d1->bind_param('i', $pid); $d1->execute(); $d1->close();

  $d2 = $conn->prepare("DELETE FROM likes WHERE picture_id=?");
  $d2->bind_param('i', $pid); $d2->execute(); $d2->close();

  $d3 = $conn->prepare("DELETE FROM pictures WHERE picture_id=? AND profile_id=?");
  $d3->bind_param('ii', $pid, $me); $d3->execute(); $d3->close();

  $conn->commit();
} catch (Throwable $e) {
  $conn->rollback();
  $conn->close();
  set_flash('err','Could not delete. Try again.');
  header('Location: ../profile.php'); exit;
}
$conn->close();

$file = __DIR__ . '/../uploads/' . basename($row['picture_url']);
if (is_file($file)) { @unlink($file); }

set_flash('ok','Picture deleted.');
header('Location: ../profile.php'); exit;
