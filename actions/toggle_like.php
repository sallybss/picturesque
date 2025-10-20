<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';

if (empty($_SESSION['profile_id'])) { 
  header('Location: ../auth/login.php'); 
  exit; 
}

$me = (int)$_SESSION['profile_id'];
$pic = (int)($_POST['picture_id'] ?? 0);
if ($pic <= 0) { 
  header('Location: ../index.php'); 
  exit; 
}

$conn = db();

/* check if this user already liked the picture */
$chk = $conn->prepare('SELECT like_id FROM likes WHERE picture_id=? AND profile_id=?');
$chk->bind_param('ii', $pic, $me);
$chk->execute();
$chk->store_result();

if ($chk->num_rows > 0) {
  $chk->close();
  $stmt = $conn->prepare('DELETE FROM likes WHERE picture_id=? AND profile_id=?');
  $stmt->bind_param('ii', $pic, $me);
} else {
  $chk->close();
  $stmt = $conn->prepare('INSERT INTO likes (picture_id, profile_id) VALUES (?, ?)');
  $stmt->bind_param('ii', $pic, $me);
}

$stmt->execute();
$stmt->close();
$conn->close();

$back = $_SERVER['HTTP_REFERER'] ?? '../index.php';
header("Location: $back");
exit;
