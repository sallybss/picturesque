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

  <?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>
  <?php if ($m = get_flash('ok')):  ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>

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
          <a href="./profile.php" class="btn-ghost pill">Back to profile</a>
        </div>

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

          <div class="form-row">
            <label class="label">Title</label>
            <input class="input" type="text" name="title" placeholder="Give your photo a title" required>
          </div>

          <div class="form-row">
            <label class="label">Description</label>
            <textarea class="input textarea" name="desc" rows="5" placeholder="Optional description"></textarea>
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
  </script>
</body>

</html>