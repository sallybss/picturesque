<?php
require __DIR__ . '/includes/flash.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/sidebar.php';

if (empty($_SESSION['profile_id'])) {
  header('Location: ./auth/login.php');
  exit;
}

$me   = (int)$_SESSION['profile_id'];
$conn = db();

/* Check if admin (no fetch_column to support older PHP) */
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

/* Handle save (title, content, optional file upload) */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title   = trim($_POST['title'] ?? '');
  $content = trim($_POST['content'] ?? '');

  if ($title === '' || $content === '') {
    set_flash('err', 'Title and content are required.');
    header('Location: settings.php');
    exit;
  }

  $imagePathToSave = null;
  if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
    $file = $_FILES['image'];

    // basic validation
    if ($file['error'] !== UPLOAD_ERR_OK) {
      set_flash('err', 'Upload failed.');
      header('Location: settings.php'); exit;
    }

    // Verify it's an image (safe for PHP 7)
    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
      set_flash('err', 'The uploaded file is not a valid image.');
      header('Location: settings.php'); exit;
    }

    // Limit size to ~5MB (adjust if you want)
    if ($file['size'] > 5 * 1024 * 1024) {
      set_flash('err', 'Image too large (max 5MB).');
      header('Location: settings.php'); exit;
    }

    // ensure uploads/pages exists
    $dir = __DIR__ . '/uploads/pages';
    if (!is_dir($dir)) { mkdir($dir, 0777, true); }

    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $name = 'about_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
      set_flash('err', 'Could not save the uploaded image.');
      header('Location: settings.php'); exit;
    }

    // store relative path under /uploads
    $imagePathToSave = 'pages/' . $name;
  }

  // Upsert About row; update image only if a new one was uploaded
  if ($imagePathToSave) {
    $stmt = $conn->prepare("
      INSERT INTO pages (slug, title, content, image_path, updated_by)
      VALUES ('about', ?, ?, ?, ?)
      ON DUPLICATE KEY UPDATE
        title=VALUES(title),
        content=VALUES(content),
        image_path=VALUES(image_path),
        updated_by=VALUES(updated_by),
        updated_at=NOW()
    ");
    $stmt->bind_param('sssi', $title, $content, $imagePathToSave, $me);
  } else {
    $stmt = $conn->prepare("
      INSERT INTO pages (slug, title, content, updated_by)
      VALUES ('about', ?, ?, ?)
      ON DUPLICATE KEY UPDATE
        title=VALUES(title),
        content=VALUES(content),
        updated_by=VALUES(updated_by),
        updated_at=NOW()
    ");
    $stmt->bind_param('ssi', $title, $content, $me);
  }
  $stmt->execute();
  $stmt->close();

  set_flash('ok', 'About page updated successfully.');
  header('Location: settings.php');
  exit;
}

/* Load current About content for the form */
$stmt = $conn->prepare("SELECT title, content, image_path FROM pages WHERE slug='about' LIMIT 1");
$stmt->execute();
$page = $stmt->get_result()->fetch_assoc() ?: [
  'title'      => 'About Picturesque',
  'content'    => '',
  'image_path' => ''
];
$stmt->close();
$conn->close();

$baseUrl       = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$publicUploads = $baseUrl . '/uploads/';
$currentImage  = $page['image_path'] ? $publicUploads . htmlspecialchars($page['image_path']) : '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Settings · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=13">
</head>
<body>
  <?php if ($m = get_flash('ok')): ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
  <?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

  <div class="layout">
    <?php render_sidebar(['isAdmin' => $isAdmin]); ?>

    <main class="content">
      <h1>Admin Settings</h1>
      <p>Edit the content of the About page below.</p>

      <form method="post" enctype="multipart/form-data" class="form-stacked" style="max-width: 640px;">
        <label>Title</label>
        <input type="text" name="title" value="<?= htmlspecialchars($page['title']) ?>" required>

        <label>Content</label>
        <textarea name="content" rows="8" required><?= htmlspecialchars($page['content']) ?></textarea>

        <label>Upload Image (optional)</label>
        <?php if ($currentImage): ?>
          <div style="margin-bottom:.5rem">
            <img src="<?= $currentImage ?>" alt="About image" style="max-width:320px;border-radius:8px">
          </div>
        <?php endif; ?>
        <input type="file" name="image" accept="image/*">

        <button type="submit" class="btn-primary" style="margin-top:1rem">Save Changes</button>
      </form>
    </main>
  </div>
</body>
</html>