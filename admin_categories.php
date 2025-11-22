<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/views/topbar.php';
require_once __DIR__ . '/includes/views/sidebar.php';

$me = Auth::requireUserOrRedirect('./auth/login.php');

$profiles = new ProfileRepository();
$meRow    = $profiles->getHeader($me);
$isAdmin  = strtolower(trim($meRow['role'] ?? '')) === 'admin';
if (!$isAdmin) {
  set_flash('err', 'Admins only.');
  header('Location: ./index.php');
  exit;
}

$catsRepo = new CategoriesRepository();

$conn = DB::get();
$cats = $conn->query("
  SELECT category_id, category_name, slug, active
  FROM categories
  ORDER BY active DESC, category_name
")->fetch_all(MYSQLI_ASSOC);

$cssPath = __DIR__ . '/public/css/main.css';
$ver     = file_exists($cssPath) ? filemtime($cssPath) : time();
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Manage Categories · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=<?= $ver ?>">
</head>

<body>
  <div id="flash-stack" class="flash-stack">
    <?php if ($m = get_flash('ok')): ?>
      <div class="flash flash-ok"><?= htmlspecialchars($m) ?></div>
    <?php endif; ?>

    <?php if ($m = get_flash('err')): ?>
      <div class="flash flash-err"><?= htmlspecialchars($m) ?></div>
    <?php endif; ?>
  </div>

  <button class="hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false">☰</button>

  <div class="layout">
    <?php render_sidebar(['isAdmin' => true]); ?>

    <main class="content">
      <div class="content-top">
        <div class="top-actions" style="display:flex; align-items:center; justify-content:space-between; width:100%;">
          <button class="hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false">☰</button>
          <?php render_topbar_userbox($meRow); ?>
        </div>
      </div>

      <div class="settings-wrap">
        <h1 class="page-title">Categories</h1>

        <section class="card pad" style="margin-bottom:1rem">
          <form action="actions/admin/category_add.php" method="post" class="flex-row" style="gap:.5rem; align-items:center;">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <label class="label" style="margin:0">Name</label>
            <input type="text" class="input" name="name" placeholder="e.g. Night" required style="max-width:260px">
            <button type="submit" class="btn-primary">Add</button>
          </form>
        </section>

        <section class="card pad">
          <table class="cats-table" style="width:100%">
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Slug</th>
              <th>Status</th>
              <th style="white-space:nowrap">Toggle</th>
            </tr>
            <?php foreach ($cats as $c): ?>
              <tr>
                <td><?= (int)$c['category_id'] ?></td>
                <td><?= htmlspecialchars($c['category_name']) ?></td>
                <td><?= htmlspecialchars($c['slug']) ?></td>
                <td>
                  <?php if ((int)$c['active']): ?>
                    <span class="badge badge-green">Active</span>
                  <?php else: ?>
                    <span class="badge badge-gray">Hidden</span>
                  <?php endif; ?>
                </td>
                <td style="white-space:nowrap">
                  <form action="actions/admin/category_toggle.php" method="post" style="display:inline">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="category_id" value="<?= (int)$c['category_id'] ?>">
                    <button type="submit" class="btn-ghost">
                      <?= (int)$c['active'] ? 'Hide' : 'Show' ?>
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </table>
        </section>
      </div>
    </main>
  </div>

  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const flashes = document.querySelectorAll('.flash-stack .flash');
      if (!flashes.length) return;

      setTimeout(() => {
        flashes.forEach(flash => {
          A
          flash.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
          flash.style.opacity = '0';
          flash.style.transform = 'translateY(-6px)';

          setTimeout(() => flash.remove(), 500);
        });
      }, 2000);
    });

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