<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';

if (empty($_SESSION['profile_id'])) {
  header('Location: ../auth/login.php'); exit;
}

$me   = (int)$_SESSION['profile_id'];
$pid  = isset($_POST['picture_id']) ? (int)$_POST['picture_id'] : 0;
$csrf = $_POST['csrf'] ?? null;

if ($pid <= 0 || !check_csrf($csrf)) {   // ← fixed here
  set_flash('err', 'Invalid request.');
  header('Location: ../profile.php'); exit;
}

$conn = db();

/* 1) Make sure the picture exists AND belongs to me */
$stmt = $conn->prepare("SELECT picture_url FROM pictures WHERE picture_id = ? AND profile_id = ?");
$stmt->bind_param('ii', $pid, $me);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
  set_flash('err', 'Picture not found or not yours.');
  $conn->close();
  header('Location: ../profile.php'); exit;
}

$conn->begin_transaction();

try {
  // If you have ON DELETE CASCADE FKs for comments/likes, you can skip these two deletes.
  $stmt = $conn->prepare("DELETE FROM comments WHERE picture_id = ?");
  $stmt->bind_param('i', $pid);
  $stmt->execute();
  $stmt->close();

  $stmt = $conn->prepare("DELETE FROM likes WHERE picture_id = ?");
  $stmt->bind_param('i', $pid);
  $stmt->execute();
  $stmt->close();

  $stmt = $conn->prepare("DELETE FROM pictures WHERE picture_id = ? AND profile_id = ?");
  $stmt->bind_param('ii', $pid, $me);
  $stmt->execute();
  $stmt->close();

  $conn->commit();
} catch (Throwable $e) {
  $conn->rollback();
  $conn->close();
  set_flash('err', 'Could not delete picture. Try again.');
  header('Location: ../profile.php'); exit;
}

$conn->close();

/* 3) Remove the file from /uploads (ignore if missing) */
$filename = basename($row['picture_url']);
$filePath = __DIR__ . '/../uploads/' . $filename;
if (is_file($filePath)) { @unlink($filePath); }

/* 4) Done */
set_flash('ok', 'Picture deleted');
header('Location: ../profile.php'); exit;