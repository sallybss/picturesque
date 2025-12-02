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
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#ffffffff" viewBox="0 0 256 256">
                <path d="M64,105V40a8,8,0,0,0-16,0v65a32,32,0,0,0,0,62v49a8,8,0,0,0,16,0V167a32,32,0,0,0,0-62Zm-8,47a16,16,0,1,1,16-16A16,16,0,0,1,56,152Zm80-95V40a8,8,0,0,0-16,0V57a32,32,0,0,0,0,62v97a8,8,0,0,0,16,0V119a32,32,0,0,0,0-62Zm-8,47a16,16,0,1,1,16-16A16,16,0,0,1,128,104Zm104,64a32.06,32.06,0,0,0-24-31V40a8,8,0,0,0-16,0v97a32,32,0,0,0,0,62v17a8,8,0,0,0,16,0V199A32.06,32.06,0,0,0,232,168Zm-32,16a16,16,0,1,1,16-16A16,16,0,0,1,200,184Z"></path>
              </svg>
            </button>

            <div class="user-menu" id="userMenu">
              <div class="user-menu-section">
                <span class="user-menu-title">Theme</span>
                <button type="button" class="user-menu-item" data-theme="light">Light mode</button>
                <button type="button" class="user-menu-item" data-theme="dark">Dark mode</button>
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

      <div class="content-top admin-topbar">
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
            <img class="ai-avatar" src="<?= htmlspecialchars($a) ?>" alt="">
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
                <form class="inline" method="post" action="./actions/admin/toggle_status.php">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                  <input type="hidden" name="profile_id" value="<?= (int)$u['profile_id'] ?>">
                  <button class="btn-ghost pill" type="submit">
                    <?= ($u['status'] === 'blocked') ? 'Unblock' : 'Block' ?>
                  </button>
                </form>

                <form class="inline" method="post" action="./actions/admin/delete_user.php"
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

    (function() {
      const body = document.body;
      const THEME_KEY = 'pq_theme';
      const FONT_KEY = 'pq_font';

      function applyTheme(theme) {
        body.classList.remove('theme-light', 'theme-dark');
        body.classList.add('theme-' + theme);
      }

      function applyFont(size) {
        body.classList.remove('font-small', 'font-medium', 'font-large');
        body.classList.add('font-' + size);
      }

      const savedTheme = localStorage.getItem(THEME_KEY) || 'light';
      const savedFont = localStorage.getItem(FONT_KEY) || 'medium';

      applyTheme(savedTheme);
      applyFont(savedFont);
    })();

    (function() {
      const body = document.body;
      const menuToggle = document.getElementById('userMenuToggle');
      const menu = document.getElementById('userMenu');

      if (!menuToggle || !menu) return;

      const THEME_KEY = 'pq_theme';
      const FONT_KEY = 'pq_font';

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
      const savedFont = localStorage.getItem(FONT_KEY) || 'medium';
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
        if (menu.classList.contains('open')) {
          closeMenu();
        } else {
          openMenu();
        }
      });

      document.addEventListener('click', (e) => {
        if (!menu.contains(e.target) && e.target !== menuToggle) {
          closeMenu();
        }
      });

      menu.querySelectorAll('[data-theme]').forEach(btn => {
        btn.addEventListener('click', () => {
          applyTheme(btn.getAttribute('data-theme'));
        });
      });

      menu.querySelectorAll('[data-font]').forEach(btn => {
        btn.addEventListener('click', () => {
          applyFont(btn.getAttribute('data-font'));
        });
      });
    })();
  </script>
</body>

</html>
