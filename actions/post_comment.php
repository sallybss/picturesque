<?php
require_once __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../index.php');
}

if (!check_csrf($_POST['csrf'] ?? null)) {
    set_flash('err', 'Invalid request.');
    redirect('../index.php');
}

$me = Auth::requireUserOrRedirect('../auth/login.php');

$pictureId = (int)($_POST['picture_id'] ?? 0);
$parentId  = isset($_POST['parent_comment_id']) && $_POST['parent_comment_id'] !== ''
    ? (int)$_POST['parent_comment_id']
    : null;
$content   = trim($_POST['comment_content'] ?? '');

$repo = new CommentRepository();


if ($pictureId <= 0 || $content === '') {
    set_flash('err', 'Please write a comment.');
    redirect("../picture.php?id=$pictureId");
}

if (!$repo->pictureExists($pictureId)) {
    set_flash('err', 'Picture not found.');
    redirect('../index.php');
}

if ($parentId !== null && !$repo->parentExists($parentId, $pictureId)) {
    set_flash('err', 'Invalid reply target.');
    redirect("../picture.php?id=$pictureId");
}


$repo->add($pictureId, $me, $content, $parentId);

set_flash('ok', 'Comment added!');
redirect("../picture.php?id=$pictureId");
