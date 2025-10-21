<?php
require __DIR__ . '/includes/flash.php';
require __DIR__ . '/includes/sidebar.php';
require __DIR__ . '/includes/db_class.php';
require __DIR__ . '/includes/auth_class.php';
require __DIR__ . '/includes/paths_class.php';
require __DIR__ . '/includes/profile_repository.php';

$me = Auth::requireAdminOrRedirect('./index.php');


$paths = new Paths();

$profiles = new ProfileRepository();
$meRow = $profiles->getHeader($me);
$isAdmin = (($meRow['role'] ?? 'user') === 'admin');
if (!$isAdmin) { header('Location: ./index.php'); exit; }

$q = trim($_GET['q'] ?? '');
$users = $profiles->searchUsersWithStats($q);

$publicUploads = $paths->uploads;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=8">
</head>
<body>
  <?php if ($m = get_flash('ok')): ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
  <?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

  <div class="layout">
    <?php render_sidebar(['isAdmin' => true]); ?>

    <main class="content">
      <div class="content-top">
        <h1 class="page-title">Overview</h1>

        <form class="search-wrap" method="get" action="admin.php">
          <input class="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search users">
        </form>

        <div class="userbox">
          <span class="avatar" style="background-image:url('<?= !empty($meRow['avatar_photo']) ? $publicUploads . htmlspecialchars($meRow['avatar_photo']) : 'https://placehold.co/28x28?text=%20' ?>');"></span>
          <span class="username"><?= htmlspecialchars($meRow['display_name'] ?? 'Admin') ?></span>
        </div>
      </div>

      <p class="admin-subtitle">Users (<?= count($users) ?> total)</p>

      <div class="admin-list">
        <?php foreach ($users as $u): ?>
          <?php $a = !empty($u['avatar_photo']) ? $publicUploads . htmlspecialchars($u['avatar_photo']) : 'https://placehold.co/32x32?text=%20'; ?>
          <div class="admin-item">
            <div class="ai-left">
              <img class="ai-avatar" src="<?= $a ?>" alt="">
              <div class="ai-meta">
                <div class="ai-name"><?= htmlspecialchars($u['display_name']) ?></div>
                <div class="ai-sub">
                  <span><?= htmlspecialchars($u['login_email']) ?></span>
                  <span>• Joined <?= htmlspecialchars(substr($u['created_at'], 0, 10)) ?></span>
                  <span>• Posts <?= (int)$u['posts'] ?></span>
                </div>
              </div>
            </div>

            <div class="ai-center">
              <span class="status-badge status-<?= htmlspecialchars($u['status']) ?>"><?= htmlspecialchars($u['status']) ?></span>
              <span class="role-badge"><?= htmlspecialchars($u['role']) ?></span>
            </div>

            <div class="ai-actions">
              <?php if ((int)$u['profile_id'] !== $me && ($u['role'] ?? 'user') !== 'admin'): ?>
                <form class="inline" method="post" action="./actions/admin_toggle_status.php">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                  <input type="hidden" name="profile_id" value="<?= (int)$u['profile_id'] ?>">
                  <button class="btn-ghost pill" type="submit">
                    <?= $u['status'] === 'blocked' ? 'Unblock' : 'Block' ?>
                  </button>
                </form>

                <form class="inline" method="post" action="./actions/admin_delete_user.php" onsubmit="return confirm('Delete this user and all their posts/likes/comments? This cannot be undone.');">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                  <input type="hidden" name="profile_id" value="<?= (int)$u['profile_id'] ?>">
                  <button class="btn-danger pill" type="submit">Delete</button>
                </form>
              <?php endif; ?>

              <a class="btn-ghost pill" href="./admin_user_posts.php?id=<?= (int)$u['profile_id'] ?>">View posts</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </main>
  </div>
</body>
</html>
