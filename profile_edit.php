<?php
require_once __DIR__ . '/includes/init.php';

$me = Auth::requireUserOrRedirect('./auth/login.php');

$paths = new Paths();

$profiles = new ProfileRepository();
$meRow = $profiles->getById($me);
if (!$meRow) { header('Location: ./index.php'); exit; }

$isAdmin   = isset($meRow['role']) && strtolower($meRow['role']) === 'admin';
$avatarSrc = !empty($meRow['avatar_photo']) ? $paths->uploads . htmlspecialchars($meRow['avatar_photo']) : 'https://placehold.co/96x96?text=%20';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Edit Profile · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=1">
</head>
<body>
  <?php if ($m = get_flash('ok')): ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
  <?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

  <div class="layout">
    <?php render_sidebar(['isAdmin' => $isAdmin]); ?>

    <main class="content">
      <div class="form-card">
        <h2 class="card-title">Edit Profile</h2>

        <div class="edit-avatar-row">
          <div class="edit-avatar"><img src="<?= $avatarSrc ?>" alt="Avatar" class="avatar-preview"></div>
          <span class="avatar-note">Upload a new avatar (JPG/PNG/WEBP)</span>
        </div>

        <form action="./actions/post_profile_update.php" method="post" enctype="multipart/form-data">
          <div class="form-row">
            <label class="label-sm" for="display_name">Display name</label>
            <input class="input-sm" id="display_name" name="display_name" required value="<?= htmlspecialchars($meRow['display_name'] ?? '') ?>">
          </div>

          <div class="form-row">
            <label class="label-sm" for="avatarInput">Avatar image</label>
            <input id="avatarInput" class="file-input" type="file" name="avatar" accept="image/*">
            <div class="dropzone" id="avatarDropzone">
              <div class="dz-empty" id="avatarDzEmpty">
                <svg class="dz-icon" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M12 16V6m0 0l-4 4m4-4l4 4M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <p class="dz-text">Drag &amp; drop your avatar here or <button type="button" class="dz-link" id="avatarBrowseBtn">browse</button></p>
              </div>
              <img id="avatarPreview" class="dz-preview" alt="Avatar preview" hidden>
              <button type="button" class="dz-remove" id="avatarRemoveBtn" aria-label="Remove image" hidden>×</button>
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

  <script>
    const avatarInput = document.getElementById('avatarInput');
    const avatarDz = document.getElementById('avatarDropzone');
    const avatarDzEmpty = document.getElementById('avatarDzEmpty');
    const avatarPreview = document.getElementById('avatarPreview');
    const avatarRemove = document.getElementById('avatarRemoveBtn');
    const avatarBrowse = document.getElementById('avatarBrowseBtn');
    const originalUrl = "<?= $avatarSrc ?>";

    (function () {
      if (originalUrl) {
        avatarPreview.src = originalUrl;
        avatarPreview.hidden = false;
        avatarRemove.hidden = false;
        avatarDzEmpty.hidden = true;
        avatarDz.classList.add('has-image');
      }
    })();

    if (avatarBrowse) avatarBrowse.addEventListener('click', () => avatarInput.click());
    avatarDz.addEventListener('click', (e) => { if (e.target === avatarDz) avatarInput.click(); });
    avatarPreview.addEventListener('click', () => avatarInput.click());

    avatarInput.addEventListener('change', function () {
      const f = this.files[0];
      if (f) setFile(f);
    });

    ['dragenter','dragover'].forEach(ev => avatarDz.addEventListener(ev, e => { e.preventDefault(); avatarDz.classList.add('is-drag'); }));
    ['dragleave','drop'].forEach(ev => avatarDz.addEventListener(ev, e => { e.preventDefault(); avatarDz.classList.remove('is-drag'); }));
    avatarDz.addEventListener('drop', (e) => {
      const f = e.dataTransfer.files[0];
      if (f) setFile(f);
    });

    function setFile(file) {
      if (!file.type.startsWith('image/')) { alert('Please choose an image file.'); return clearFile(false); }
      if (file.size > 10 * 1024 * 1024) { alert('Max size is 10MB.'); return clearFile(false); }

      const r = new FileReader();
      r.onload = (e) => {
        avatarPreview.src = e.target.result;
        avatarPreview.hidden = false;
        avatarRemove.hidden = false;
        avatarDzEmpty.hidden = true;
        avatarDz.classList.add('has-image');
      };
      r.readAsDataURL(file);

      const dt = new DataTransfer();
      dt.items.add(file);
      avatarInput.files = dt.files;
    }

    function clearFile(keepOriginal) {
      avatarInput.value = '';
      if (keepOriginal && originalUrl) {
        avatarPreview.src = originalUrl;
        avatarPreview.hidden = false;
        avatarDzEmpty.hidden = true;
        avatarRemove.hidden = false;
        avatarDz.classList.add('has-image');
      } else {
        avatarPreview.src = '';
        avatarPreview.hidden = true;
        avatarDzEmpty.hidden = false;
        avatarRemove.hidden = true;
        avatarDz.classList.remove('has-image');
      }
    }

    avatarRemove.addEventListener('click', (e) => { e.preventDefault(); clearFile(false); });
  </script>
</body>
</html>
