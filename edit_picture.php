<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/views/topbar.php';
require_once __DIR__ . '/includes/views/sidebar.php';

$catsRepo = new CategoriesRepository();
$cats = $catsRepo->listActive();

$me  = Auth::requireUserOrRedirect('./auth/login.php');

$pid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($pid <= 0) {
  set_flash('err', 'Invalid picture.');
  header('Location: ./profile.php');
  exit;
}

$paths = new Paths();

$pictures = new PictureRepository();
$pic = $pictures->getEditableByOwner($pid, $me);
if (!$pic) {
  set_flash('err', 'Picture not found or not yours.');
  header('Location: ./profile.php');
  exit;
}

$currentImgUrl = img_from_db($pic['picture_url'] ?? null);

$profiles = new ProfileRepository();
$meRow    = $profiles->getHeader($me);
$isAdmin  = strtolower(trim($meRow['role'] ?? '')) === 'admin';

$cssPath = __DIR__ . '/public/css/main.css';
$cssVer  = file_exists($cssPath) ? filemtime($cssPath) : time();
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Edit · Picturesque</title>
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

      <div class="create-page">
        <div class="create-header">
          <h1>Edit post</h1>
          <a href="./profile.php" class="btn-ghost pill">Back to profile</a>
        </div>

        <form method="post" action="./actions/user/update_picture.php" enctype="multipart/form-data" class="create-form">
          <input type="hidden" name="picture_id" value="<?= (int)$pic['picture_id'] ?>">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
          <input type="hidden" name="reset_image" id="resetImage" value="">

          <input id="photo" class="file-input" type="file" name="photo" accept="image/*">

          <div class="dropzone" id="dropzone">
            <div class="dz-empty" id="dzEmpty">
              <svg class="dz-icon" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 16V6m0 0l-4 4m4-4l4 4M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"
                  fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
              <p class="dz-text">Drag &amp; drop a new photo or <button type="button" class="dz-link" id="browseBtn">browse</button></p>
            </div>

            <img id="preview" class="dz-preview" alt="Selected image preview" hidden>
            <button type="button" class="dz-remove" id="removeBtn" aria-label="Remove image" hidden>×</button>
          </div>

          <p class="muted" style="margin-bottom:25px; font-size:0.95rem;">
            Leave image empty to keep the current photo.
          </p>

          <div class="form-row">
            <div class="label-row">
              <label class="label" for="titleInput">Title</label>
              <span class="field-counter" id="titleCount">0 / 50</span>
            </div>
            <input id="titleInput"
              class="input"
              type="text"
              name="title"
              value="<?= htmlspecialchars($pic['picture_title']) ?>"
              placeholder="Give your photo a title"
              required>
          </div>

          <div class="form-row">
            <div class="label-row">
              <label class="label" for="descInput">Description</label>
              <span class="field-counter" id="descCount">0 / 250</span>
            </div>
            <textarea id="descInput" class="input textarea" name="desc" rows="5" placeholder="Optional description" maxlength="250"><?= htmlspecialchars($pic['picture_description']) ?></textarea>
          </div>

          <div class="form-row">
            <label class="label">Category</label>
            <select class="input" name="category_id" required>
              <option value="">Choose a category…</option>
              <?php foreach ($cats as $c): ?>
                <option value="<?= (int)$c['category_id'] ?>"
                  <?= (int)($pic['category_id'] ?? 0) === (int)$c['category_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($c['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="actions">
            <a href="./profile.php" class="btn-ghost wide">Cancel</a>
            <button class="btn-primary wide" type="submit" name="submit">Save changes</button>
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

    const input = document.getElementById('photo');
    const dz = document.getElementById('dropzone');
    const dzEmpty = document.getElementById('dzEmpty');
    const preview = document.getElementById('preview');
    const removeBtn = document.getElementById('removeBtn');
    const browseBtn = document.getElementById('browseBtn');
    const resetInput = document.getElementById('resetImage');
    const originalUrl = "<?= htmlspecialchars($currentImgUrl) ?>";

    (function() {
      if (originalUrl) {
        preview.src = originalUrl;
        preview.hidden = false;
        removeBtn.hidden = false;
        dzEmpty.hidden = true;
        dz.classList.add('has-image');
      }
    })();

    browseBtn.addEventListener('click', () => input.click());
    dz.addEventListener('click', (e) => {
      if (e.target === dz) input.click();
    });

    input.addEventListener('change', function() {
      const file = this.files[0];
      if (file) setFile(file);
    });

    ['dragenter', 'dragover'].forEach(ev => dz.addEventListener(ev, e => {
      e.preventDefault();
      dz.classList.add('is-drag');
    }));
    ['dragleave', 'drop'].forEach(ev => dz.addEventListener(ev, e => {
      e.preventDefault();
      dz.classList.remove('is-drag');
    }));
    dz.addEventListener('drop', (e) => {
      const file = e.dataTransfer.files[0];
      if (file) setFile(file);
    });

    function setFile(file) {
      if (!file.type.startsWith('image/')) {
        alert('Please choose an image file.');
        return;
      }
      if (file.size > 10 * 1024 * 1024) {
        alert('Max size is 10MB.');
        return;
      }

      const reader = new FileReader();
      reader.onload = (e) => {
        preview.src = e.target.result;
        preview.hidden = false;
        removeBtn.hidden = false;
        dzEmpty.hidden = true;
        dz.classList.add('has-image');
      };
      reader.readAsDataURL(file);

      const dt = new DataTransfer();
      dt.items.add(file);
      input.files = dt.files;

      if (resetInput) resetInput.value = "";
    }

    function clearFile() {
      input.value = '';
      preview.src = '';
      preview.hidden = true;
      removeBtn.hidden = true;
      dzEmpty.hidden = false;
      dz.classList.remove('has-image');
      if (resetInput) resetInput.value = "1";
    }

    removeBtn.addEventListener('click', (e) => {
      e.preventDefault();
      clearFile();
    });

    const descField = document.getElementById('descInput');
    const descCounter = document.getElementById('descCount');
    const DESC_MAX = 250;

    if (descField && descCounter) {
      const updateDescCounter = () => {
        let text = descField.value;

        if (text.length > DESC_MAX) {
          text = text.slice(0, DESC_MAX);
          descField.value = text;
        }

        const len = text.length;
        descCounter.textContent = `${len} / ${DESC_MAX}`;

        if (len >= DESC_MAX) {
          descField.classList.add('at-limit');
          descCounter.classList.add('at-limit');
        } else {
          descField.classList.remove('at-limit');
          descCounter.classList.remove('at-limit');
        }
      };

      descField.addEventListener('input', updateDescCounter);
      updateDescCounter();
    }

    const titleInput = document.getElementById('titleInput');
    const titleCount = document.getElementById('titleCount');
    const TITLE_MAX = 50;

    if (titleInput && titleCount) {
      const updateTitleCounter = () => {
        let text = titleInput.value;

        if (text.length > TITLE_MAX) {
          text = text.slice(0, TITLE_MAX);
          titleInput.value = text;
          titleInput.classList.add('at-limit');
          titleCount.classList.add('at-limit');
        } else {
          titleInput.classList.remove('at-limit');
          titleCount.classList.remove('at-limit');
        }

        titleCount.textContent = `${text.length} / ${TITLE_MAX}`;
      };

      titleInput.addEventListener('input', updateTitleCounter);
      updateTitleCounter();
    }

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
</body>

</html>
