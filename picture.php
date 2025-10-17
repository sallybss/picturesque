<?php
require __DIR__ . '/includes/flash.php';
require __DIR__ . '/includes/db.php';

if (empty($_SESSION['profile_id'])) { header('Location: ./auth/login.php'); exit; }

$picture_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($picture_id <= 0) { header('Location: ./index.php'); exit; }

$conn = db();

/* Dynamic base for uploads (works in subfolder) */
$baseUrl       = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$publicUploads = $baseUrl . '/uploads/';

/* Picture + author + counts */
$sql = "
  SELECT
    p.picture_id, p.profile_id, p.picture_title, p.picture_description, p.picture_url, p.created_at,
    pr.display_name,
    COALESCE(l.cnt,0) AS like_count,
    COALESCE(c.cnt,0) AS comment_count
  FROM pictures p
  JOIN profiles pr ON pr.profile_id = p.profile_id
  LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM likes    GROUP BY picture_id) l ON l.picture_id = p.picture_id
  LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM comments GROUP BY picture_id) c ON c.picture_id = p.picture_id
  WHERE p.picture_id = ?
  LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $picture_id);
$stmt->execute();
$pic = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pic) { $conn->close(); header('Location: ./index.php'); exit; }

/* ====== COMMENTS (threaded) ====== */

/* CHANGE HERE IF NEEDED: if your column is named `parent_comment` (no _id), set to 'parent_comment' */
$parentField = 'parent_comment_id';

$stmt = $conn->prepare("
  SELECT
    c.comment_id,
    c.{$parentField} AS parent_id,
    c.comment_content,
    c.created_at,
    c.profile_id,
    p.display_name,
    p.avatar_photo
  FROM comments c
  JOIN profiles p ON p.profile_id = c.profile_id
  WHERE c.picture_id = ?
  ORDER BY c.created_at ASC
");
$stmt->bind_param('i', $picture_id);
$stmt->execute();
$res = $stmt->get_result();

/* Build an in-memory tree */
$byId = [];
while ($row = $res->fetch_assoc()) {
  $row['children'] = [];
  $byId[(int)$row['comment_id']] = $row;
}
$stmt->close();
$conn->close();

$rootComments = [];
foreach ($byId as $id => &$node) {
  $pid = $node['parent_id'];
  if ($pid === null) {
    $rootComments[] = &$node;
  } else {
    if (isset($byId[(int)$pid])) {
      $byId[(int)$pid]['children'][] = &$node;
    } else {
      $rootComments[] = &$node; // fallback (shouldn't happen with FK)
    }
  }
}
unset($node);

/* Recursive renderer */
function render_comment($c, $depth, $picture_id, $publicUploads) {
  $avatar = !empty($c['avatar_photo'])
    ? $publicUploads . htmlspecialchars($c['avatar_photo'])
    : 'https://placehold.co/24x24?text=%20';
  $indent = min($depth, 4); // cap indent visually
  ?>
  <div class="comment" id="c-<?= (int)$c['comment_id'] ?>" style="margin-left: <?= $indent * 16 ?>px">
    <div class="c-head" style="display:flex;align-items:center;gap:8px">
      <img class="c-avatar" src="<?= $avatar ?>" alt="" style="width:24px;height:24px;border-radius:50%;object-fit:cover">
      <b class="c-name"><?= htmlspecialchars($c['display_name']) ?></b>
      <span class="c-time" style="color:#6b7280;font-size:12px"><?= htmlspecialchars(substr($c['created_at'], 0, 16)) ?></span>
    </div>

    <div class="c-body" style="margin:6px 0 4px"><?= nl2br(htmlspecialchars($c['comment_content'])) ?></div>

    <div class="c-actions">
      <button type="button" class="link js-reply" data-target="rf-<?= (int)$c['comment_id'] ?>" style="background:none;border:0;color:#6b7280;cursor:pointer;font-size:12px;padding:0">Reply</button>
    </div>

    <!-- Inline reply form (hidden by default) -->
    <form id="rf-<?= (int)$c['comment_id'] ?>" class="reply-form" method="post" action="./actions/post_comment.php" style="display:none; margin-top:8px;">
      <input type="hidden" name="picture_id" value="<?= (int)$picture_id ?>">
      <input type="hidden" name="parent_comment_id" value="<?= (int)$c['comment_id'] ?>">
      <textarea name="comment_content" rows="2" class="input" placeholder="Write a reply…" required style="width:100%;padding:12px;border:1px solid #e5e7eb;border-radius:10px;outline:none"></textarea>
      <div style="margin-top:8px">
        <button type="submit" class="btn" name="submit" style="padding:8px 12px;border:none;border-radius:10px;background:#8ec6df;color:#fff;font-weight:600;cursor:pointer">Reply</button>
      </div>
    </form>

    <?php if (!empty($c['children'])): ?>
      <div class="c-children">
        <?php foreach ($c['children'] as $child) {
          render_comment($child, $depth + 1, $picture_id, $publicUploads);
        } ?>
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
  <link rel="stylesheet" href="./public/css/main.css">
  <style>
    .wrap{max-width:1000px;margin:24px auto;display:grid;grid-template-columns:1fr 380px;gap:24px}
    @media(max-width:1000px){.wrap{grid-template-columns:1fr}}
    .card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden}
    .card img{width:100%;display:block}
    .pad{padding:14px}
    .muted{color:#6b7280}
    .counts{color:#6b7280;font-size:13px}
    .comment{border-top:1px solid #e5e7eb;padding:12px 0}
    .input, textarea{width:100%;padding:12px;border:1px solid #e5e7eb;border-radius:10px;outline:none}
    .btn{padding:10px 14px;border:none;border-radius:10px;background:#8ec6df;color:#fff;font-weight:600;cursor:pointer}
    .btn:hover{background:#7dbad6}
    .topbar{display:flex;justify-content:space-between;align-items:center;margin:10px 0 16px}
    a.link{color:#2563eb;text-decoration:none}
  </style>
</head>
<body>

<div style="max-width:1000px;margin:12px auto">
  <a class="link" href="./index.php">← Back to feed</a>
</div>

<div class="wrap">
  <!-- Left: image + info -->
  <div class="card">
    <img src="<?= $publicUploads . htmlspecialchars($pic['picture_url']) ?>" alt="">
    <div class="pad">
      <h2 style="margin:0"><?= htmlspecialchars($pic['picture_title']) ?></h2>
      <?php if (!empty($pic['picture_description'])): ?>
        <p class="muted" style="margin:8px 0 0"><?= htmlspecialchars($pic['picture_description']) ?></p>
      <?php endif; ?>
      <div class="counts" style="margin-top:10px">
        by <b><?= htmlspecialchars($pic['display_name']) ?></b>
        · ❤ <?= (int)$pic['like_count'] ?> · 💬 <?= (int)$pic['comment_count'] ?>
      </div>
    </div>
  </div>

  <!-- Right: comments -->
  <div class="card">
    <div class="pad">
      <div class="topbar">
        <h3 style="margin:0">Comments (<?= (int)$pic['comment_count'] ?>)</h3>
      </div>

      <?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>
      <?php if ($m = get_flash('ok')): ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>

      <!-- Root comment form -->
      <form method="post" action="./actions/post_comment.php">
        <input type="hidden" name="picture_id" value="<?= (int)$pic['picture_id'] ?>">
        <textarea name="comment_content" rows="3" class="input" placeholder="Write a comment..." required></textarea>
        <div style="margin-top:10px">
          <button class="btn" type="submit" name="submit">Post comment</button>
        </div>
      </form>

      <!-- Threaded comments -->
      <div id="comments" style="margin-top:14px">
        <?php if (!$rootComments): ?>
          <p class="muted">No comments yet. Be the first!</p>
        <?php else: ?>
          <?php foreach ($rootComments as $root) { render_comment($root, 0, $picture_id, $publicUploads); } ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Tiny JS to toggle inline reply forms -->
<script>
document.addEventListener('click', function (e) {
  const btn = e.target.closest('.js-reply');
  if (!btn) return;
  const id = btn.getAttribute('data-target');
  const el = document.getElementById(id);
  if (el) el.style.display = (!el.style.display || el.style.display === 'none') ? 'block' : 'none';
});
</script>

</body>
</html>