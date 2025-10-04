<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';

if (empty($_SESSION['profile_id'])) { header('Location: ../auth/login.php'); exit; }

$picture_id = isset($_POST['picture_id']) ? (int)$_POST['picture_id'] : 0;
if ($picture_id <= 0) { header('Location: ../index.php'); exit; }

$conn = db();

// does a like already exist?
$chk = $conn->prepare("SELECT like_id FROM likes WHERE picture_id = ? AND profile_id = ?");
$chk->bind_param('ii', $picture_id, $_SESSION['profile_id']);
$chk->execute();
$chk->store_result();

if ($chk->num_rows > 0) {
  // unlike
  $chk->close();
  $del = $conn->prepare("DELETE FROM likes WHERE picture_id = ? AND profile_id = ?");
  $del->bind_param('ii', $picture_id, $_SESSION['profile_id']);
  $del->execute();
  $del->close();
} else {
  // like
  $chk->close();
  $ins = $conn->prepare("INSERT INTO likes (picture_id, profile_id) VALUES (?, ?)");
  $ins->bind_param('ii', $picture_id, $_SESSION['profile_id']);
  $ins->execute();
  $ins->close();
}

$conn->close();

// send back to the page you came from
$back = $_SERVER['HTTP_REFERER'] ?? '../index.php';
header("Location: $back");