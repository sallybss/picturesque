<?php
require __DIR__ . '/includes/flash.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/admin_guard.php';
require __DIR__ . '/includes/sidebar.php';

if (empty($_SESSION['profile_id'])) { header('Location: ./auth/login.php'); exit; }

$me     = (int)$_SESSION['profile_id'];
$userId = (int)($_GET['id'] ?? 0);
if ($userId <= 0) { set_flash('err', 'Missing user id.'); header('Location: ./admin.php'); exit; }

$conn = db();
require_admin($conn, $me);

$u = $conn->prepare("SELECT display_name, email FROM profiles WHERE profile_id=?");
$u->bind_param('i', $userId);
$u->execute();
$user = $u->get_result()->fetch_assoc();
$u->close();

if (!$user) { set_flash('err', 'User not found.'); header('Location: ./admin.php'); exit; }

$p = $conn->prepare("
  SELECT
    p.picture_id, p.picture_title, p.picture_description, p.picture_url, p.created_at,
    COALESCE(l.cnt,0) AS like_count,
    COALESCE(c.cnt,0) AS comment_count
  FROM pictures p
  LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM likes    GROUP BY picture_id) l ON l.picture_id = p.picture_id
  LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM comments GROUP BY picture_id) c ON c.picture_id = p.picture_id
  WHERE p.profile_id = ?
  ORDER BY p.created_at DESC
");
$p->bind_param('i', $userId);
$p->execute();
$posts = $p->get_result()->fetch_all(MYSQLI_ASSOC);
$p->close();
$conn->close();

$baseUrl       = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$publicUploads = $baseUrl . '/uploads/';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin · Posts of <?= htmlspecialchars($user['display_name']) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=8">
</head>
<body>
  <?php if ($m = get_flash('ok')):  ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
  <?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

  <div class="layout">
    <?php render_sidebar(['isAdmin' => true]); ?>

    <main class="content">
      <div class="content-top">
        <h1 class="page-title">Posts · <?= htmlspecialchars($user['display_name']) ?> (<?= count($posts) ?>)</h1>
      </div>

      <section class="feed">
        <?php foreach ($posts as $p): ?>
          <article class="card">
            <img src="<?= $publicUploads . htmlspecialchars($p['picture_url']) ?>" alt="">
            <div class="card-body">
              <div class="card-title"><?= htmlspecialchars($p['picture_title']) ?></div>
              <?php if (!empty($p['picture_description'])): ?>
                <div class="card-desc"><?= htmlspecialchars($p['picture_description']) ?></div>
              <?php endif; ?>
              <div class="meta">
                <span class="counts">
                  <span class="muted">❤ <?= (int)$p['like_count'] ?></span>
                  <span class="muted">💬 <?= (int)$p['comment_count'] ?></span>
                </span>
                <span class="spacer"></span>
                <form method="post" action="./actions/admin_delete_picture.php"
                      onsubmit="return confirm('Delete this picture?');" style="display:inline">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                  <input type="hidden" name="picture_id" value="<?= (int)$p['picture_id'] ?>">
                  <button class="btn-danger pill" type="submit">Delete</button>
                </form>
              </div>
            </div>
          </article>
        <?php endforeach; ?>

        <?php if (!$posts): ?>
          <p class="muted">No posts.</p>
        <?php endif; ?>
      </section>
    </main>
  </div>
</body>
</html>
