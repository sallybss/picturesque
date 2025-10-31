<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/comment_repository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../index.php'); exit;
}
if (!check_csrf($_POST['csrf'] ?? null)) {
  set_flash('err','Invalid request.'); header('Location: ../index.php'); exit;
}

$me   = Auth::requireUserOrRedirect('../auth/login.php');

$pictureId = (int)($_POST['picture_id'] ?? 0);
$parentId  = isset($_POST['parent_comment_id']) && $_POST['parent_comment_id'] !== ''
           ? (int)$_POST['parent_comment_id'] : null;
$content   = trim($_POST['comment_content'] ?? '');   

if ($pictureId <= 0 || $content === '') {
  set_flash('err','Please write a comment.'); 
  header('Location: ../picture.php?id='.$pictureId); exit;
}

$exists = DB::get()->prepare('SELECT 1 FROM pictures WHERE picture_id=?');
$exists->bind_param('i', $pictureId);
$exists->execute();
if (!$exists->get_result()->fetch_row()) {
  $exists->close();
  set_flash('err','Picture not found.');
  header('Location: ../index.php'); exit;
}
$exists->close();

if ($parentId !== null) {
  $chk = DB::get()->prepare('SELECT 1 FROM comments WHERE comment_id=? AND picture_id=?');
  $chk->bind_param('ii', $parentId, $pictureId);
  $chk->execute();
  if (!$chk->get_result()->fetch_row()) {
    $chk->close();
    set_flash('err','Invalid reply target.');
    header('Location: ../picture.php?id='.$pictureId); exit;
  }
  $chk->close();
}

$repo = new CommentRepository();
$repo->add($pictureId, $me, $content, $parentId);

set_flash('ok', 'Comment added!');
header('Location: ../picture.php?id='.$pictureId); 
exit;
