<?php
require_once __DIR__ . '/../includes/init.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../index.php'); exit; }
if (!check_csrf($_POST['csrf'] ?? null)) { set_flash('err','Invalid request.'); header('Location: ../index.php'); exit; }

$me = Auth::requireUserOrRedirect('../auth/login.php');

$pictureId = (int)($_POST['picture_id'] ?? 0);
$content   = trim($_POST['comment_content'] ?? '');
$parentRaw = trim($_POST['parent_comment_id'] ?? '');
$parentId  = ($parentRaw === '') ? null : (int)$parentRaw;

if ($pictureId <= 0 || $content === '') {
    set_flash('err','Please write a comment.');
    header('Location: ../picture.php?id=' . $pictureId . '#comments'); exit;
}

try {
    $repo = new CommentRepository();

    if ($parentId !== null && !$repo->parentExists($parentId, $pictureId)) {
        set_flash('err','Reply failed: parent comment not found.');
        header('Location: ../picture.php?id=' . $pictureId . '#comments'); exit;
    }

    $repo->add($pictureId, $me, $content, $parentId);

    set_flash('ok','Comment added!');
    header('Location: ../picture.php?id=' . $pictureId . '#comments'); exit;

} catch (Throwable $e) {
    set_flash('err','Could not post your comment.');
    header('Location: ../picture.php?id=' . $pictureId . '#comments'); exit;
}
