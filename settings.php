<?php
require_once __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/categories_repository.php';
require __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/topbar.php'; // 👈 add this

$catsRepo = new CategoriesRepository();

$me = Auth::requireAdminOrRedirect('./index.php');

$paths = new Paths();

$profiles = new ProfileRepository();
$meRow = $profiles->getHeader($me);
$isAdmin = (($meRow['role'] ?? 'user') === 'admin');
if (!$isAdmin) {
  header('Location: ./index.php');
  exit;
}

$pages = new PagesRepository();
$page  = $pages->getAbout();

// ---- Load Rules page (slug='rules') ----
$rules        = $pages->getBySlug('rules');
$rulesTitle   = $rules['title']   ?? 'Rules & Regulations';
$rulesContent = $rules['content'] ?? '';

$pageId    = (int)($page['page_id'] ?? 0);
$title     = $page['title']   ?? 'About Picturesque';
$content   = $page['content'] ?? '';
$imagePath = $page['image_path'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!check_csrf($_POST['csrf'] ?? null)) {
    set_flash('err', 'Invalid CSRF token.');
    header('Location: ./settings.php');
    exit;
  }
  $action = trim($_POST['action'] ?? '');

  // ---- Rules: save text (title + content) ----
  if ($action === 'save_rules') {
    $newTitle   = trim($_POST['rules_title']   ?? 'Rules & Regulations');
    $newContent = trim($_POST['rules_content'] ?? '');

    if ($newTitle === '' || $newContent === '') {
      set_flash('err', 'Rules title and content are required.');
      header('Location: ./settings.php#rules');
      exit;
    }

    // Text-only for now; no image upload. updated_by = $me
    $pages->upsert('rules', $newTitle, $newContent, null, $me);

    set_flash('ok', 'Rules & Regulations saved.');
    header('Location: ./settings.php#rules');
    exit;
  }

  // ---- Categories handlers ----
  if ($action === 'add_cat') {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
      set_flash('err', 'Category name required.');
      header('Location: ./settings.php#cats');
      exit;
    }
    $slug = slugify($name);

    try {
      $st = DB::get()->prepare("INSERT INTO categories (category_name, slug, active) VALUES (?, ?, 1)");
      $st->bind_param('ss', $name, $slug);
      $st->execute();
      $st->close();
      set_flash('ok', 'Category added.');
    } catch (mysqli_sql_exception $e) {
      set_flash('err', 'Could not add (duplicate name/slug?).');
    }
    header('Location: ./settings.php#cats');
    exit;
  }

  if ($action === 'toggle_cat') {
    $id = (int)($_POST['category_id'] ?? 0);
    if ($id <= 0) {
      set_flash('err', 'Bad id.');
      header('Location: ./settings.php#cats');
      exit;
    }

    $st = DB::get()->prepare("UPDATE categories SET active = 1 - active WHERE category_id=?");
    $st->bind_param('i', $id);
    $st->execute();
    $st->close();
    set_flash('ok', 'Toggled.');
    header('Location: ./settings.php#cats');
    exit;
  }

  if ($action === 'delete_cat') {
    $id = (int)($_POST['category_id'] ?? 0);
    if ($id <= 0) {
      set_flash('err', 'Bad id.');
      header('Location: ./settings.php#cats');
      exit;
    }

    $st = DB::get()->prepare("DELETE FROM categories WHERE category_id = ?");
    $st->bind_param('i', $id);
    try {
      $st->execute();
      set_flash('ok', 'Category deleted.');
    } catch (mysqli_sql_exception $e) {
      set_flash('err', 'Could not delete category.');
    }
    $st->close();
    header('Location: ./settings.php#cats');
    exit;
  }

  // ---- About page save (title/content/image) ----
  $newTitle   = trim($_POST['title'] ?? '');
  $newContent = trim($_POST['content'] ?? '');
  $resetImg   = !empty($_POST['reset_image']);

  if ($newTitle === '' || $newContent === '') {
    set_flash('err', 'Title and content are required.');
    header('Location: ./settings.php');
    exit;
  }

  $newImagePath = $imagePath;
  if ($resetImg) $newImagePath = null;

  if (!empty($_FILES['image']['name'])) {
    $f = $_FILES['image'];
    $ok = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($f['type'], $ok)) {
      set_flash('err', 'Upload JPG, PNG, WEBP or GIF.');
      header('Location: ./settings.php');
      exit;
    }
    if ($f['size'] > 6 * 1024 * 1024) {
      set_flash('err', 'Image too large (max 6MB).');
      header('Location: ./settings.php');
      exit;
    }

    $ext  = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    $name = 'about_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = __DIR__ . '/uploads/' . $name;

    if (!move_uploaded_file($f['tmp_name'], $dest)) {
      set_flash('err', 'Upload failed.');
      header('Location: ./settings.php');
      exit;
    }
    $newImagePath = $name;
  }

  if ($pageId > 0) {
    $pages->updateAbout($pageId, $newTitle, $newContent, $newImagePath, $me);
  } else {
    $pages->insertAbout($newTitle, $newContent, $newImagePath, $me);
  }

  set_flash('ok', 'About page updated.');
  header('Location: ./settings.php');
  exit;
}

$cats = DB::get()->query("
  SELECT
    c.category_id,
    c.category_name,
    c.slug,
    c.active,
    COUNT(p.picture_id) AS pic_count
  FROM categories c
  LEFT JOIN pictures p ON p.category_id = c.category_id
  GROUP BY c.category_id, c.category_name, c.slug, c.active
  ORDER BY c.active DESC, c.category_name
")->fetch_all(MYSQLI_ASSOC);

$imgUrl = $imagePath ? ($paths->uploads . htmlspecialchars($imagePath)) : null;
$cssVer = file_exists(__DIR__ . '/public/css/main.css') ? filemtime(__DIR__ . '/public/css/main.css') : time();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Settings · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=<?= $cssVer ?>">
</head>
<body>

  <?php if ($m = get_flash('ok')):  ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
  <?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

  <div class="layout">
    <?php render_sidebar(['isAdmin' => true]); ?>

    <main class="content">
      <!-- 👇 userbox in the top-right -->
      <div class="content-top">
        <?php render_topbar_userbox($meRow); ?>
      </div>

      <div class="settings-wrap">
        <h1 class="page-title">Admin Settings</h1>
        <p class="sub">Edit the About page and Rules & Regulations.</p>

        <section id="cats" class="card pad" style="margin-bottom:1rem">
          <h2 class="section-title" style="margin-bottom:.75rem">Categories</h2>

          <form class="flex-row" method="post" action="settings.php" style="gap:.5rem; align-items:center; margin-bottom:1rem">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="action" value="add_cat">
            <label class="label" style="margin:0">Name</label>
            <input class="input" name="name" placeholder="e.g. Night" required style="max-width:260px">
            <button class="btn-primary" type="submit">Add</button>
          </form>

          <table class="cats-table">
            <tr>
              <th>Name</th>
              <th>Photos</th>
              <th>Status</th>
              <th style="white-space:nowrap">Actions</th>
            </tr>
            <?php foreach ($cats as $c): ?>
              <tr>
                <td><?= htmlspecialchars($c['category_name']) ?></td>

                <td>
                  <?= (int)$c['pic_count'] ?>
                  <?php if ((int)$c['pic_count'] > 0): ?>
                    • <a class="btn-link" href="index.php?cat=<?= urlencode($c['slug']) ?>" target="_blank" rel="noopener">View</a>
                  <?php endif; ?>
                </td>

                <td>
                  <?php if ((int)$c['active'] === 1): ?>
                    <span class="badge badge-green">Active</span>
                  <?php else: ?>
                    <span class="badge badge-gray">Hidden</span>
                  <?php endif; ?>
                </td>

                <td style="white-space:nowrap">
                  <form method="post" action="settings.php" style="display:inline">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="action" value="toggle_cat">
                    <input type="hidden" name="category_id" value="<?= (int)$c['category_id'] ?>">
                    <button class="btn-ghost" type="submit">
                      <?= (int)$c['active'] ? 'Hide' : 'Show' ?>
                    </button>
                  </form>

                  <form method="post" action="settings.php" style="display:inline"
                    onsubmit="return confirm('Delete this category? Photos will keep their image but lose this category.');">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete_cat">
                    <input type="hidden" name="category_id" value="<?= (int)$c['category_id'] ?>">
                    <button class="btn-danger" type="submit">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </table>
        </section>

        <!-- Rules & Regulations (editable) -->
        <section id="rules" class="card pad" style="margin-bottom:1rem">
          <h2 class="section-title" style="margin-bottom:.75rem">Rules &amp; Regulations</h2>

          <form method="post" action="settings.php#rules" class="form-grid">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="action" value="save_rules">

            <div class="label">Title</div>
            <div class="row">
              <input class="input" name="rules_title" value="<?= htmlspecialchars($rulesTitle) ?>" required>
            </div>

            <div class="label">Content</div>
            <div class="row">
              <textarea class="textarea" name="rules_content" rows="10" required><?= htmlspecialchars($rulesContent) ?></textarea>
            </div>

            <div></div>
            <div class="btns">
              <button class="btn-primary" type="submit">Save Rules</button>
              <a class="btn-ghost" href="./rules.php" target="_blank" rel="noopener">View Public Page</a>
            </div>
          </form>
        </section>

        <!-- About page -->
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
              <div class="image-preview-wrapper">
                <img id="preview" class="preview"
                  src="<?= $imgUrl ?: 'https://placehold.co/800x260?text=No+image' ?>"
                  alt="About image">
                <?php if ($imgUrl): ?>
                  <button type="button" class="remove-image" id="removeImageBtn" title="Remove image">×</button>
                <?php endif; ?>
              </div>

              <input type="hidden" name="reset_image" id="resetImage" value="">
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
    const fileInput = document.getElementById('img');
    const preview = document.getElementById('preview');
    const removeBtn = document.getElementById('removeImageBtn');
    const resetInput = document.getElementById('resetImage');

    if (fileInput) {
      fileInput.addEventListener('change', e => {
        const f = e.target.files && e.target.files[0];
        if (!f) return;
        preview.src = URL.createObjectURL(f);
        if (resetInput) resetInput.value = "";
        if (removeBtn) removeBtn.style.display = 'none';
      });
    }

    if (removeBtn) {
      removeBtn.addEventListener('click', e => {
        e.preventDefault();
        preview.src = 'https://placehold.co/800x260?text=No+image';
        if (resetInput) resetInput.value = "1";
        removeBtn.style.display = 'none';
        if (fileInput) fileInput.value = "";
      });
    }
  </script>

</body>
</html>