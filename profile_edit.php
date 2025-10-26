<?php
require_once __DIR__ . '/includes/init.php';

$me = Auth::requireUserOrRedirect('./auth/login.php');

$profiles = new ProfileRepository();
$meRow = $profiles->getById($me);
if (!$meRow) { header('Location: ./index.php'); exit; }

$isAdmin = strtolower($meRow['role'] ?? '') === 'admin';

$avatarSrc = !empty($meRow['avatar_photo'])
  ? img_from_db($meRow['avatar_photo'])
  :('https://placehold.co/96x96?text=%20');
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
          <div class="edit-avatar">
            <img src="<?= htmlspecialchars($avatarSrc) ?>" alt="Avatar" class="avatar-preview">
          </div>
          <span class="avatar-note">Upload a new avatar (JPG/PNG/WEBP)</span>
        </div>

        <form action="./actions/post_profile_update.php" method="post" enctype="multipart/form-data">
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
                        stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <p class="dz-text">
                  Drag &amp; drop your avatar here or
                  <button type="button" class="dz-link" id="avatarBrowseBtn">browse</button>
                </p>
              </div>

              <!-- IMPORTANT: give the preview an initial src so placeholder always shows -->
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

  <script>
    const avatarInput  = document.getElementById('avatarInput');
    const avatarDz     = document.getElementById('avatarDropzone');
    const avatarDzEmpty= document.getElementById('avatarDzEmpty');
    const avatarPreview= document.getElementById('avatarPreview');
    const avatarRemove = document.getElementById('avatarRemoveBtn');
    const avatarBrowse = document.getElementById('avatarBrowseBtn');
    const originalUrl  = "<?= htmlspecialchars($avatarSrc) ?>";

    // Start with the current (or placeholder) avatar visible in the preview.
    // Hide the “empty” message.
    avatarPreview.src = originalUrl;
    avatarDzEmpty.hidden = true;

    function setFile(file) {
      if (!file.type.startsWith('image/')) { alert('Please choose an image file.'); return; }
      if (file.size > 10 * 1024 * 1024)   { alert('Max size is 10MB.'); return; }

      const r = new FileReader();
      r.onload = (e) => {
        avatarPreview.src = e.target.result;
      };
      r.readAsDataURL(file);

      const dt = new DataTransfer();
      dt.items.add(file);
      avatarInput.files = dt.files;
    }

    function clearFile(keepOriginal) {
      avatarInput.value = '';
      avatarPreview.src = keepOriginal ? originalUrl : originalUrl; // keep showing something (placeholder or old)
    }

    if (avatarBrowse) avatarBrowse.addEventListener('click', () => avatarInput.click());
    avatarDz.addEventListener('click', (e) => { if (e.target === avatarDz) avatarInput.click(); });
    avatarPreview.addEventListener('click', () => avatarInput.click());
    avatarRemove.addEventListener('click', (e) => { e.preventDefault(); clearFile(true); });

    ['dragenter','dragover'].forEach(ev => avatarDz.addEventListener(ev, e => { e.preventDefault(); avatarDz.classList.add('is-drag'); }));
    ['dragleave','drop'].forEach(ev => avatarDz.addEventListener(ev, e => { e.preventDefault(); avatarDz.classList.remove('is-drag'); }));
    avatarDz.addEventListener('drop', (e) => {
      const f = e.dataTransfer.files[0];
      if (f) setFile(f);
    });

    avatarInput.addEventListener('change', function () {
      const f = this.files[0];
      if (f) setFile(f);
    });
  </script>
</body>
</html>
