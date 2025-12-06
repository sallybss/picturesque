<?php
require_once __DIR__ . '/../../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !check_csrf($_POST['csrf'] ?? null)) {
    set_flash('err', 'Invalid request.');
    redirect('../../index.php');  
}

$me = Auth::requireAdminOrRedirect('../../index.php');

$commentId = (int)($_POST['comment_id'] ?? 0);
$pictureId = (int)($_POST['picture_id'] ?? 0);

if ($commentId <= 0 || $pictureId <= 0) {
    set_flash('err', 'Bad request.');
    redirect('../../index.php');
}

if (
    isset($_SESSION['comment_rate_limit_picture'], $_SESSION['comment_rate_limit_until']) &&
    (int)$_SESSION['comment_rate_limit_picture'] === $pictureId
) {
    unset($_SESSION['comment_rate_limit_picture'], $_SESSION['comment_rate_limit_until']);
}

$repo = new CommentRepository();
$repo->delete($commentId);

set_flash('ok', 'Comment deleted.');
redirect('../../picture.php?id=' . $pictureId);
exit;
