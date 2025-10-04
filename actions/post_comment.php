<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';

if (empty($_SESSION['profile_id'])) { header('Location: ../auth/login.php'); exit; }
if (!isset($_POST['submit'])) { header('Location: ../index.php'); exit; }

$picture_id = isset($_POST['picture_id']) ? (int)$_POST['picture_id'] : 0;
$content    = trim($_POST['comment_content'] ?? '');

if ($picture_id <= 0 || $content === '') {
  set_flash('err','Please write a comment.');
  header('Location: ../index.php'); exit;
}

$conn = db();
$stmt = $conn->prepare("INSERT INTO comments (picture_id, profile_id, comment_content) VALUES (?, ?, ?)");
$stmt->bind_param('iis', $picture_id, $_SESSION['profile_id'], $content);
$stmt->execute();
$stmt->close(); 
$conn->close();

set_flash('ok','Comment added!');
header('Location: ../picture.php?id=' . $picture_id);