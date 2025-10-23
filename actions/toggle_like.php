<?php
require_once __DIR__ . '/../includes/init.php';

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
