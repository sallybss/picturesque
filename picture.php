<?php
require __DIR__ . '/includes/flash.php';
require __DIR__ . '/includes/sidebar.php';
require __DIR__ . '/includes/db_class.php';
require __DIR__ . '/includes/auth_class.php';
require __DIR__ . '/includes/paths_class.php';
require __DIR__ . '/includes/profile_repository.php';
require __DIR__ . '/includes/picture_repository.php';
require __DIR__ . '/includes/comment_repository.php';

$me = Auth::requireUserOrRedirect('./auth/login.php');

$picture_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($picture_id <= 0) { header('Location: ./index.php'); exit; }

$paths = new Paths();

$profiles = new ProfileRepository();
$meRow = $profiles->getHeader($me);
$isAdmin = ($meRow['role'] ?? '') === 'admin';

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
    if (isset($byId[(int)$pid])) $byId[(int)$pid]['children'][] = &$node; else $rootComments[] = &$node;
  }
}
unset($node);

function render_comment($c, $depth, $picture_id, $uploads) {
  $avatar = !empty($c['avatar_photo']) ? $uploads . htmlspecialchars($c['avatar_photo']) : 'https://placehold.co/24x24?text=%20';
  $d = max(0, min(4, (int)$depth));
  ?>
  <div class="comment" id="c-<?= (int)$c['comment_id'] ?>" data-depth="<?= $d ?>">
    <div class="c-head">
      <img class="c-avatar" src="<?= $avatar ?>" alt="">
      <b class="c-name"><?= htmlspecialchars($c['display_name']) ?></b>
      <span class="c-time"><?= htmlspecialchars(substr($c['created_at'], 0, 16)) ?></span>
    </div>

    <div class="c-body"><?= nl2br(htmlspecialchars($c['comment_content'])) ?></div>

    <div class="c-actions">
      <button type="button" class="link-btn js-reply" data-target="rf-<?= (int)$c['comment_id'] ?>">Reply</button>
    </div>

    <form id="rf-<?= (int)$c['comment_id'] ?>" class="reply-form" method="post" action="./actions/post_comment.php">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
  <input type="hidden" name="picture_id" value="<?= (int)$picture_id ?>">
  <input type="hidden" name="parent_comment_id" value="<?= (int)$c['comment_id'] ?>">
  <textarea name="comment_content" rows="2" class="input" placeholder="Write a reply…" required></textarea>
  <div class="reply-actions">
    <button type="submit" class="btn" name="submit">Reply</button>
  </div>
</form>


    <?php if (!empty($c['children'])): ?>
      <div class="c-children">
        <?php foreach ($c['children'] as $child) render_comment($child, $depth + 1, $picture_id, $uploads); ?>
      </div>
    <?php endif; ?>
  </div>
  <?php
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($pic['picture_title']) ?> · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=12">
</head>
<body>

<?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>
<?php if ($m = get_flash('ok')): ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>

<div class="layout">
  <?php render_sidebar(['isAdmin' => $isAdmin]); ?>

  <main class="content">
    <div class="backbar">
      <a class="link" href="./index.php">← Back to feed</a>
    </div>

    <div class="single-wrap">
      <div class="card">
        <img src="<?= $paths->uploads . htmlspecialchars($pic['picture_url']) ?>" alt="">
        <div class="pad">
          <h2 class="title"><?= htmlspecialchars($pic['picture_title']) ?></h2>
          <?php if (!empty($pic['picture_description'])): ?>
            <p class="muted"><?= htmlspecialchars($pic['picture_description']) ?></p>
          <?php endif; ?>
          <div class="counts">
            by <b><?= htmlspecialchars($pic['display_name']) ?></b>
            · ❤ <?= (int)$pic['like_count'] ?> · 💬 <?= (int)$pic['comment_count'] ?>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="pad">
          <div class="topbar">
            <h3 class="subtitle">Comments (<?= (int)$pic['comment_count'] ?>)</h3>
          </div>

          <form method="post" action="./actions/post_comment.php" class="comment-form">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
  <input type="hidden" name="picture_id" value="<?= (int)$pic['picture_id'] ?>">
  <textarea name="comment_content" rows="3" class="input" placeholder="Write a comment..." required></textarea>
  <div class="form-actions">
    <button class="btn" type="submit" name="submit">Post comment</button>
  </div>
</form>


          <div id="comments">
            <?php if (!$rootComments): ?>
              <p class="muted">No comments yet. Be the first!</p>
            <?php else: ?>
              <?php foreach ($rootComments as $root) render_comment($root, 0, $picture_id, $paths->uploads); ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
document.addEventListener('click', function (e) {
  const btn = e.target.closest('.js-reply');
  if (!btn) return;
  const id = btn.getAttribute('data-target');
  const el = document.getElementById(id);
  if (!el) return;
  el.style.display = (!el.style.display || el.style.display === 'none') ? 'block' : 'none';
});
</script>

</body>
</html>
