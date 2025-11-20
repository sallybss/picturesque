<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/views/topbar.php';
require_once __DIR__ . '/includes/views/sidebar.php';

$me = Auth::requireAdminOrRedirect('./index.php');

$paths = new Paths();

$profiles = new ProfileRepository();
$meRow = $profiles->getHeader($me);
$isAdmin = (strtolower($meRow['role'] ?? 'user') === 'admin');
if (!$isAdmin) {
  header('Location: ./index.php');
  exit;
}

$q = trim($_GET['q'] ?? '');
$users = $profiles->searchUsersWithStats($q);

$cssPath = __DIR__ . '/public/css/main.css';
$cssVer  = file_exists($cssPath) ? filemtime($cssPath) : time();
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Admin · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=<?= $cssVer ?>">
</head>

<body>
  <?php if ($m = get_flash('ok')): ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
  <?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

  <div class="layout">
    <?php render_sidebar(['isAdmin' => true]); ?>

    <main class="content">
      <div class="content-top">
        <div class="top-actions" style="display:flex; align-items:center; justify-content:space-between; width:100%;">
          <button class="hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false">☰</button>
          <?php render_topbar_userbox($meRow); ?>
        </div>
      </div>

      <div class="content-top">
        <h1 class="page-title">Overview</h1>

        <form class="search-wrap" method="get" action="admin.php">
          <input class="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search users">
          <button class="btn-primary search-btn" type="submit">Go</button>
        </form>
      </div>

      <p class="admin-subtitle">Users (<?= count($users) ?> total)</p>

      <div class="admin-list">
        <?php foreach ($users as $u): ?>
          <?php
          $a = !empty($u['avatar_photo'])
            ? img_from_db($u['avatar_photo'])
            : 'https://placehold.co/32x32?text=%20';
          ?>
          <div class="admin-item">
            <div class="ai-left">
              <img class="ai-avatar" src="<?= $a ?>" alt="">
              <div class="ai-meta">
                <div class="ai-name"><?= htmlspecialchars($u['display_name']) ?></div>
                <div class="ai-sub">
                  <span><?= htmlspecialchars($u['login_email']) ?></span>
                  <span>• Joined <?= htmlspecialchars(substr((string)$u['created_at'], 0, 10)) ?></span>
                  <span>• Posts <?= (int)$u['posts'] ?></span>
                </div>
              </div>
            </div>

            <div class="ai-center">
              <span class="status-badge status-<?= htmlspecialchars($u['status']) ?>"><?= htmlspecialchars($u['status']) ?></span>
              <span class="role-badge"><?= htmlspecialchars($u['role']) ?></span>
            </div>

            <div class="ai-actions">
              <?php if ((int)$u['profile_id'] !== $me && (strtolower($u['role'] ?? 'user') !== 'admin')): ?>
                <form class="inline" method="post" action="./actions/admin_toggle_status.php">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                  <input type="hidden" name="profile_id" value="<?= (int)$u['profile_id'] ?>">
                  <button class="btn-ghost pill" type="submit">
                    <?= ($u['status'] === 'blocked') ? 'Unblock' : 'Block' ?>
                  </button>
                </form>

                <form class="inline" method="post" action="./actions/admin_delete_user.php"
                  onsubmit="return confirm('Delete this user and all their posts/likes/comments? This cannot be undone.');">
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

  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
  <script>
    (function() {
      const body = document.body;
      const btn = document.getElementById('hamburger');
      const backdrop = document.getElementById('sidebarBackdrop');
      const closeBtn = document.getElementById('closeSidebar');

      function openMenu() {
        body.classList.add('sidebar-open');
        btn?.setAttribute('aria-expanded', 'true');
      }

      function closeMenu() {
        body.classList.remove('sidebar-open');
        btn?.setAttribute('aria-expanded', 'false');
      }

      function toggle() {
        body.classList.contains('sidebar-open') ? closeMenu() : openMenu();
      }

      btn?.addEventListener('click', toggle);
      backdrop?.addEventListener('click', closeMenu);
      closeBtn?.addEventListener('click', closeMenu);
      document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeMenu();
      });
    })();
  </script>
</body>

</html>