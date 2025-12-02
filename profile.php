<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/views/topbar.php';
require_once __DIR__ . '/includes/views/sidebar.php';

$me = Auth::requireUserOrRedirect('./auth/login.php');

$profileId = isset($_GET['id']) ? (int)$_GET['id'] : $me;
if ($profileId <= 0) {
  header('Location: ./auth/login.php');
  exit;
}

$paths    = new Paths();
$profiles = new ProfileRepository();

$meRow     = $profiles->getHeader($me);
$iAmAdmin  = (strtolower($meRow['role'] ?? 'user') === 'admin');

$viewRow = $profiles->getById($profileId);
if (!$viewRow) {
  header('Location: ./auth/login.php');
  exit;
}

$picturesRepo  = new PictureRepository();
$picturesCount = $picturesRepo->countByProfile($profileId);
$likesCount    = $picturesRepo->likesCountForProfilePictures($profileId);
$commentsCount = $picturesRepo->commentsCountForProfilePictures($profileId);
$myPics        = $picturesRepo->listByProfile($profileId);

$avatarSrc = !empty($viewRow['avatar_photo'])
  ? img_from_db($viewRow['avatar_photo'])
  : 'https://placehold.co/96x96?text=%20';

$coverSrc = !empty($viewRow['cover_photo'])
  ? img_from_db($viewRow['cover_photo'])
  : asset('images/default-cover.jpg');

$cssPath = __DIR__ . '/public/css/main.css';
$cssVer  = file_exists($cssPath) ? filemtime($cssPath) : time();
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($viewRow['display_name']) ?> · Profile</title>
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
    <?php render_sidebar(['isAdmin' => $iAmAdmin]); ?>

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

      <div class="profile-cover">
        <img src="<?= $coverSrc ?>" alt="Cover Photo" class="cover-image">

        <?php if ($me === $profileId): ?>
          <div class="cover-actions">
            <form method="post" action="./actions/user/update_cover.php" enctype="multipart/form-data">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
              <input id="coverInput" type="file" name="cover" accept="image/*" hidden onchange="this.form.submit()">
              <label for="coverInput" class="btn btn-ghost small">Change Cover</label>
            </form>

            <form method="post" action="./actions/user/update_cover.php">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
              <input type="hidden" name="action" value="reset">
              <button type="submit" class="btn btn-ghost small">Reset</button>
            </form>
          </div>
        <?php endif; ?>
      </div>

      <section class="profile-hero">
        <img class="profile-avatar" src="<?= $avatarSrc ?>" alt="">
        <div class="profile-info">
          <div class="profile-name"><?= htmlspecialchars($viewRow['display_name']) ?></div>
          <?php if (!empty($viewRow['email'])): ?>
            <div class="profile-email"><?= htmlspecialchars($viewRow['email']) ?></div>
          <?php endif; ?>
          <div class="profile-stats">
            <span><b><?= (int)$picturesCount ?></b> Posts</span>
            <span><b><?= (int)$likesCount ?></b> Likes</span>
            <span><b><?= (int)$commentsCount ?></b> Comments</span>
          </div>
          <?php if ($me === $profileId): ?>
            <div class="profile-actions" style="display:flex; gap:8px; flex-wrap:wrap">
              <a class="btn btn-primary" href="./profile_edit.php">Edit Profile</a>
              <a class="btn btn-ghost" href="./profile_settings.php">Profile Settings</a>
            </div>
          <?php endif; ?>
        </div>
      </section>

      <h2 class="section-title"><?= ($me === $profileId ? 'My Photos' : 'Photos') ?></h2>
      <section class="feed">
        <?php foreach ($myPics as $p): ?>
          <?php $cardImg = img_from_db($p['picture_url'] ?? $p['pic_url'] ?? ''); ?>
          <article class="card">
            <div class="card-cover">
              <img src="<?= $cardImg ?>" alt="">
            </div>

            <div class="card-body">
              <div class="card-title"><?= htmlspecialchars($p['picture_title']) ?></div>
              <?php if (!empty($p['picture_description'])): ?>
                <div class="card-desc"><?= htmlspecialchars($p['picture_description']) ?></div>
              <?php endif; ?>
              <div class="meta">
                <span class="counts">
                  <span class="muted">❤ <?= (int)$p['like_count'] ?></span>
                  <a class="muted" href="picture.php?id=<?= (int)$p['picture_id'] ?>">💬 <?= (int)$p['comment_count'] ?></a>
                </span>
                <span class="spacer"></span>
                <?php if ($me === $profileId): ?>
                  <a class="btn btn-ghost" href="./edit_picture.php?id=<?= (int)$p['picture_id'] ?>">Edit</a>
                  <form method="post" action="./actions/user/delete_picture.php" onsubmit="return confirm('Delete this photo? This cannot be undone.');" style="display:inline">
                    <input type="hidden" name="picture_id" value="<?= (int)$p['picture_id'] ?>">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <button type="submit" class="btn btn-danger">Delete</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>

        <?php if (!$myPics): ?>
          <p class="muted"><?= ($me === $profileId ? "You haven’t posted yet. Click " : "No photos yet.") ?><b><?= ($me === $profileId ? "Create" : "") ?></b></p>
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