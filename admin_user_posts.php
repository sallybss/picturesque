<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/views/topbar.php';
require_once __DIR__ . '/includes/views/sidebar.php';

$me = Auth::requireAdminOrRedirect('./index.php');

$userId = (int)($_GET['id'] ?? 0);
if ($userId <= 0) {
    set_flash('err', 'Missing user id.');
    header('Location: ./admin.php');
    exit;
}

$paths    = new Paths();
$profiles = new ProfileRepository();

$meRow   = $profiles->getHeader($me);
$isAdmin = (strtolower($meRow['role'] ?? 'user') === 'admin');
if (!$isAdmin) {
    set_flash('err', 'Admins only.');
    header('Location: ./index.php');
    exit;
}

$user = $profiles->getById($userId);
if (!$user) {
    set_flash('err', 'User not found.');
    header('Location: ./admin.php');
    exit;
}

$picturesRepo = new PictureRepository();
$posts        = $picturesRepo->listByProfile($userId);

$cssPath = __DIR__ . '/public/css/main.css';
$cssVer  = file_exists($cssPath) ? filemtime($cssPath) : time();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin · Posts of <?= htmlspecialchars($user['display_name']) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=<?= $cssVer ?>">
</head>
<body>
  <?php if ($m = get_flash('ok')):  ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
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

      <div class="content-top" style="display:flex; align-items:center; justify-content:space-between;">
        <h1 class="page-title">
          Posts · <?= htmlspecialchars($user['display_name']) ?>
          (<?= count($posts) ?>)
        </h1>
        <a href="admin.php" class="btn-ghost pill">← Back to overview</a>
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

                <form method="post"
                      action="./actions/admin/delete_picture.php"
                      onsubmit="return confirm('Delete this picture?');"
                      style="display:inline">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                  <input type="hidden" name="picture_id" value="<?= (int)$p['picture_id'] ?>">
                  <button class="btn-danger pill" type="submit">Delete</button>
                </form>
              </div>
            </div>
          </article>
        <?php endforeach; ?>

        <?php if (!$posts): ?>
          <p class="muted">No posts yet.</p>
        <?php endif; ?>
      </section>
    </main>
  </div>

  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

  <script>
    (function() {
      const body      = document.body;
      const btn       = document.getElementById('hamburger');
      const backdrop  = document.getElementById('sidebarBackdrop');
      const closeBtn  = document.getElementById('closeSidebar');

      function openMenu() {
        body.classList.add('sidebar-open');
        if (btn) btn.setAttribute('aria-expanded', 'true');
      }

      function closeMenu() {
        body.classList.remove('sidebar-open');
        if (btn) btn.setAttribute('aria-expanded', 'false');
      }

      function toggle() {
        body.classList.contains('sidebar-open') ? closeMenu() : openMenu();
      }

      btn      && btn.addEventListener('click', toggle);
      backdrop && backdrop.addEventListener('click', closeMenu);
      closeBtn && closeBtn.addEventListener('click', closeMenu);

      document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeMenu();
      });
    })();
  </script>

  <?php if (!empty($_GET['afterDelete'])): ?>
  <script>
    (function () {
      if (!window.history || !history.pushState) return;

      history.pushState({afterDelete: true}, "", window.location.href);

      window.addEventListener("popstate", function () {
        window.location.href = "admin.php";
      });
    })();
  </script>
  <?php endif; ?>
</body>
</html>
