<?php
require_once __DIR__ . '/../../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../../index.php');
}

if (!check_csrf($_POST['csrf'] ?? null)) {
    set_flash('err', 'Invalid request.');
    redirect('../../index.php');
}

$me = Auth::requireUserOrRedirect('../../auth/login.php');

$pictureId = (int)($_POST['picture_id'] ?? 0);
$parentId  = isset($_POST['parent_comment_id']) && $_POST['parent_comment_id'] !== ''
    ? (int)$_POST['parent_comment_id']
    : null;

$content = trim($_POST['comment_content'] ?? '');
$content = mb_substr($content, 0, 500);

if ($pictureId <= 0) {
    set_flash('err', 'Picture not specified.');
    redirect('../../index.php');
}

$redirect = '../../picture.php?id=' . $pictureId;

if (mb_strlen($content) < 5) {
    set_flash('err', 'Comment is too short (min 5 characters).');
    redirect($redirect);
}

$commentsRepo = new CommentRepository();

if (!$commentsRepo->pictureExists($pictureId)) {
    set_flash('err', 'Picture not found.');
    redirect('../../index.php');
}

if ($parentId !== null && !$commentsRepo->parentExists($parentId, $pictureId)) {
    set_flash('err', 'Reply target not found.');
    redirect($redirect);
}

$maxPerMinute = 2;
$recent       = $commentsRepo->countRecentForUser((int)$me, 1); 

if ($recent >= $maxPerMinute) {
    $now = time();
    $_SESSION['comment_rate_limit_until']   = $now + 60;
    $_SESSION['comment_rate_limit_picture'] = $pictureId;
    redirect($redirect);
}

$commentsRepo->add($pictureId, (int)$me, $content, $parentId);

set_flash('ok', 'Comment posted!');
redirect($redirect);