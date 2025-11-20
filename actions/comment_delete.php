<?php
require_once __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../index.php');
}

if (!check_csrf($_POST['csrf'] ?? null)) {
    set_flash('err', 'Invalid request.');
    redirect('../index.php');
}

$me        = Auth::requireUserOrRedirect('../auth/login.php');
$commentId = (int)($_POST['comment_id'] ?? 0);
$pictureId = (int)($_POST['picture_id'] ?? 0);

if ($commentId <= 0 || $pictureId <= 0) {
    set_flash('err', 'Bad request.');
    redirect('../index.php');
}

$profiles = new ProfileRepository();
$meRow    = $profiles->getHeader($me);
$isAdmin  = strtolower(trim($meRow['role'] ?? '')) === 'admin';

if (!$isAdmin) {
    set_flash('err', 'Admins only.');
    redirect("../picture.php?id=$pictureId");
}

$repo = new CommentRepository();
$repo->delete($commentId);

set_flash('ok', 'Comment deleted.');
redirect("../picture.php?id=$pictureId");
