<?php
require __DIR__ . '/includes/flash.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/sidebar.php';

if (empty($_SESSION['profile_id'])) { header('Location: ./auth/login.php'); exit; }

$me   = (int)$_SESSION['profile_id'];
$conn = db();

/* admin check */
$stmt = $conn->prepare("SELECT role FROM profiles WHERE profile_id=?");
$stmt->bind_param('i', $me);
$stmt->execute();
$meRow = $stmt->get_result()->fetch_assoc();
$stmt->close();
$isAdmin = (($meRow['role'] ?? 'user') === 'admin');
if (!$isAdmin) { header('Location: ./index.php'); exit; }

/* paths */
$baseUrl       = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$publicUploads = $baseUrl . '/uploads/';

/* fetch about page */
$stmt = $conn->prepare("SELECT page_id, title, content, image_path FROM pages WHERE slug='about' LIMIT 1");
$stmt->execute();
$page = $stmt->get_result()->fetch_assoc();
$stmt->close();

$pageId     = (int)($page['page_id'] ?? 0);
$title      = $page['title']   ?? 'About Picturesque';
$content    = $page['content'] ?? '';
$image_path = $page['image_path'] ?? null;
$imgUrl     = $image_path ? ($publicUploads . htmlspecialchars($image_path)) : null;

/* handle post */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!check_csrf($_POST['csrf'] ?? null)) {
    set_flash('err','Invalid CSRF token.');
    header('Location: ./settings.php'); exit;
  }

  $newTitle   = trim($_POST['title'] ?? '');
  $newContent = trim($_POST['content'] ?? '');
  $resetImg   = !empty($_POST['reset_image']); // comes from hidden field "1" when X pressed

  if ($newTitle === '' || $newContent === '') {
    set_flash('err','Title and content are required.');
    header('Location: ./settings.php'); exit;
  }

  $newFilename = $image_path;          // keep old by default
  if ($resetImg) $newFilename = null;  // user clicked X (remove)

  // optional new upload
  if (!empty($_FILES['image']['name'])) {
    $file = $_FILES['image'];
    $ok   = ['image/jpeg','image/png','image/webp','image/gif'];
    if (!in_array($file['type'], $ok)) {
      set_flash('err','Please upload JPG, PNG, WEBP, or GIF.');
      header('Location: ./settings.php'); exit;
    }
    if ($file['size'] > 6*1024*1024) {
      set_flash('err','Image too large (max 6MB).');
      header('Location: ./settings.php'); exit;
    }
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $name = 'about_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = __DIR__ . '/uploads/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
      set_flash('err','Upload failed.');
      header('Location: ./settings.php'); exit;
    }
    $newFilename = $name;
  }

  if ($pageId > 0) {
    $stmt = $conn->prepare("UPDATE pages SET title=?, content=?, image_path=?, updated_by=?, updated_at=NOW() WHERE page_id=?");
    $stmt->bind_param('sssii', $newTitle, $newContent, $newFilename, $me, $pageId);
  } else {
    $slug = 'about';
    $stmt = $conn->prepare("INSERT INTO pages (slug, title, content, image_path, updated_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('ssssi', $slug, $newTitle, $newContent, $newFilename, $me);
  }
  $stmt->execute(); $stmt->close();

  set_flash('ok','About page updated successfully.');
  header('Location: ./settings.php'); exit;
}

$conn->close();

/* cache-bust main.css automatically */
$cssPath = __DIR__ . '/public/css/main.css';
$ver = file_exists($cssPath) ? filemtime($cssPath) : time();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Settings · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=<?= $ver ?>">
</head>
<body>

<?php if ($m = get_flash('ok')): ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
<?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

<div class="layout">
  <?php render_sidebar(['isAdmin' => true]); ?>

  <main class="content">
    <div class="settings-wrap">
      <h1 class="page-title">Admin Settings</h1>
      <p class="sub">Edit the content of the About page below.</p>

      <form class="about-card" method="post" action="settings.php" enctype="multipart/form-data">
        <div class="pad form-grid">
          <div class="label">Title</div>
          <div class="row">
            <input class="input" name="title" value="<?= htmlspecialchars($title) ?>" required>
          </div>

          <div class="label">Content</div>
          <div class="row">
            <textarea class="textarea" name="content" required><?= htmlspecialchars($content) ?></textarea>
          </div>

          <div class="label">Page Image</div>
          <div class="row">
            <!-- Preview + remove X -->
            <div class="image-preview-wrapper">
              <img id="preview" class="preview"
                   src="<?= $imgUrl ?: 'https://placehold.co/800x260?text=No+image' ?>"
                   alt="About image">
              <?php if ($imgUrl): ?>
                <button type="button" class="remove-image" id="removeImageBtn" title="Remove image">×</button>
              <?php endif; ?>
            </div>
            <input type="hidden" name="reset_image" id="resetImage" value="">

            <!-- File picker -->
            <div class="filebar">
              <label for="img" class="filebtn">Choose image…</label>
              <input id="img" type="file" name="image" accept="image/*">
              <span class="help">JPG, PNG, WEBP, GIF. Max 6MB.</span>
            </div>
          </div>

          <div></div>
          <div class="btns">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <button class="btn-primary" type="submit">Save Changes</button>
            <a class="btn-ghost" href="./about.php">View About Page</a>
          </div>
        </div>
      </form>
    </div>
  </main>
</div>

<script>
  const fileInput  = document.getElementById('img');
  const preview    = document.getElementById('preview');
  const removeBtn  = document.getElementById('removeImageBtn');
  const resetInput = document.getElementById('resetImage');

  // live preview on file choose
  if (fileInput) {
    fileInput.addEventListener('change', e => {
      const f = e.target.files && e.target.files[0];
      if (!f) return;
      preview.src = URL.createObjectURL(f);
      if (resetInput) resetInput.value = ""; // new image chosen => don't reset
      if (removeBtn)  removeBtn.style.display = 'none';
    });
  }

  // remove current image (set reset flag)
  if (removeBtn) {
    removeBtn.addEventListener('click', e => {
      e.preventDefault();
      preview.src = 'https://placehold.co/800x260?text=No+image';
      if (resetInput) resetInput.value = "1";
      removeBtn.style.display = 'none';
      // also clear any selected file
      if (fileInput) fileInput.value = "";
    });
  }
</script>

</body>
</html>