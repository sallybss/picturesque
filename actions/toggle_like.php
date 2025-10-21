<?php
require __DIR__ . '/../includes/flash.php';
require __DIR__ . '/../includes/db_class.php';
require __DIR__ . '/../includes/auth_class.php';
require __DIR__ . '/../includes/like_repository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../index.php'); exit; }
if (!check_csrf($_POST['csrf'] ?? null))   { header('Location: ../index.php'); exit; }

$me  = Auth::requireUserOrRedirect('../auth/login.php');
$pic = (int)($_POST['picture_id'] ?? 0);
if ($pic <= 0) { header('Location: ../index.php'); exit; }

$likes = new LikeRepository();
$likes->toggle($pic, $me);

$back = $_SERVER['HTTP_REFERER'] ?? '../index.php';
header("Location: $back");
exit;
