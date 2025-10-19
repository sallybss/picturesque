<?php
require __DIR__ . '/includes/flash.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/admin_guard.php';
require __DIR__ . '/includes/sidebar.php';

if (empty($_SESSION['profile_id'])) { header('Location: ./auth/login.php'); exit; }

$me   = (int)$_SESSION['profile_id'];
$conn = db();
require_admin($conn, $me);

$baseUrl       = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$publicUploads = $baseUrl . '/uploads/';

$stmt = $conn->prepare("SELECT title, content, image_path FROM pages WHERE slug='about' LIMIT 1");
$stmt->execute();
$page = $stmt->get_result()->fetch_assoc() ?: ['title' => 'About', 'content' => '', 'image_path' => null];
$stmt->close();
$conn->close();
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
            <input class="input" type="text" name="title" value="<?= htmlspecialchars($page['title']) ?>" required>
          </div>

          <div class="form-row">
            <label class="label">Content</label>
            <textarea class="input textarea" name="content" rows="6" required><?= htmlspecialchars($page['content']) ?></textarea>
          </div>

          <div class="form-row">
            <label class="label">Image (optional)</label>
            <?php if (!empty($page['image_path'])): ?>
              <div class="image-preview-wrapper" style="margin-bottom:10px">
                <img class="preview" src="<?= $publicUploads . htmlspecialchars($page['image_path']) ?>" alt="About image">
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
