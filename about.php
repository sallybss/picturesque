<?php
require __DIR__ . '/includes/flash.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/sidebar.php';

if (empty($_SESSION['profile_id'])) { header('Location: ./auth/login.php'); exit; }

$me   = (int)$_SESSION['profile_id'];
$conn = db();

$stmt = $conn->prepare("SELECT role FROM profiles WHERE profile_id=?");
$stmt->bind_param('i', $me);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$isAdmin = (($row['role'] ?? '') === 'admin');
$stmt->close();

$baseUrl       = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$publicUploads = $baseUrl . '/uploads/';

$stmt = $conn->prepare("SELECT title, content, image_path FROM pages WHERE slug='about' LIMIT 1");
$stmt->execute();
$page = $stmt->get_result()->fetch_assoc() ?: ['title' => 'About Picturesque', 'content' => 'Welcome to Picturesque!', 'image_path' => null];
$stmt->close();
$conn->close();

$title = $page['title'];
$img   = !empty($page['image_path']) ? ($publicUploads . htmlspecialchars($page['image_path'])) : null;

$cssVer = file_exists(__DIR__ . '/public/css/main.css') ? filemtime(__DIR__ . '/public/css/main.css') : time();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title) ?> · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=<?= $cssVer ?>">
</head>
<body>
  <?php if ($m = get_flash('ok')):  ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
  <?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

  <div class="layout">
    <?php render_sidebar(['isAdmin' => $isAdmin]); ?>

    <main class="content">
      <section class="about">
        <h1><?= htmlspecialchars($title) ?></h1>
        <p class="lead"><?= nl2br(htmlspecialchars($page['content'])) ?></p>
        <?php if ($img): ?>
          <img src="<?= $img ?>" alt="About image" class="about-image">
        <?php endif; ?>
      </section>
    </main>
  </div>
</body>
</html>
