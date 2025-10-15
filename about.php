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

/* Detect if admin for sidebar */
$stmt = $conn->prepare("SELECT role FROM profiles WHERE profile_id=?");
$stmt->bind_param('i', $me);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$isAdmin = (isset($row['role']) && $row['role'] === 'admin');
$stmt->close();

/* Base URL for image paths */
$baseUrl       = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$publicUploads = $baseUrl . '/uploads/';

/* Load About content */
$stmt = $conn->prepare("SELECT title, content, image_path FROM pages WHERE slug='about' LIMIT 1");
$stmt->execute();
$page = $stmt->get_result()->fetch_assoc() ?: [
  'title' => 'About Picturesque',
  'content' => 'Welcome to Picturesque!',
  'image_path' => null
];
$stmt->close();
$conn->close();

$title = $page['title'];
$img   = !empty($page['image_path']) ? ($publicUploads . htmlspecialchars($page['image_path'])) : null;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title) ?> · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=12">
</head>
<body>
  <div class="layout">
    <?php render_sidebar(['isAdmin' => $isAdmin]); ?>

    <main class="content">
      <section class="about">
        <h1><?= htmlspecialchars($title) ?></h1>
        <p class="lead"><?= nl2br(htmlspecialchars($page['content'])) ?></p>
        <?php if ($img): ?>
          <img src="<?= $img ?>" alt="About" class="about-image">
        <?php endif; ?>
      </section>
    </main>
  </div>
</body>
</html>