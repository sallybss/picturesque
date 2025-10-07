<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db.php';

if (empty($_SESSION['profile_id'])) { header('Location: ../auth/login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../profile.php'); exit; }

$me    = (int)$_SESSION['profile_id'];
$pid   = isset($_POST['picture_id']) ? (int)$_POST['picture_id'] : 0;
$title = trim($_POST['title'] ?? '');
$desc  = trim($_POST['desc'] ?? '');
$csrf  = $_POST['csrf'] ?? null;

if ($pid <= 0 || $title === '' || !csrf_check($csrf)) {
  set_flash('err','Invalid form.');
  header("Location: ../edit_picture.php?id=".$pid);
  exit;
}

$conn = db();

/* 1) Ownership: must be my picture */
$stmt = $conn->prepare("SELECT picture_url FROM pictures WHERE picture_id = ? AND profile_id = ?");
$stmt->bind_param('ii', $pid, $me);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
  $conn->close();
  set_flash('err','Picture not found or not yours.');
  header('Location: ../profile.php'); exit;
}

/* 2) If a new photo is uploaded, validate + move it */
$filenameToSave = $row['picture_url']; // default: keep old file

if (!empty($_FILES['photo']['name'])) {
  $photo = $_FILES['photo'];
  $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
  if (!in_array($photo['type'], $allowed)) {
    $conn->close();
    set_flash('err','Only JPG/PNG/GIF/WEBP allowed.');
    header("Location: ../edit_picture.php?id=".$pid); exit;
  }
  if ($photo['error'] !== UPLOAD_ERR_OK) {
    $conn->close();
    set_flash('err','Upload failed.');
    header("Location: ../edit_picture.php?id=".$pid); exit;
  }
  if ($photo['size'] > 10 * 1024 * 1024) {
    $conn->close();
    set_flash('err','Max size is 10MB.');
    header("Location: ../edit_picture.php?id=".$pid); exit;
  }

  /* Generate a safe unique filename */
  $ext = pathinfo($photo['name'], PATHINFO_EXTENSION);
  $newName = bin2hex(random_bytes(8)) . '.' . strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $ext));
  $destDir = realpath(__DIR__ . '/../uploads');

  if ($destDir === false) {
    $conn->close();
    set_flash('err','Upload folder missing.');
    header("Location: ../edit_picture.php?id=".$pid); exit;
  }

  $destPath = $destDir . DIRECTORY_SEPARATOR . $newName;
  if (!move_uploaded_file($photo['tmp_name'], $destPath)) {
    $conn->close();
    set_flash('err','Could not save file.');
    header("Location: ../edit_picture.php?id=".$pid); exit;
  }

  $filenameToSave = $newName;

  /* (optional) delete old file */
  $old = basename($row['picture_url']);
  $oldPath = $destDir . DIRECTORY_SEPARATOR . $old;
  if (is_file($oldPath)) { @unlink($oldPath); }
}

/* 3) Update DB */
$stmt = $conn->prepare("UPDATE pictures SET picture_title = ?, picture_description = ?, picture_url = ? WHERE picture_id = ? AND profile_id = ?");
$stmt->bind_param('sssii', $title, $desc, $filenameToSave, $pid, $me);
$stmt->execute();
$stmt->close();
$conn->close();

set_flash('ok','Picture updated');
header('Location: ../profile.php'); exit;
