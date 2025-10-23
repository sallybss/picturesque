<?php
require_once __DIR__ . '/includes/init.php';

$me = Auth::requireAdminOrRedirect('./index.php');


$paths = new Paths();

$profiles = new ProfileRepository();
$meRow = $profiles->getHeader($me);
$isAdmin = (($meRow['role'] ?? 'user') === 'admin');
if (!$isAdmin) { header('Location: ./index.php'); exit; }

$pages = new PagesRepository();
$page  = $pages->getAbout();
$title = $page['title'] ?? 'About';
$content = $page['content'] ?? '';
$imagePath = $page['image_path'] ?? null;

$publicUploads = $paths->uploads;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Edit About · Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=10">
</head>
<body>
  <?php if ($m = get_flash('ok')):  ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
  <?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

  <div class="layout">
    <?php render_sidebar(['isAdmin' => true]); ?>

    <main class="content">
      <h1 class="page-title">Edit About Page</h1>

      <div class="form-card">
        <form method="post" action="./actions/admin_save_about.php" enctype="multipart/form-data" autocomplete="off">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

          <div class="form-row">
            <label class="label">Title</label>
            <input class="input" type="text" name="title" value="<?= htmlspecialchars($title) ?>" required>
          </div>

          <div class="form-row">
            <label class="label">Content</label>
            <textarea class="input textarea" name="content" rows="6" required><?= htmlspecialchars($content) ?></textarea>
          </div>

          <div class="form-row">
            <label class="label">Image (optional)</label>
            <?php if (!empty($imagePath)): ?>
              <div class="image-preview-wrapper" style="margin-bottom:10px">
                <img class="preview" src="<?= $publicUploads . htmlspecialchars($imagePath) ?>" alt="About image">
              </div>
            <?php endif; ?>
            <input class="file-input-vis" type="file" name="image" accept="image/*">
          </div>

          <div class="profile-actions-row">
            <button class="btn-primary" type="submit">Save</button>
            <a class="btn-ghost" href="./admin.php">Cancel</a>
          </div>
        </form>
      </div>
    </main>
  </div>
</body>
</html>
