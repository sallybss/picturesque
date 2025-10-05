<?php
require __DIR__ . '/includes/flash.php';
require __DIR__ . '/includes/db.php';

if (empty($_SESSION['profile_id'])) { header('Location: ./auth/login.php'); exit; }

$picture_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($picture_id <= 0) { header('Location: ./index.php'); exit; }

$conn = db();

/* Fetch the picture + author + counts */
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
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $picture_id);
$stmt->execute();
$pic = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pic) { $conn->close(); header('Location: ./index.php'); exit; }

/* Fetch comments with author names */
$cs = $conn->prepare("
  SELECT cm.comment_id, cm.comment_content, cm.created_at, pr.display_name
  FROM comments cm
  JOIN profiles pr ON pr.profile_id = cm.profile_id
  WHERE cm.picture_id = ?
  ORDER BY cm.created_at ASC
");
$cs->bind_param('i', $picture_id);
$cs->execute();
$comments = $cs->get_result()->fetch_all(MYSQLI_ASSOC);
$cs->close();
$conn->close();
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
    <img src="uploads/<?= htmlspecialchars($pic['picture_url']) ?>" alt="">
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

      <!-- Add comment -->
      <form method="post" action="./actions/post_comment.php">
        <input type="hidden" name="picture_id" value="<?= (int)$pic['picture_id'] ?>">
        <textarea name="comment_content" rows="3" class="input" placeholder="Write a comment..." required></textarea>
        <div style="margin-top:10px">
          <button class="btn" type="submit" name="submit">Post comment</button>
        </div>
      </form>

      <!-- List comments -->
      <div style="margin-top:14px">
        <?php if (!$comments): ?>
          <p class="muted">No comments yet. Be the first!</p>
        <?php else: ?>
          <?php foreach ($comments as $c): ?>
            <div class="comment">
              <div style="font-weight:600"><?= htmlspecialchars($c['display_name']) ?></div>
              <div style="margin-top:4px"><?= nl2br(htmlspecialchars($c['comment_content'])) ?></div>
              <div class="muted" style="margin-top:4px; font-size:12px">
                <?= htmlspecialchars($c['created_at']) ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

</body>
</html>