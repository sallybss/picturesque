<?php
require __DIR__ . '/includes/flash.php';
require __DIR__ . '/includes/sidebar.php';
require __DIR__ . '/includes/db_class.php';
require __DIR__ . '/includes/auth_class.php';
require __DIR__ . '/includes/paths_class.php';
require __DIR__ . '/includes/profile_repository.php';
require __DIR__ . '/includes/pages_repository.php';

$me = Auth::requireUserOrRedirect('./auth/login.php');

$paths = new Paths();

$profiles = new ProfileRepository();
$meRow = $profiles->getHeader($me);
$isAdmin = (($meRow['role'] ?? 'user') === 'admin');

$pages = new PagesRepository();
$page = $pages->getAbout() ?: ['title' => 'About Picturesque', 'content' => 'Welcome to Picturesque!', 'image_path' => null];

$title = $page['title'] ?? 'About Picturesque';
$img = !empty($page['image_path']) ? ($paths->uploads . htmlspecialchars($page['image_path'])) : null;

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
        <p class="lead"><?= nl2br(htmlspecialchars($page['content'] ?? '')) ?></p>
        <?php if ($img): ?>
          <img src="<?= $img ?>" alt="About image" class="about-image">
        <?php endif; ?>
      </section>
    </main>
  </div>
</body>
</html>
