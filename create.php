<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/views/topbar.php';
require_once __DIR__ . '/includes/views/sidebar.php';

$catsRepo = new CategoriesRepository();
$cats     = $catsRepo->listActive();

$me = Auth::requireUserOrRedirect('./auth/login.php');

$paths     = new Paths();
$profiles  = new ProfileRepository();
$meRow     = $profiles->getHeader($me);
$isAdmin   = strtolower(trim($meRow['role'] ?? '')) === 'admin';

$cssPath = __DIR__ . '/public/css/main.css';
$ver     = file_exists($cssPath) ? filemtime($cssPath) : time();

$limitPer5Min = 5;
$pictureRepo  = new PictureRepository();
$recentCount  = $pictureRepo->countRecentForUser((int)$me);
$postsLeft    = max(0, $limitPer5Min - $recentCount);

$limitUntil = null;
if (!empty($_SESSION['post_limit_until'])) {
    $limitUntil = (int)$_SESSION['post_limit_until'];

    if ($limitUntil <= time()) {
        unset($_SESSION['post_limit_until']);
        $limitUntil = null;
    }
}

$showRateModal = $limitUntil !== null && $postsLeft <= 0;
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Create · Picturesque</title>
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

  <div class="layout">
    <?php render_sidebar(['isAdmin' => $isAdmin]); ?>

    <main class="content">
      <div class="content-top">
        <div class="top-actions" style="display:flex; align-items:center; justify-content:space-between; width:100%;">
          <button class="hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false">☰</button>
          <?php render_topbar_userbox($meRow); ?>
        </div>
      </div>

      <div class="create-page">
        <div class="create-header">
          <h1>New post</h1>
          <a href="./index.php" class="btn-ghost pill">Back to home</a>
        </div>

        <p class="rate-limit-note">
          You can upload up to <strong><?= $limitPer5Min ?></strong> pictures every
          <strong>5 minutes</strong>.
          <?php if ($postsLeft > 0): ?>
            <span>(You have <strong><?= $postsLeft ?></strong> left in this window.)</span>
          <?php else: ?>
            <span>(Limit reached for this 5-minute window.)</span>
          <?php endif; ?>

          <?php if ($limitUntil): ?>
            <span
              data-rate-limit="wrapper"
              data-until="<?= $limitUntil ?>"
              style="margin-left: 6px;"
            >
              Next post allowed in
              <strong><span id="rateLimitTimerText">05:00</span></strong>.
            </span>
          <?php endif; ?>
        </p>

        <form method="post" action="./actions/user/post_picture.php" enctype="multipart/form-data" class="create-form">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
          <input id="photo" class="file-input" type="file" name="photo" accept="image/*" required>

          <div class="dropzone" id="dropzone">
            <div class="dz-empty" id="dzEmpty">
              <svg class="dz-icon" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 16V6m0 0l-4 4m4-4l4 4M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"
                  fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
              <p class="dz-text">Drag &amp; drop a photo or <button type="button" class="dz-link" id="browseBtn">browse</button></p>
            </div>

            <img id="preview" class="dz-preview" alt="Selected image preview" hidden>
            <button type="button" class="dz-remove" id="removeBtn" aria-label="Remove image" hidden>×</button>
          </div>

          <p class="muted" style="margin-bottom:25px; font-size: 0.95rem;">
            Images are automatically resized to fit the feed layout.
            Full images are visible when opening the picture.
          </p>

          <div class="form-row">
            <div class="label-row">
              <label class="label" for="titleInput">Title</label>
              <span class="field-counter" id="titleCount">0 / 50</span>
            </div>
            <input id="titleInput" class="input" type="text" name="title" placeholder="Give your photo a title" maxlength="50" required>
          </div>

          <div class="form-row">
            <div class="label-row">
              <label class="label" for="descInput">Description</label>
              <span class="field-counter" id="descCount">0 / 250</span>
            </div>
            <textarea id="descInput" class="input textarea" name="desc" rows="5" placeholder="Optional description" maxlength="250"></textarea>
          </div>

          <div class="form-row">
            <label class="label">Category</label>
            <select class="input" name="category_id" required>
              <option value="">Choose a category…</option>
              <?php foreach ($cats as $c): ?>
                <option value="<?= (int)$c['category_id'] ?>">
                  <?= htmlspecialchars($c['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="actions">
            <a href="./profile.php" class="btn-ghost wide">Cancel</a>
            <button class="btn-primary wide" type="submit" name="submit">Publish</button>
          </div>
        </form>
      </div>
    </main>
  </div>

  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
  <div
    class="rate-modal-backdrop"
    id="rateLimitModal"
    <?= $showRateModal ? '' : 'hidden' ?>
  >
    <div class="rate-modal">
      <h2>Posting limit reached</h2>
      <p>You can upload up to <strong><?= $limitPer5Min ?></strong> pictures every <strong>5 minutes</strong>.</p>
      <?php if ($limitUntil): ?>
        <p>
          Next post allowed in
          <strong><span id="rateLimitTimerTextModal">05:00</span></strong>.
        </p>
      <?php endif; ?>
      <small>This helps prevent spam and keeps Picturesque fast for everyone.</small>
      <button type="button" id="rateLimitModalClose" class="btn-primary" style="margin-top:12px;">OK</button>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const flashes = document.querySelectorAll('.flash-stack .flash');
      if (flashes.length) {
        setTimeout(() => {
          flashes.forEach(flash => {
            flash.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            flash.style.opacity = '0';
            flash.style.transform = 'translateY(-6px)';
            setTimeout(() => flash.remove(), 500);
          });
        }, 2000);
      }
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
    }

    function clearFile() {
      input.value = '';
      preview.src = '';
      preview.hidden = true;
      removeBtn.hidden = true;
      dzEmpty.hidden = false;
      dz.classList.remove('has-image');
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
      const wrapper = document.querySelector('[data-rate-limit="wrapper"]');
      if (!wrapper) return;

      const until = parseInt(wrapper.dataset.until || '0', 10);
      if (!until) return;

      const textMain  = document.getElementById('rateLimitTimerText');
      const textModal = document.getElementById('rateLimitTimerTextModal');

      function format(diff) {
        const m = Math.floor(diff / 60);
        const s = diff % 60;
        return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
      }

      function tick() {
        const now = Math.floor(Date.now() / 1000);
        let diff = until - now;

        if (diff <= 0) {
          if (textMain)  textMain.textContent  = '00:00';
          if (textModal) textModal.textContent = '00:00';
          return;
        }

        const val = format(diff);
        if (textMain)  textMain.textContent  = val;
        if (textModal) textModal.textContent = val;

        setTimeout(tick, 1000);
      }

      tick();
    })();

    (function() {
      const modal = document.getElementById('rateLimitModal');
      const close = document.getElementById('rateLimitModalClose');
      if (!modal || !close) return;

      close.addEventListener('click', () => {
        modal.hidden = true;
      });
    })();
  </script>
</body>

</html>