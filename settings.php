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

$MAX_CATEGORIES = 10;
$catsRepo       = new CategoriesRepository();  

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

    if ($newTitle === '' || $rawRules === '') {
      set_flash('err', 'Rules title and content are required.');
      header("Location: {$basePath}#rules");
      exit;
    }

    $newTitle   = mb_substr($newTitle, 0, 80);
    $newContent = mb_substr($rawRules, 0, 3000);

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

    $currentCount = $catsRepo->countAll();
    if ($currentCount >= $MAX_CATEGORIES) {
      set_flash('err', "You can have at most {$MAX_CATEGORIES} categories.");
      header("Location: {$basePath}#cats");
      exit;
    }

    $slug = slugify($name);

    $ok = $catsRepo->create($name, $slug);
    if ($ok) {
      set_flash('ok', 'Category added.');
    } else {
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

    $catsRepo->toggleActive($id);

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

    $ok = $catsRepo->delete($id);
    if ($ok) {
      set_flash('ok', 'Category deleted.');
    } else {
      set_flash('err', 'Could not delete category.');
    }

    header("Location: {$basePath}#cats");
    exit;
  }

  if ($action === 'save_about') {
    $newTitle   = trim($_POST['title']   ?? '');
    $newContent = trim($_POST['content'] ?? '');

    if ($newTitle === '' || $newContent === '') {
      set_flash('err', 'Title and content are required.');
      header("Location: {$basePath}#about");
      exit;
    }

    $newTitle   = mb_substr($newTitle,   0, 80);
    $newContent = mb_substr($newContent, 0, 3000);

    $resetImg     = !empty($_POST['reset_image']);
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

      $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
      $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
      if (!in_array($ext, $allowedExt, true)) {
        set_flash('err', 'Upload JPG, PNG, WEBP or GIF.');
        header("Location: {$basePath}#about");
        exit;
      }

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

$cats = $catsRepo->listWithPicCount();

$catLimitReached = (count($cats) >= $MAX_CATEGORIES);

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
                  <?= $catLimitReached ? 'disabled' : '' ?>
                  required>

                <button
                  type="submit"
                  class="btn-primary cat-add-btn"
                  <?= $catLimitReached ? 'disabled' : '' ?>>
                  Add
                </button>
              </div>

              <small class="help">
                Max 40 characters. Keep names short and descriptive.
                <?php if ($catLimitReached): ?>
                  <br>You reached the limit of <?= $MAX_CATEGORIES ?> categories.
                  Delete one if you need to add another.
                <?php endif; ?>
              </small>

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

                    <form method="post" action="<?= htmlspecialchars($basePath) ?>#cats" style="display:inline;" onsubmit="return confirm('Delete this category? Pictures keeping this category will lose it.');">
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
                <span class="field-counter" id="aboutContentCount">0 / 3000</span>
              </div>
              <textarea
                id="aboutContent"
                class="input textarea"
                name="content"
                rows="8"
                maxlength="3000"
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

  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

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
        setupCounter('aboutContent', 'aboutContentCount', 3000);
      })();
    })();
    (function() {
      const body = document.body;
      const THEME_KEY = 'pq_theme';
      const FONT_KEY = 'pq_font';

      function applyTheme(theme) {
        body.classList.remove('theme-light', 'theme-dark');
        body.classList.add('theme-' + theme);
      }

      function applyFont(size) {
        body.classList.remove('font-small', 'font-medium', 'font-large');
        body.classList.add('font-' + size);
      }

      const savedTheme = localStorage.getItem(THEME_KEY) || 'light';
      const savedFont = localStorage.getItem(FONT_KEY) || 'medium';

      applyTheme(savedTheme);
      applyFont(savedFont);
    })();

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
      const savedFont = localStorage.getItem(FONT_KEY) || 'medium';
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