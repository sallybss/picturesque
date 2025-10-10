<?php
require __DIR__ . '/includes/flash.php';
require __DIR__ . '/includes/db.php';

if (empty($_SESSION['profile_id'])) {
  header('Location: ./auth/login.php');
  exit;
}

$me = (int)$_SESSION['profile_id'];
$conn = db();
$stmt = $conn->prepare("
  SELECT display_name, email, avatar_photo, role
  FROM profiles
  WHERE profile_id = ?
");
$stmt->bind_param('i', $me);
$stmt->execute();
$meRow = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();
$isAdmin = (($meRow['role'] ?? '') === 'admin');
$avatarSrc = !empty($meRow['avatar_photo']) ? 'uploads/' . htmlspecialchars($meRow['avatar_photo']) : 'https://placehold.co/96x96?text=%20';
?>

<?php $cur = basename($_SERVER['PHP_SELF']); ?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Edit Profile · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=7">
</head>

<body>
  <?php if ($m = get_flash('ok')): ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
  <?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

  <div class="layout">
    <!-- Sidebar -->
    <aside class="sidenav">
      <div class="brand">PICTURESQUE</div>

      <a class="create-btn" href="./create.php">☆ Create</a>

      <nav class="nav">
        <a href="./index.php" class="<?= $cur === 'index.php'   ? 'active' : '' ?>">
          <svg class="ico" viewBox="0 0 256 256" fill="currentColor" aria-hidden="true">
            <path d="M219.31,108.68l-80-80a16,16,0,0,0-22.62,0l-80,80A15.87,15.87,0,0,0,32,120v96a8,8,0,0,0,8,8h64a8,8,0,0,0,8-8V160h32v56a8,8,0,0,0,8,8h64a8,8,0,0,0,8-8V120A15.87,15.87,0,0,0,219.31,108.68ZM208,208H160V152a8,8,0,0,0-8-8H104a8,8,0,0,0-8,8v56H48V120l80-80,80,80Z"></path>
          </svg>
          Home
          <span class="badge">5</span> <!-- notification optional, can remove -->
        </a>

        <a href="./profile.php" class="<?= in_array($cur, ['profile.php', 'profile_edit.php']) ? 'active' : '' ?>">
          <svg class="ico" viewBox="0 0 256 256" fill="currentColor" aria-hidden="true">
            <path d="M230.92,212c-15.23-26.33-38.7-45.21-66.09-54.16a72,72,0,1,0-73.66,0C63.78,166.78,40.31,185.66,25.08,212a8,8,0,1,0,13.85,8c18.84-32.56,52.14-52,89.07-52s70.23,19.44,89.07,52a8,8,0,1,0,13.85-8ZM72,96a56,56,0,1,1,56,56A56.06,56.06,0,0,1,72,96Z"></path>
          </svg>
          My Profile
        </a>

        <?php if ($isAdmin): ?>
          <a href="./admin.php" class="<?= $cur === 'admin.php' ? 'active' : '' ?>">🛡️ Admin</a>
        <?php endif; ?>

        <div class="rule"></div>

        <a href="./settings.php" class="<?= $cur === 'settings.php'   ? 'active' : '' ?>">
          <span class="icon">⚙️</span>
          Settings
        </a>
        <a href="./auth/logout.php">
          <svg class="ico" viewBox="0 0 256 256" fill="currentColor" aria-hidden="true">
            <path d="M120,216a8,8,0,0,1-8,8H48a8,8,0,0,1-8-8V40a8,8,0,0,1,8-8h64a8,8,0,0,1,0,16H56V208h56A8,8,0,0,1,120,216Zm109.66-93.66-40-40a8,8,0,0,0-11.32,11.32L204.69,120H112a8,8,0,0,0,0,16h92.69l-26.35,26.34a8,8,0,0,0,11.32,11.32l40-40A8,8,0,0,0,229.66,122.34Z"></path>
          </svg>
          Logout
        </a>
      </nav>
    </aside>


    <main class="content">
      <div class="form-card">
        <h2 class="card-title">Edit Profile</h2>

        <div class="edit-avatar-row">
          <div class="edit-avatar">
            <img src="<?= $avatarSrc ?>" alt="Avatar">
          </div>
          <span class="avatar-note">Upload a new avatar (JPG/PNG/WEBP)</span>
        </div>

        <form action="./actions/post_profile_update.php" method="post" enctype="multipart/form-data">
          <div class="form-row">
            <label class="label-sm" for="display_name">Display name</label>
            <input class="input-sm" id="display_name" name="display_name"
              value="<?= htmlspecialchars($meRow['display_name'] ?? '') ?>" required>
          </div>

          <div class="form-row">
            <label class="label-sm" for="avatarInput">Avatar image</label>
            <!-- Hidden file input + dropzone with preview -->
            <input id="avatarInput" class="file-input" type="file" name="avatar" accept="image/*">
            <div class="dropzone" id="avatarDropzone">
              <!-- Empty state -->
              <div class="dz-empty" id="avatarDzEmpty">
                <svg class="dz-icon" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M12 16V6m0 0l-4 4m4-4l4 4M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <p class="dz-text">
                  Drag &amp; drop your avatar here or
                  <button type="button" class="dz-link" id="avatarBrowseBtn">browse</button>
                </p>
              </div>

              <!-- Preview state -->
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
    const originalAvatarSrc = "<?= $avatarSrc ?>";

    // --- Show current avatar as preview, and show remove button so user can clear it ---
    (function initAvatarPreview() {
      if (originalAvatarSrc) {
        avatarPreview.src = originalAvatarSrc;
        avatarPreview.hidden = false;
        avatarRemove.hidden = false;
        avatarDzEmpty.hidden = true;
        avatarDz.classList.add('has-image');
      }
    })();

    // Open file dialog
    if (avatarBrowse) avatarBrowse.addEventListener('click', () => avatarInput.click());

    // Clicking the dropzone background OR the image opens the picker
    avatarDz.addEventListener('click', (e) => {
      if (e.target === avatarDz) avatarInput.click();
    });
    avatarPreview.addEventListener('click', () => avatarInput.click());

    // Handle input change
    avatarInput.addEventListener('change', function() {
      const file = this.files[0];
      if (file) setAvatarFile(file);
    });

    // Drag & drop
    ['dragenter', 'dragover'].forEach(ev => avatarDz.addEventListener(ev, e => {
      e.preventDefault();
      avatarDz.classList.add('is-drag');
    }));
    ['dragleave', 'drop'].forEach(ev => avatarDz.addEventListener(ev, e => {
      e.preventDefault();
      avatarDz.classList.remove('is-drag');
    }));
    avatarDz.addEventListener('drop', (e) => {
      const file = e.dataTransfer.files[0];
      if (file) setAvatarFile(file);
    });

    // Apply file → preview
    function setAvatarFile(file) {
      if (!file.type.startsWith('image/')) {
        alert('Please choose an image file.');
        clearAvatarFile(false);
        return;
      }
      if (file.size > 10 * 1024 * 1024) {
        alert('Max size is 10MB.');
        clearAvatarFile(false);
        return;
      }

      const reader = new FileReader();
      reader.onload = (e) => {
        avatarPreview.src = e.target.result;
        avatarPreview.hidden = false;
        avatarRemove.hidden = false;
        avatarDzEmpty.hidden = true;
        avatarDz.classList.add('has-image');
      };
      reader.readAsDataURL(file);

      // ensure dropped file is attached to the input
      const dt = new DataTransfer();
      dt.items.add(file);
      avatarInput.files = dt.files;
    }

    function clearAvatarFile(keepOriginalUrl) {
      avatarInput.value = '';

      if (keepOriginalUrl && originalAvatarSrc) {
        avatarPreview.src = originalAvatarSrc;
        avatarPreview.hidden = false;
        avatarDzEmpty.hidden = true;
        avatarDz.classList.add('has-image');
        avatarRemove.hidden = false;
      } else {
        avatarPreview.src = '';
        avatarPreview.hidden = true;
        avatarDzEmpty.hidden = false;
        avatarDz.classList.remove('has-image');
        avatarRemove.hidden = true;
      }
    }

    avatarRemove.addEventListener('click', (e) => {
      e.preventDefault();
      clearAvatarFile(false);
    });
  </script>
</body>

</html>