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
        <div class="spacer"></div>
        <div class="top-actions">
          <button class="hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false">☰</button>
          <?php if ($isLoggedIn): ?>
            <?php render_topbar_userbox($meRow); ?>
          <?php endif; ?>
        </div>
      </div>

      <section class="pad">
        <h1 class="page-title"><?= htmlspecialchars($title) ?></h1>
        <div class="prose">
          <?= nl2br(htmlspecialchars($content)) ?>
        </div>
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
  </script>
</body>
</html>
