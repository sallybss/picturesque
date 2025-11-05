<?php
require_once __DIR__ . '/includes/init.php';

$me = Auth::requireUserOrRedirect('./auth/login.php');

$profileId = isset($_GET['id']) ? (int)$_GET['id'] : $me;
if ($profileId <= 0) {
  header('Location: ./auth/login.php');
  exit;
}

$paths    = new Paths();
$profiles = new ProfileRepository();

$meRow     = $profiles->getHeader($me);
$iAmAdmin  = (strtolower($meRow['role'] ?? 'user') === 'admin');

$viewRow = $profiles->getById($profileId);
if (!$viewRow) {
  header('Location: ./auth/login.php');
  exit;
}

$picturesRepo  = new PictureRepository();
$picturesCount = $picturesRepo->countByProfile($profileId);
$likesCount    = $picturesRepo->likesCountForProfilePictures($profileId);
$commentsCount = $picturesRepo->commentsCountForProfilePictures($profileId);
$myPics        = $picturesRepo->listByProfile($profileId);

$avatarSrc = !empty($viewRow['avatar_photo'])
  ? img_from_db($viewRow['avatar_photo'])
  : 'https://placehold.co/96x96?text=%20';

$coverSrc = !empty($viewRow['cover_photo'])
  ? img_from_db($viewRow['cover_photo'])
  : asset('images/default-cover.jpg');

$cssPath = __DIR__ . '/public/css/main.css';
$cssVer  = file_exists($cssPath) ? filemtime($cssPath) : time();
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($viewRow['display_name']) ?> · Profile</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=<?= $cssVer ?>">
</head>

<body>
  <?php if ($m = get_flash('ok')): ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
  <?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

  <button class="hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false">☰</button>

  <div class="layout">
    <?php render_sidebar(['isAdmin' => $iAmAdmin]); ?>

    <main class="content">
      <div class="profile-cover">
        <img src="<?= $coverSrc ?>" alt="Cover Photo" class="cover-image">

        <?php if ($me === $profileId): ?>
          <div class="cover-actions">
            <form method="post" action="./actions/update_cover.php" enctype="multipart/form-data">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
              <input id="coverInput" type="file" name="cover" accept="image/*" hidden onchange="this.form.submit()">
              <label for="coverInput" class="btn btn-ghost small">Change Cover</label>
            </form>
            <form method="post" action="./actions/update_cover.php">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
              <input type="hidden" name="action" value="reset">
              <button type="submit" class="btn btn-ghost small">Reset</button>
            </form>
          </div>
        <?php endif; ?>
      </div>

      <section class="profile-hero">
        <img class="profile-avatar" src="<?= $avatarSrc ?>" alt="">
        <div class="profile-info">
          <div class="profile-name"><?= htmlspecialchars($viewRow['display_name']) ?></div>
          <?php if (!empty($viewRow['email'])): ?>
            <div class="profile-email"><?= htmlspecialchars($viewRow['email']) ?></div>
          <?php endif; ?>
          <div class="profile-stats">
            <span><b><?= (int)$picturesCount ?></b> Posts</span>
            <span><b><?= (int)$likesCount ?></b> Likes</span>
            <span><b><?= (int)$commentsCount ?></b> Comments</span>
          </div>
          <?php if ($me === $profileId): ?>
            <div class="profile-actions" style="display:flex; gap:8px; flex-wrap:wrap">
              <a class="btn btn-primary" href="./profile_edit.php">Edit Profile</a>
              <a class="btn btn-ghost" href="./profile_settings.php">Profile Settings</a>
            </div>
          <?php endif; ?>
        </div>
      </section>

      <h2 class="section-title"><?= ($me === $profileId ? 'My Photos' : 'Photos') ?></h2>
      <section class="feed">
        <?php foreach ($myPics as $p): ?>
          <?php $cardImg = img_from_db($p['picture_url'] ?? $p['pic_url'] ?? ''); ?>
          <article class="card">
            <img src="<?= $cardImg ?>" alt="">
            <div class="card-body">
              <div class="card-title"><?= htmlspecialchars($p['picture_title']) ?></div>
              <?php if (!empty($p['picture_description'])): ?>
                <div class="card-desc"><?= htmlspecialchars($p['picture_description']) ?></div>
              <?php endif; ?>
              <div class="meta">
                <span class="counts">
                  <span class="muted">❤ <?= (int)$p['like_count'] ?></span>
                  <a class="muted" href="picture.php?id=<?= (int)$p['picture_id'] ?>">💬 <?= (int)$p['comment_count'] ?></a>
                </span>
                <span class="spacer"></span>
                <?php if ($me === $profileId): ?>
                  <a class="btn btn-ghost" href="./edit_picture.php?id=<?= (int)$p['picture_id'] ?>">Edit</a>
                  <form method="post" action="./actions/delete_picture.php" onsubmit="return confirm('Delete this photo? This cannot be undone.');" style="display:inline">
                    <input type="hidden" name="picture_id" value="<?= (int)$p['picture_id'] ?>">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <button type="submit" class="btn btn-danger">Delete</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>

        <?php if (!$myPics): ?>
          <p class="muted"><?= ($me === $profileId ? "You haven’t posted yet. Click " : "No photos yet.") ?><b><?= ($me === $profileId ? "Create" : "") ?></b></p>
        <?php endif; ?>
      </section>
    </main>
  </div>

  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

  <script>
    (function() {
      const body = document.body;
      const btn = document.getElementById('hamburger');
      const backdrop = document.getElementById('sidebarBackdrop');

      function openMenu() {
        body.classList.add('sidebar-open');
        btn && btn.setAttribute('aria-expanded', 'true');
      }

      function closeMenu() {
        body.classList.remove('sidebar-open');
        btn && btn.setAttribute('aria-expanded', 'false');
      }

      function toggle() {
        body.classList.contains('sidebar-open') ? closeMenu() : openMenu();
      }
      btn && btn.addEventListener('click', toggle);
      backdrop && backdrop.addEventListener('click', closeMenu);
      document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeMenu();
      });
    })();
  </script>
</body>

</html>