<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/views/topbar.php';
require_once __DIR__ . '/includes/views/sidebar.php';

$me = Auth::requireAdminOrRedirect('./index.php');

$paths    = new Paths();
$profiles = new ProfileRepository();
$meRow    = $profiles->getHeader($me);
$isAdmin  = (strtolower(trim($meRow['role'] ?? 'user')) === 'admin');
if (!$isAdmin) {
  header('Location: ./index.php');
  exit;
}

$basePath = strtok($_SERVER['REQUEST_URI'], '?');

$pages        = new PagesRepository();
$page         = $pages->getAbout();
$rules        = $pages->getBySlug('rules');
$rulesTitle   = $rules['title']   ?? 'Rules & Regulations';
$rulesContent = $rules['content'] ?? '';

$pageId    = (int)($page['page_id'] ?? 0);
$title     = $page['title']      ?? 'About Picturesque';
$content   = $page['content']    ?? '';
$imagePath = $page['image_path'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!check_csrf($_POST['csrf'] ?? null)) {
    set_flash('err', 'Invalid CSRF token.');
    header("Location: {$basePath}");
    exit;
  }

  $action = trim($_POST['action'] ?? '');

  if ($action === 'save_rules') {
    $newTitle = trim($_POST['rules_title']   ?? 'Rules & Regulations');
    $rawRules = trim($_POST['rules_content'] ?? '');

    $allowedTags = '<h2><h3><p><ul><ol><li><strong><em><b><i><a><br>';
    $newContent  = strip_tags($rawRules, $allowedTags);

    $newContent = preg_replace(
      '~href\s*=\s*["\']\s*javascript:[^"\']*["\']~i',
      'href="#"',
      $newContent
    );

    $newContent = preg_replace(
      '~\s(on\w+|style)\s*=\s*(".*?"|\'.*?\'|[^\s>]+)~i',
      '',
      $newContent
    );

    if ($newTitle === '' || $newContent === '') {
      set_flash('err', 'Rules title and content are required.');
      header("Location: {$basePath}#rules");
      exit;
    }

    $pages->upsert('rules', $newTitle, $newContent, null, $me);
    set_flash('ok', 'Rules & Regulations saved.');
    header("Location: {$basePath}#rules");
    exit;
  }

  if ($action === 'add_cat') {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
      set_flash('err', 'Category name required.');
      header("Location: {$basePath}#cats");
      exit;
    }

    $slug = slugify($name);
    try {
      $st = DB::get()->prepare(
        "INSERT INTO categories (category_name, slug, active) VALUES (?, ?, 1)"
      );
      $st->bind_param('ss', $name, $slug);
      $st->execute();
      $st->close();
      set_flash('ok', 'Category added.');
    } catch (mysqli_sql_exception $e) {
      set_flash('err', 'Could not add (duplicate name/slug?).');
    }
    header("Location: {$basePath}#cats");
    exit;
  }

  if ($action === 'toggle_cat') {
    $id = (int)($_POST['category_id'] ?? 0);
    if ($id <= 0) {
      set_flash('err', 'Bad id.');
      header("Location: {$basePath}#cats");
      exit;
    }

    $st = DB::get()->prepare(
      "UPDATE categories SET active = 1 - active WHERE category_id=?"
    );
    $st->bind_param('i', $id);
    $st->execute();
    $st->close();

    set_flash('ok', 'Toggled.');
    header("Location: {$basePath}#cats");
    exit;
  }

  if ($action === 'delete_cat') {
    $id = (int)($_POST['category_id'] ?? 0);
    if ($id <= 0) {
      set_flash('err', 'Bad id.');
      header("Location: {$basePath}#cats");
      exit;
    }

    $st = DB::get()->prepare(
      "DELETE FROM categories WHERE category_id = ?"
    );
    $st->bind_param('i', $id);
    try {
      $st->execute();
      set_flash('ok', 'Category deleted.');
    } catch (mysqli_sql_exception $e) {
      set_flash('err', 'Could not delete category.');
    }
    $st->close();

    header("Location: {$basePath}#cats");
    exit;
  }

  if ($action === 'save_about') {
    $newTitle   = trim($_POST['title']   ?? '');
    $rawContent = trim($_POST['content'] ?? '');

    $allowedTags = '<h2><h3><p><ul><ol><li><strong><em><b><i><a><br>';
    $newContent  = strip_tags($rawContent, $allowedTags);

    $newContent = preg_replace(
      '~href\s*=\s*["\']\s*javascript:[^"\']*["\']~i',
      'href="#"',
      $newContent
    );

    $newContent = preg_replace(
      '~\s(on\w+|style)\s*=\s*(".*?"|\'.*?\'|[^\s>]+)~i',
      '',
      $newContent
    );

    $resetImg = !empty($_POST['reset_image']);

    if ($newTitle === '' || $newContent === '') {
      set_flash('err', 'Title and content are required.');
      header("Location: {$basePath}#about");
      exit;
    }

    $newImagePath = $imagePath;
    if ($resetImg) {
      $newImagePath = null;
    }

    if (!empty($_FILES['image']['name'])) {
      $f  = $_FILES['image'];
      $ok = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mime  = $finfo ? finfo_file($finfo, $f['tmp_name']) : null;
      if ($finfo) {
        finfo_close($finfo);
      }

      if (!in_array($mime, $ok, true)) {
        set_flash('err', 'Upload JPG, PNG, WEBP or GIF.');
        header("Location: {$basePath}#about");
        exit;
      }

      if ($f['size'] > 6 * 1024 * 1024) {
        set_flash('err', 'Image too large (max 6MB).');
        header("Location: {$basePath}#about");
        exit;
      }

      $ext  = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
      $name = 'about_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
      $dest = __DIR__ . '/uploads/' . $name;

      if (!move_uploaded_file($f['tmp_name'], $dest)) {
        set_flash('err', 'Upload failed.');
        header("Location: {$basePath}#about");
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
    header("Location: {$basePath}#about");
    exit;
  }
}

$cats = DB::get()->query("
  SELECT c.category_id, c.category_name, c.slug, c.active,
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
  <div id="flash-stack" class="flash-stack">
    <?php if ($m = get_flash('ok')): ?>
      <div class="flash flash-ok"><?= htmlspecialchars($m) ?></div>
    <?php endif; ?>
    <?php if ($m = get_flash('err')): ?>
      <div class="flash flash-err"><?= htmlspecialchars($m) ?></div>
    <?php endif; ?>
  </div>

  <div class="layout">
    <?php render_sidebar(['isAdmin' => true, 'isGuest' => false]); ?>

    <main class="content">
      <div class="content-top">
        <div class="top-actions" style="display:flex;align-items:center;justify-content:space-between;width:100%;">
          <button class="hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false">☰</button>
          <?php render_topbar_userbox($meRow); ?>
        </div>
      </div>

      <div class="settings-wrap">
        <h1 class="page-title">Admin Settings</h1>
        <p class="sub">Manage categories, Rules &amp; Regulations, and the About page.</p>

        <section class="form-card" id="cats">
          <h2 class="section-title">Categories</h2>
          <p class="sub">Categories are used on the “New post” form. You can add, hide/show, or delete them.</p>

          <form method="post" action="<?= htmlspecialchars($basePath) ?>#cats" class="form-grid" style="margin-bottom:18px;">
            <div>
              <label class="label" for="catName">Name</label>
            </div>
            <div>
              <div class="cat-add-row">
                <input
                  id="catName"
                  class="input"
                  type="text"
                  name="name"
                  maxlength="40"
                  placeholder="e.g. Night"
                  required>
                <button type="submit" class="btn-primary cat-add-btn">Add</button>
              </div>
              <small class="help">Max 40 characters. Keep names short and descriptive.</small>
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
              <input type="hidden" name="action" value="add_cat">
            </div>
          </form>

          <table class="cats-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Photos</th>
                <th>Status</th>
                <th style="text-align:right;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($cats as $cat): ?>
                <tr>
                  <td><?= htmlspecialchars($cat['category_name']) ?></td>
                  <td><?= (int)$cat['pic_count'] ?></td>
                  <td>
                    <?php if ($cat['active']): ?>
                      <span class="badge badge-green">Active</span>
                    <?php else: ?>
                      <span class="badge badge-gray">Hidden</span>
                    <?php endif; ?>
                  </td>
                  <td style="text-align:right; white-space:nowrap;">
                    <form method="post" action="<?= htmlspecialchars($basePath) ?>#cats" style="display:inline;">
                      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                      <input type="hidden" name="action" value="toggle_cat">
                      <input type="hidden" name="category_id" value="<?= (int)$cat['category_id'] ?>">
                      <button type="submit" class="btn-ghost pill" style="padding:6px 10px;font-size:13px;">
                        <?= $cat['active'] ? 'Hide' : 'Show' ?>
                      </button>
                    </form>

                    <form method="post" action="./settings.php#cats" style="display:inline;" onsubmit="return confirm('Delete this category? Pictures keeping this category will lose it.');">
                      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                      <input type="hidden" name="action" value="delete_cat">
                      <input type="hidden" name="category_id" value="<?= (int)$cat['category_id'] ?>">
                      <button type="submit" class="btn-danger pill" style="padding:6px 10px;font-size:13px;">
                        Delete
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </section>

        <section class="form-card" id="rules">
          <h2 class="section-title">Rules &amp; Regulations</h2>
          <p class="sub">Define how people should behave on Picturesque. This text is shown on the public rules page.</p>

          <form method="post" action="<?= htmlspecialchars($basePath) ?>#rules">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="action" value="save_rules">

            <div class="form-row">
              <label class="label" for="rulesTitle">Title</label>
              <input
                id="rulesTitle"
                class="input"
                type="text"
                name="rules_title"
                maxlength="80"
                value="<?= htmlspecialchars($rulesTitle) ?>"
                required>
            </div>

            <div class="form-row">
              <div class="label-row">
                <label class="label" for="rulesContent">Content</label>
                <span class="field-counter" id="rulesContentCount">0 / 3000</span>
              </div>
              <textarea
                id="rulesContent"
                class="input textarea"
                name="rules_content"
                rows="10"
                maxlength="3000"
                required><?= htmlspecialchars($rulesContent) ?></textarea>
            </div>

            <div class="btns">
              <button type="submit" class="btn-primary">Save Rules</button>
            </div>
          </form>
        </section>

        <section class="form-card" id="about">
          <h2 class="section-title">About Picturesque</h2>
          <p class="sub">Describe what Picturesque is and why it exists. This content is shown on the About page.</p>

          <form method="post" action="<?= htmlspecialchars($basePath) ?>#about" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="action" value="save_about">

            <div class="form-row">
              <label class="label" for="aboutTitle">Title</label>
              <input
                id="aboutTitle"
                class="input"
                type="text"
                name="title"
                maxlength="80"
                value="<?= htmlspecialchars($title) ?>"
                required>
            </div>

            <div class="form-row">
              <div class="label-row">
                <label class="label" for="aboutContent">Content</label>
                <span class="field-counter" id="aboutContentCount">0 / 2000</span>
              </div>
              <textarea
                id="aboutContent"
                class="input textarea"
                name="content"
                rows="8"
                maxlength="2000"
                required><?= htmlspecialchars($content) ?></textarea>
            </div>

            <div class="form-row">
              <label class="label">Page image</label>

              <?php if ($imgUrl): ?>
                <div class="image-preview-wrapper">
                  <img class="preview" src="<?= $imgUrl ?>" alt="Current About image">
                  <button type="submit" name="reset_image" value="1" class="remove-image" title="Remove image">×</button>
                </div>
              <?php endif; ?>

              <div class="filebar">
                <label class="filebtn" for="aboutImage">Choose image…</label>
                <input id="aboutImage" type="file" name="image" accept="image/*">
                <span class="help">JPG, PNG, WEBP or GIF, max 6&nbsp;MB.</span>
              </div>
            </div>

            <div class="btns">
              <button type="submit" class="btn-primary">Save About Page</button>
            </div>
          </form>
        </section>
      </div>
    </main>
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

      (function() {
        function setupCounter(fieldId, counterId, max) {
          const field = document.getElementById(fieldId);
          const counter = document.getElementById(counterId);
          if (!field || !counter) return;

          const update = () => {
            let text = field.value || "";
            if (text.length > max) {
              text = text.slice(0, max);
              field.value = text;
            }
            counter.textContent = `${text.length} / ${max}`;
            if (text.length >= max) {
              field.classList.add('at-limit');
              counter.classList.add('at-limit');
            } else {
              field.classList.remove('at-limit');
              counter.classList.remove('at-limit');
            }
          };

          field.addEventListener('input', update);
          update();
        }

        setupCounter('rulesContent', 'rulesContentCount', 3000);
        setupCounter('aboutContent', 'aboutContentCount', 2000);
      })();
    })();
  </script>
</body>

</html>