<?php
require __DIR__ . '/includes/flash.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/sidebar.php';

if (empty($_SESSION['profile_id'])) {
  header('Location: ./auth/login.php');
  exit;
}

$me = (int)$_SESSION['profile_id'];
$conn = db();

/* Check if admin */
$stmt = $conn->prepare("SELECT role FROM profiles WHERE profile_id=?");
$stmt->bind_param('i', $me);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$isAdmin = (isset($row['role']) && $row['role'] === 'admin');
$stmt->close();

if (!$isAdmin) {
  http_response_code(403);
  echo "Forbidden: Admins only.";
  exit;
}

/* Handle update */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $content = trim($_POST['content'] ?? '');
  $imagePath = trim($_POST['image_path'] ?? '');

  $stmt = $conn->prepare("
    UPDATE pages 
    SET title=?, content=?, image_path=?, updated_by=?, updated_at=NOW()
    WHERE slug='about'
  ");
  $stmt->bind_param('sssi', $title, $content, $imagePath, $me);
  $stmt->execute();
  $stmt->close();

  set_flash('ok', 'About page updated successfully.');
  header('Location: settings.php');
  exit;
}

/* Load About content */
$stmt = $conn->prepare("SELECT title, content, image_path FROM pages WHERE slug='about' LIMIT 1");
$stmt->execute();
$page = $stmt->get_result()->fetch_assoc() ?: [
  'title' => 'About Picturesque',
  'content' => 'Welcome to Picturesque!',
  'image_path' => ''
];
$stmt->close();
$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Settings · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=12">
</head>
<body>
  <?php if ($m = get_flash('ok')): ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
  <?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

  <div class="layout">
    <?php render_sidebar(['isAdmin' => $isAdmin]); ?>

    <main class="content">
      <h1>Admin Settings</h1>
      <p>Edit the content of the About page below.</p>

      <form method="post" class="form-stacked" style="max-width: 600px;">
        <label>Title</label>
        <input type="text" name="title" value="<?= htmlspecialchars($page['title']) ?>" required>

        <label>Content</label>
        <textarea name="content" rows="8" required><?= htmlspecialchars($page['content']) ?></textarea>

        <label>Image Path (optional)</label>
        <input type="text" name="image_path" value="<?= htmlspecialchars($page['image_path']) ?>">

        <button type="submit" class="btn-primary">Save Changes</button>
      </form>
    </main>
  </div>
</body>
</html>