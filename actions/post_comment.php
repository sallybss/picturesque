<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['profile_id'])) { header('Location: ../auth/login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../index.php'); exit; }

$me         = (int)$_SESSION['profile_id'];
$picture_id = isset($_POST['picture_id']) ? (int)$_POST['picture_id'] : 0;
$content    = trim($_POST['comment_content'] ?? '');

/** If your column is named `parent_comment` (no _id), change the name here: */
$parentField = 'parent_comment_id'; // ← change to 'parent_comment' if that's your actual column name

$parent_id  = (isset($_POST['parent_comment_id']) && $_POST['parent_comment_id'] !== '')
              ? (int)$_POST['parent_comment_id'] : null;

if ($picture_id <= 0 || $content === '') {
  set_flash('err','Please write a comment.');
  header('Location: ../picture.php?id=' . $picture_id);
  exit;
}

$conn = db();

/* If replying, ensure the parent exists and belongs to this picture */
if ($parent_id !== null) {
  $stmt = $conn->prepare("SELECT 1 FROM comments WHERE comment_id=? AND picture_id=?");
  $stmt->bind_param('ii', $parent_id, $picture_id);
  $stmt->execute();
  $ok = (bool)$stmt->get_result()->fetch_row();
  $stmt->close();
  if (!$ok) {
    set_flash('err','Reply failed: parent comment not found.');
    header('Location: ../picture.php?id=' . $picture_id);
    exit;
  }
}

/* Insert (use the correct parent column name) */
$sql = "
  INSERT INTO comments (picture_id, profile_id, {$parentField}, comment_content, created_at)
  VALUES (?, ?, ?, ?, NOW())
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iiis', $picture_id, $me, $parent_id, $content);
$stmt->execute();
$stmt->close();
$conn->close();

set_flash('ok','Comment added!');
header('Location: ../picture.php?id=' . $picture_id . '#comments');
exit;