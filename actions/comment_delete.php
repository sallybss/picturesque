<?php
require_once __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../index.php'); exit; }
if (!check_csrf($_POST['csrf'] ?? null)) { set_flash('err','Invalid request.'); header('Location: ../index.php'); exit; }

$me = Auth::requireUserOrRedirect('../auth/login.php');

$commentId = (int)($_POST['comment_id'] ?? 0);
$pictureId = (int)($_POST['picture_id'] ?? 0);
if ($commentId <= 0 || $pictureId <= 0) { set_flash('err','Bad request.'); header('Location: ../index.php'); exit; }

$profiles = new ProfileRepository();
$meRow = $profiles->getHeader($me);
$isAdmin = strtolower(trim($meRow['role'] ?? '')) === 'admin';
if (!$isAdmin) { set_flash('err','Admins only.'); header('Location: ../picture.php?id='.$pictureId); exit; }

$st = DB::get()->prepare("SELECT picture_id FROM comments WHERE comment_id = ? LIMIT 1");
$st->bind_param('i', $commentId); $st->execute();
$row = $st->get_result()->fetch_assoc(); $st->close();
if (!$row) { set_flash('err','Comment not found.'); header('Location: ../picture.php?id='.$pictureId); exit; }

$del = DB::get()->prepare("DELETE FROM comments WHERE comment_id = ?");
$del->bind_param('i', $commentId);
$del->execute(); $del->close();

set_flash('ok','Comment deleted.');
header('Location: ../picture.php?id='.$pictureId);
