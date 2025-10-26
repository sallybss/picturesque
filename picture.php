<?php
require_once __DIR__ . '/includes/init.php';

$me = Auth::requireUserOrRedirect('./auth/login.php');
$profiles = new ProfileRepository();
$meRow = $profiles->getHeader($me);
$isAdmin = ($meRow['role'] ?? '') === 'admin';

$picture_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($picture_id <= 0) { header('Location: ./index.php'); exit; }

$pictures = new PictureRepository();
$pic = $pictures->getOneWithCountsAndAuthor($picture_id);
if (!$pic) { header('Location: ./index.php'); exit; }

$commentsRepo = new CommentRepository();
$rows = $commentsRepo->listForPictureWithAuthors($picture_id);

$byId = [];
foreach ($rows as $row) {
    $row['children'] = [];
    $byId[(int)$row['comment_id']] = $row;
}

$rootComments = [];
foreach ($byId as $id => &$node) {
    $pid = $node['parent_id'];          
    if ($pid === null) {
        $rootComments[] = &$node;
    } else {
        $pid = (int)$pid;
        if (isset($byId[$pid])) {
            $byId[$pid]['children'][] = &$node;
        } else {
            $rootComments[] = &$node;
        }
    }
}
unset($node);


function render_comment($c, $depth, $picture_id, $isAdmin) {
  $avatar = img_from_db($c['avatar_photo']);
  $d = max(0, min(4, (int)$depth));
  ?>
  <div class="comment" id="c-<?= (int)$c['comment_id'] ?>" data-depth="<?= $d ?>">
    <div class="c-head">
      <img class="c-avatar" src="<?= $avatar ?>" alt="">
      <b class="c-name"><?= htmlspecialchars($c['display_name']) ?></b>
      <span class="c-time"><?= htmlspecialchars(substr($c['created_at'], 0, 16)) ?></span>
      <?php if (!empty($isAdmin)): ?>
        <form method="post" action="./actions/comment_delete.php" style="display:inline; margin-left:auto">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
          <input type="hidden" name="comment_id" value="<?= (int)$c['comment_id'] ?>">
          <input type="hidden" name="picture_id" value="<?= (int)$picture_id ?>">
          <button class="iconbtn danger" onclick="return confirm('Delete this comment? Replies will be removed too.');">Delete</button>
        </form>
      <?php endif; ?>
    </div>
    <div class="c-body"><?= nl2br(htmlspecialchars($c['comment_content'])) ?></div>
    <div class="c-actions"><button type="button" class="link-btn js-reply" data-target="rf-<?= (int)$c['comment_id'] ?>">Reply</button></div>
    <form id="rf-<?= (int)$c['comment_id'] ?>" class="reply-form" method="post" action="./actions/post_comment.php" style="display:none;">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <input type="hidden" name="picture_id" value="<?= (int)$picture_id ?>">
      <input type="hidden" name="parent_comment_id" value="<?= (int)$c['comment_id'] ?>">
      <textarea name="comment_content" rows="2" class="input" placeholder="Write a reply…" required></textarea>
      <div class="reply-actions"><button type="submit" class="btn">Reply</button></div>
    </form>
    <?php if (!empty($c['children'])): ?>
      <div class="c-children">
        <?php foreach ($c['children'] as $child) render_comment($child, $depth + 1, $picture_id, $isAdmin); ?>
      </div>
    <?php endif; ?>
  </div>
  <?php
}
?>
...
<div class="single-wrap">
  <div class="card">
    <img src="<?= img_from_db($pic['picture_url']) ?>" alt="">
    <div class="pad">
      <h2 class="title"><?= htmlspecialchars($pic['picture_title']) ?></h2>
      <?php if (!empty($pic['picture_description'])): ?>
        <p class="muted"><?= htmlspecialchars($pic['picture_description']) ?></p>
      <?php endif; ?>
      <div class="counts">
        by <b><?= htmlspecialchars($pic['display_name']) ?></b> · ❤ <?= (int)$pic['like_count'] ?> · 💬 <?= (int)$pic['comment_count'] ?>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="pad">
      <div class="topbar"><h3 class="subtitle">Comments (<?= (int)$pic['comment_count'] ?>)</h3></div>
      ...
      <div id="comments">
        <?php if (!$rootComments): ?>
          <p class="muted">No comments yet. Be the first!</p>
        <?php else: ?>
          <?php foreach ($rootComments as $root) render_comment($root, 0, $picture_id, $isAdmin); ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
