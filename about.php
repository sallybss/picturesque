<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/views/topbar.php';
require_once __DIR__ . '/includes/views/sidebar.php';

$me = Auth::requireUserOrRedirect('./auth/login.php');

$paths    = new Paths();
$profiles = new ProfileRepository();
$meRow    = $profiles->getHeader($me);
$isAdmin  = (strtolower($meRow['role'] ?? 'user') === 'admin');

$pages = new PagesRepository();
$page  = $pages->getAbout() ?: [
    'title'      => 'About Picturesque',
    'content'    => 'Welcome to Picturesque!',
    'image_path' => null,
];

$title    = $page['title']      ?? 'About Picturesque';
$content  = $page['content']    ?? '';
$imageRel = $page['image_path'] ?? null;
$imgUrl   = $imageRel ? $paths->uploads . $imageRel : null;

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
  <?php if ($m = get_flash('ok')):  ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
  <?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

  <div class="layout">
    <?php render_sidebar(['isAdmin' => $isAdmin]); ?>

    <main class="content">
      <div class="content-top">
        <div class="top-actions" style="display:flex; align-items:center; justify-content:space-between; width:100%;">
          <button class="hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false">☰</button>
          <?php render_topbar_userbox($meRow); ?>
        </div>
      </div>

      <section class="about">
        <h1><?= htmlspecialchars($title) ?></h1>
        <p class="lead"><?= nl2br(htmlspecialchars($content)) ?></p>

        <?php if ($imgUrl): ?>
          <img src="<?= htmlspecialchars($imgUrl) ?>" alt="About image" class="about-image">
        <?php endif; ?>
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
