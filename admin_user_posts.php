<?php
require_once __DIR__ . '/includes/init.php';


$me = Auth::requireAdminOrRedirect('./index.php');

$userId = (int)($_GET['id'] ?? 0);
if ($userId <= 0) { set_flash('err', 'Missing user id.'); header('Location: ./admin.php'); exit; }

$paths = new Paths();

$profiles = new ProfileRepository();
$meRow = $profiles->getHeader($me);
$isAdmin = (($meRow['role'] ?? 'user') === 'admin');
if (!$isAdmin) { set_flash('err', 'Admins only.'); header('Location: ./index.php'); exit; }

$user = $profiles->getById($userId);
if (!$user) { set_flash('err', 'User not found.'); header('Location: ./admin.php'); exit; }

$pictures = new PictureRepository();
$posts = $pictures->listByProfile($userId);

$publicUploads = $paths->uploads;
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
    <?php $cover = img_from_db($p['picture_url']); ?>
    <article class="card">
      <img src="<?= $cover ?>" alt="">
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
</section>

    </main>
  </div>
</body>
</html>
