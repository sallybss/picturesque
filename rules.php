<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/pages_repository.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/profile_repository.php';
require_once __DIR__ . '/includes/topbar.php';

$pages = new PagesRepository();
$rules = $pages->getBySlug('rules');

$title   = $rules['title']   ?? 'Rules & Regulations';
$content = $rules['content'] ?? 'No rules have been published yet.';

$isLoggedIn = isset($_SESSION['profile_id']) && (int)$_SESSION['profile_id'] > 0;
$isAdmin = false;
if ($isLoggedIn) {
  $profiles = new ProfileRepository();
  $meRow = $profiles->getHeader((int)$_SESSION['profile_id']);
  $isAdmin = (strtolower($meRow['role'] ?? '') === 'admin');
}

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
  <div class="layout">
    <?php render_sidebar(['isAdmin' => $isAdmin, 'isGuest' => !$isLoggedIn]); ?>
    <main class="content">


    <?php if ($isLoggedIn && isset($meRow)): ?>
  <?php render_topbar_userbox($meRow); ?>
<?php endif; ?>


      <section class="pad">
        <h1 class="page-title"><?= htmlspecialchars($title) ?></h1>
        <div class="prose">
          <?= nl2br(htmlspecialchars($content)) ?>
        </div>
      </section>
    </main>
  </div>
</body>
</html>