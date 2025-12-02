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
  <div id="flash-stack" class="flash-stack">
    <?php if ($m = get_flash('ok')): ?>
      <div class="flash flash-ok"><?= htmlspecialchars($m) ?></div>
    <?php endif; ?>

    <?php if ($m = get_flash('err')): ?>
      <div class="flash flash-err"><?= htmlspecialchars($m) ?></div>
    <?php endif; ?>
  </div>

  <div class="layout">
    <?php render_sidebar(['isAdmin' => true]); ?>

    <main class="content">
      <div class="content-top">
        <div class="content-spacer"></div>
        <div class="top-actions">
          <button class="hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false">☰</button>

          <div class="user-settings">
            <?php render_topbar_userbox($meRow); ?>

            <button class="user-menu-toggle" id="userMenuToggle" aria-label="Display settings" aria-expanded="false">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#fff" viewBox="0 0 256 256">
                <path d="M64,105V40a8,8,0,0,0-16,0v65a32,32,0,0,0,0,62v49a8,8,0,0,0,16,0V167a32,32,0,0,0,0-62Zm-8,47a16,16,0,1,1,16-16A16,16,0,0,1,56,152Zm80-95V40a8,8,0,0,0-16,0V57a32,32,0,0,0,0,62v97a8,8,0,0,0,16,0V119a32,32,0,0,0,0-62Zm-8,47a16,16,0,1,1,16-16A16,16,0,0,1,128,104Zm104,64a32.06,32.06,0,0,0-24-31V40a8,8,0,0,0-16,0v97a32,32,0,0,0,0,62v17a8,8,0,0,0,16,0V199A32.06,32.06,0,0,0,232,168Zm-32,16a16,16,0,1,1,16-16A16,16,0,0,1,200,184Z"></path>
              </svg>
            </button>

            <div class="user-menu" id="userMenu">
              <div class="user-menu-section">
                <span class="user-menu-title">Theme</span>
                <button type="button" class="user-menu-item" data-theme="light">Light</button>
                <button type="button" class="user-menu-item" data-theme="dark">Dark</button>
              </div>

              <div class="user-menu-section">
                <span class="user-menu-title">Font size</span>
                <button type="button" class="user-menu-item" data-font="small">Small</button>
                <button type="button" class="user-menu-item" data-font="medium">Medium</button>
                <button type="button" class="user-menu-item" data-font="large">Large</button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="content-top" style="align-items:center; justify-content:space-between; gap:12px; margin-bottom:12px;">
        <h1 class="page-title" style="margin:0;">
          Posts · <?= htmlspecialchars($user['display_name']) ?>
          (<?= count($posts) ?>)
        </h1>
        <a href="admin.php" class="btn-ghost pill" style="width:auto; white-space:nowrap;">
          ← Back to overview
        </a>
      </div>


      <section class="feed">
        <?php foreach ($posts as $p): ?>
          <?php $cover = img_from_db($p['picture_url']); ?>
          <article class="card">
            <a href="picture.php?id=<?= (int)$p['picture_id'] ?>" class="card-cover">
              <img src="<?= htmlspecialchars($cover) ?>" alt="">
            </a>

            <div class="card-body">
              <div class="card-title">
                <a href="picture.php?id=<?= (int)$p['picture_id'] ?>" class="card-title-link">
                  <?= htmlspecialchars($p['picture_title']) ?>
                </a>
              </div>

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
    document.addEventListener('DOMContentLoaded', () => {
      const flashes = document.querySelectorAll('.flash-stack .flash');
      if (!flashes.length) return;

      setTimeout(() => {
        flashes.forEach(flash => {
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
        if (btn) btn.setAttribute('aria-expanded', 'true');
      }

      function closeMenu() {
        body.classList.remove('sidebar-open');
        if (btn) btn.setAttribute('aria-expanded', 'false');
      }

      function toggle() {
        body.classList.contains('sidebar-open') ? closeMenu() : openMenu();
      }

      btn && btn.addEventListener('click', toggle);
      backdrop && backdrop.addEventListener('click', closeMenu);
      closeBtn && closeBtn.addEventListener('click', closeMenu);

      document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeMenu();
      });
    })();

    (function() {
      const body = document.body;
      const menuToggle = document.getElementById('userMenuToggle');
      const menu = document.getElementById('userMenu');

      if (!menuToggle || !menu) return;

      const THEME_KEY = 'pq_theme';
      const FONT_KEY  = 'pq_font';

      function applyTheme(theme) {
        body.classList.remove('theme-light', 'theme-dark');
        body.classList.add('theme-' + theme);
        localStorage.setItem(THEME_KEY, theme);
      }

      function applyFont(size) {
        body.classList.remove('font-small', 'font-medium', 'font-large');
        body.classList.add('font-' + size);
        localStorage.setItem(FONT_KEY, size);
      }

      const savedTheme = localStorage.getItem(THEME_KEY) || 'light';
      const savedFont  = localStorage.getItem(FONT_KEY) || 'medium';
      applyTheme(savedTheme);
      applyFont(savedFont);

      function closeMenu() {
        menu.classList.remove('open');
        menuToggle.setAttribute('aria-expanded', 'false');
      }

      function openMenu() {
        menu.classList.add('open');
        menuToggle.setAttribute('aria-expanded', 'true');
      }

      menuToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        menu.classList.contains('open') ? closeMenu() : openMenu();
      });

      document.addEventListener('click', (e) => {
        if (!menu.contains(e.target) && e.target !== menuToggle) {
          closeMenu();
        }
      });

      menu.querySelectorAll('[data-theme]').forEach(btn => {
        btn.addEventListener('click', () => applyTheme(btn.dataset.theme));
      });

      menu.querySelectorAll('[data-font]').forEach(btn => {
        btn.addEventListener('click', () => applyFont(btn.dataset.font));
      });
    })();
  </script>

  <?php if (!empty($_GET['afterDelete'])): ?>
    <script>
      (function() {
        if (!window.history || !history.pushState) return;

        history.pushState({ afterDelete: true }, "", window.location.href);

        window.addEventListener("popstate", function() {
          window.location.href = "admin.php";
        });
      })();
    </script>
  <?php endif; ?>
</body>

</html>
