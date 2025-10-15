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
$page = $stmt->get_result()->fetch_assoc() ?: ['title'=>'About','content'=>'','image_path'=>null];
$stmt->close();
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
  <div class="layout">
    <?php render_sidebar(['isAdmin' => true]); ?>
    <main class="content">
      <h1>Edit About Page</h1>

      <?php if ($m = get_flash('ok')): ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
      <?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

      <form method="post" action="./actions/admin_save_about.php" enctype="multipart/form-data" class="form">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

        <label class="label">Title</label>
        <input class="input" type="text" name="title" value="<?= htmlspecialchars($page['title']) ?>" required>

        <label class="label">Content (plain text)</label>
        <textarea class="textarea" name="content" rows="6" required><?= htmlspecialchars($page['content']) ?></textarea>

        <label class="label">Image (optional)</label>
        <?php if (!empty($page['image_path'])): ?>
          <div style="margin-bottom:.5rem">
            <img src="<?= $publicUploads . htmlspecialchars($page['image_path']) ?>" alt="" style="max-width:320px;border-radius:8px">
          </div>
        <?php endif; ?>
        <input class="input" type="file" name="image" accept="image/*">

        <div style="margin-top:1rem">
          <button class="btn-primary" type="submit">Save</button>
        </div>
      </form>
    </main>
  </div>
</body>
</html>