<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/views/topbar.php';
require_once __DIR__ . '/includes/views/sidebar.php';

$me = Auth::requireUserOrRedirect('./auth/login.php');

$profiles = new ProfileRepository();
$meRow = $profiles->getById($me);
if (!$meRow) {
  header('Location: ./index.php');
  exit;
}

$isAdmin = strtolower($meRow['role'] ?? '') === 'admin';

$avatarSrc = !empty($meRow['avatar_photo'])
  ? img_from_db($meRow['avatar_photo'])
  : 'https://placehold.co/96x96?text=%20';

$cssPath = __DIR__ . '/public/css/main.css';
$cssVer  = file_exists($cssPath) ? filemtime($cssPath) : time();
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Edit Profile · Picturesque</title>
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
    <?php render_sidebar(['isAdmin' => $isAdmin]); ?>

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


      <div class="form-card">
        <h2 class="card-title">Edit Profile</h2>

        <div class="edit-avatar-row">
          <div class="edit-avatar">
            <img src="<?= htmlspecialchars($avatarSrc) ?>" alt="Avatar" class="avatar-preview">
          </div>
          <span class="avatar-note">Upload a new avatar (JPG/PNG/WEBP)</span>
        </div>

        <form action="./actions/user/post_profile_update.php" method="post" enctype="multipart/form-data">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

          <div class="form-row">
            <label class="label-sm" for="display_name">Display name</label>
            <input class="input-sm" id="display_name" name="display_name" required
              value="<?= htmlspecialchars($meRow['display_name'] ?? '') ?>">
          </div>

          <div class="form-row">
            <label class="label-sm" for="avatarInput">Avatar image</label>
            <input id="avatarInput" class="file-input" type="file" name="avatar" accept="image/*">

            <div class="dropzone" id="avatarDropzone">
              <div class="dz-empty" id="avatarDzEmpty" hidden>
                <svg class="dz-icon" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M12 16V6m0 0l-4 4m4-4l4 4M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"
                    fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <p class="dz-text">
                  Drag &amp; drop your avatar here or
                  <button type="button" class="dz-link" id="avatarBrowseBtn">browse</button>
                </p>
              </div>

              <img id="avatarPreview" class="dz-preview" alt="Avatar preview"
                src="<?= htmlspecialchars($avatarSrc) ?>">

              <button type="button" class="dz-remove" id="avatarRemoveBtn" aria-label="Remove image">×</button>
            </div>

            <p class="avatar-note" style="margin-top:8px">Upload a new avatar (JPG/PNG/WEBP). Max 10MB.</p>
          </div>

          <div class="profile-actions-row">
            <button class="btn-primary" type="submit" name="submit">Save changes</button>
            <a class="btn-ghost" href="./profile.php">Cancel</a>
          </div>
        </form>
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
      const avatarInput = document.getElementById('avatarInput');
      const avatarDz = document.getElementById('avatarDropzone');
      const avatarDzEmpty = document.getElementById('avatarDzEmpty');
      const avatarPreview = document.getElementById('avatarPreview');
      const avatarRemove = document.getElementById('avatarRemoveBtn');
      const avatarBrowse = document.getElementById('avatarBrowseBtn');
      const originalUrl = "<?= htmlspecialchars($avatarSrc) ?>";

      avatarPreview.src = originalUrl;
      avatarDzEmpty.hidden = true;

      function setFile(file) {
        if (!file.type.startsWith('image/')) {
          alert('Please choose an image file.');
          return;
        }
        if (file.size > 10 * 1024 * 1024) {
          alert('Max size is 10MB.');
          return;
        }

        const r = new FileReader();
        r.onload = e => {
          avatarPreview.src = e.target.result;
        };
        r.readAsDataURL(file);

        const dt = new DataTransfer();
        dt.items.add(file);
        avatarInput.files = dt.files;
      }

      function clearFile() {
        avatarInput.value = '';
        avatarPreview.src = originalUrl;
      }

      avatarBrowse && avatarBrowse.addEventListener('click', () => avatarInput.click());
      avatarDz.addEventListener('click', e => {
        if (e.target === avatarDz) avatarInput.click();
      });
      avatarPreview.addEventListener('click', () => avatarInput.click());
      avatarRemove.addEventListener('click', e => {
        e.preventDefault();
        clearFile();
      });

      ['dragenter', 'dragover'].forEach(ev => avatarDz.addEventListener(ev, e => {
        e.preventDefault();
        avatarDz.classList.add('is-drag');
      }));
      ['dragleave', 'drop'].forEach(ev => avatarDz.addEventListener(ev, e => {
        e.preventDefault();
        avatarDz.classList.remove('is-drag');
      }));
      avatarDz.addEventListener('drop', e => {
        const f = e.dataTransfer.files[0];
        if (f) setFile(f);
      });

      avatarInput.addEventListener('change', function() {
        const f = this.files[0];
        if (f) setFile(f);
      });
    })();

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