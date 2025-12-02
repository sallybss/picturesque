<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/views/topbar.php';
require_once __DIR__ . '/includes/views/sidebar.php';

$pages = new PagesRepository();
$rules = $pages->getBySlug('rules');

$title   = $rules['title']   ?? 'Rules & Regulations';
$content = $rules['content'] ?? 'No rules have been published yet.';

$isLoggedIn = isset($_SESSION['profile_id']) && (int)$_SESSION['profile_id'] > 0;
$isAdmin    = false;
$meRow      = [];

if ($isLoggedIn) {
  $profiles = new ProfileRepository();
  $meRow    = $profiles->getHeader((int)$_SESSION['profile_id']);
  $isAdmin  = (strtolower($meRow['role'] ?? '') === 'admin');
}

$cssVer = file_exists(__DIR__ . '/public/css/main.css')
  ? filemtime(__DIR__ . '/public/css/main.css')
  : time();
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title) ?> · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=<?= $cssVer ?>">
</head>

<body>
  <div class="layout">
    <?php render_sidebar(['isAdmin' => $isAdmin, 'isGuest' => !$isLoggedIn]); ?>

    <main class="content">
      <div class="content-top">
        <div class="content-spacer"></div>
        <div class="top-actions">
          <button class="hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false">☰</button>

          <?php if ($isLoggedIn): ?>
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
          <?php endif; ?>
        </div>
      </div>

      <section class="about">
        <header class="about-header">
          <h1><?= htmlspecialchars($title) ?></h1>
        </header>

        <article class="about-card">
          <div class="about-body">
            <?= nl2br(htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ?>
          </div>
        </article>

      </section>
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