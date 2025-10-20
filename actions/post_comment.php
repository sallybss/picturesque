<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['profile_id'])) { header('Location: ../auth/login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../index.php'); exit; }

$me         = (int)$_SESSION['profile_id'];
$picture_id = isset($_POST['picture_id']) ? (int)$_POST['picture_id'] : 0;
$content    = trim($_POST['comment_content'] ?? '');
$parent_raw = trim($_POST['parent_comment_id'] ?? '');
$parent_id  = ($parent_raw === '') ? null : (int)$parent_raw;

if ($picture_id <= 0 || $content === '') {
  set_flash('err','Please write a comment.');
  header('Location: ../picture.php?id=' . $picture_id . '#comments'); exit;
}

try {
  $conn = db();

  $parentCol = null;
  $res = $conn->query("SHOW COLUMNS FROM comments LIKE 'parent_comment_id'");
  if ($res && $res->num_rows) $parentCol = 'parent_comment_id';
  else {
    $res = $conn->query("SHOW COLUMNS FROM comments LIKE 'parent_comment'");
    if ($res && $res->num_rows) $parentCol = 'parent_comment';
  }

  if ($parent_id !== null) {
    $stmt = $conn->prepare("SELECT 1 FROM comments WHERE comment_id=? AND picture_id=?");
    $stmt->bind_param('ii', $parent_id, $picture_id);
    $stmt->execute();
    $ok = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    if (!$ok) {
      set_flash('err','Reply failed: parent comment not found.');
      header('Location: ../picture.php?id=' . $picture_id . '#comments'); exit;
    }
  }

  if ($parent_id === null || $parentCol === null) {
    $stmt = $conn->prepare("INSERT INTO comments (picture_id, profile_id, comment_content, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param('iis', $picture_id, $me, $content);
  } else {
    $sql  = "INSERT INTO comments (picture_id, profile_id, {$parentCol}, comment_content, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iiis', $picture_id, $me, $parent_id, $content);
  }

  $stmt->execute();
  $stmt->close();
  $conn->close();

  set_flash('ok','Comment added!');
  header('Location: ../picture.php?id=' . $picture_id . '#comments'); exit;

} catch (Throwable $e) {
  set_flash('err','Could not post your comment.');
  header('Location: ../picture.php?id=' . $picture_id . '#comments'); exit;
}
