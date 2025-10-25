<?php
require_once __DIR__ . '/includes/init.php';

$me = Auth::requireUserOrRedirect('./home_guest.php');

$paths = new Paths();

$profiles = new ProfileRepository();
$meRow = $profiles->getHeader($me);

$isAdmin = strtolower(trim($meRow['role'] ?? '')) === 'admin';

$meAvatarUrl = !empty($meRow['avatar_photo'])
  ? $paths->uploads . htmlspecialchars($meRow['avatar_photo'])
  : 'https://placehold.co/28x28?text=%20';

$q = trim($_GET['q'] ?? '');

$cat = trim($_GET['cat'] ?? '');
$sort = $_GET['sort'] ?? 'new';
if (!in_array($sort, ['new', 'old'], true)) {
  $sort = 'new';
}

require __DIR__ . '/includes/categories_repository.php';
$catsRepo = new CategoriesRepository();
$cats = $catsRepo->listActive();

$search = new SearchRepository();
$people = $q !== '' ? $search->peopleByDisplayNameLike('%' . ltrim($q, '@') . '%') : [];

$picturesRepo = new PictureRepository();
$pictures = $picturesRepo->feed($me, $q, $cat, $sort);

$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$cssPath = __DIR__ . '/public/css/main.css';   
$cssVer  = file_exists($cssPath) ? filemtime($cssPath) : time();

$prefix = (strpos($_SERVER['REQUEST_URI'], '/mada/') === 0) ? '/mada' : '';
?>


<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Home · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="<?= $prefix ?>/public/css/main.css?v=<?= time() ?>">
<link rel="stylesheet" href="/mada/public/css/filter.css?v=<?= time() ?>">


</head>

<body>

  <?php if ($m = get_flash('ok')): ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
  <?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

  <div class="layout">
    <?php render_sidebar(['isAdmin' => $isAdmin]); ?>

    <main class="content">
      <div class="content-top">
        <a href="./profile.php" class="userbox" title="Go to my profile">
          <span class="avatar"
            title="<?= htmlspecialchars($meRow['display_name'] ?? 'You') ?>"
            style="background-image:url('<?= $meAvatarUrl ?>');"></span>
          <span class="username"><?= htmlspecialchars($meRow['display_name'] ?? 'You') ?></span>
        </a>
      </div>

      <div class="controls-row">
        <div class="pills">
          <a class="pill<?= $cat === '' ? ' is-selected' : '' ?>" href="index.php<?= $q !== '' ? ('?q=' . urlencode($q)) : '' ?>">All</a>
          <?php foreach ($cats as $c): ?>
            <?php $href = 'index.php?cat=' . urlencode($c['slug']) . ($q !== '' ? '&q=' . urlencode($q) : ''); ?>
            <a class="pill<?= $cat === $c['slug'] ? ' is-selected' : '' ?>" href="<?= $href ?>">
              <?= htmlspecialchars($c['name']) ?>
            </a>
          <?php endforeach; ?>
        </div>

        <!-- Clean dropdown -->
        <form class="filter-form" method="get" action="index.php">
          <?php if ($q !== ''): ?>
            <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
          <?php endif; ?>
          <?php if ($cat !== ''): ?>
            <input type="hidden" name="cat" value="<?= htmlspecialchars($cat) ?>">
          <?php endif; ?>

          <label for="sort" class="filter-label">Sort by:</label>
          <div class="filter-select-wrap">
            <select name="sort" id="sort" class="filter-select" onchange="this.form.submit()">
              <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Newest → Oldest</option>
              <option value="old" <?= $sort === 'old' ? 'selected' : '' ?>>Oldest → Newest</option>
            </select>
          </div>
        </form>
      </div>


      <?php if ($q !== '' && !empty($people)): ?>
        <h2 class="section-title">People</h2>
        <div class="people-row">
          <?php foreach ($people as $u):
            $avatar = !empty($u['avatar_photo'])
              ? $paths->uploads . htmlspecialchars($u['avatar_photo'])
              : 'https://placehold.co/56x56?text=%20';
          ?>
            <a class="person" href="profile.php?id=<?= (int)$u['profile_id'] ?>">
              <img src="<?= $avatar ?>" alt="">
              <span><?= htmlspecialchars($u['display_name']) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <section class="feed">
        <?php foreach ($pictures as $p): ?>
          <?php
          $coverUrl = !empty($p['pic_url'])
            ? $paths->uploads . htmlspecialchars($p['pic_url'])
            : './public/img/placeholder-photo.jpg';

          $avatarUrl = !empty($p['author_avatar'])
            ? $paths->uploads . htmlspecialchars($p['author_avatar'])
            : 'https://placehold.co/24x24?text=%20';
          ?>
          <article class="card">
            <img src="<?= $coverUrl ?>" alt="">

            <div class="card-body">
              <a class="author-row" href="profile.php?id=<?= (int)$p['pic_profile_id'] ?>" style="text-decoration:none; color:inherit;">
                <img class="mini-avatar" src="<?= $avatarUrl ?>" alt="<?= htmlspecialchars($p['author_name']) ?> avatar">
                <span class="author"><?= htmlspecialchars($p['author_name']) ?></span>
              </a>

              <div class="card-title"><?= htmlspecialchars($p['pic_title']) ?></div>

              <?php if (!empty($p['pic_desc'])): ?>
                <div class="card-desc"><?= htmlspecialchars($p['pic_desc']) ?></div>
              <?php endif; ?>

              <div class="meta">
                <span class="counts">
                  <form method="post" action="./actions/toggle_like.php" style="display:inline">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="picture_id" value="<?= (int)$p['pic_id'] ?>">
                    <button type="submit" class="iconbtn">
                      <?= ((int)$p['liked_by_me'] === 1) ? '❤' : '♡' ?> <?= (int)$p['like_count'] ?>
                    </button>
                  </form>

                  <a class="muted" href="picture.php?id=<?= (int)$p['pic_id'] ?>">💬 <?= (int)$p['comment_count'] ?></a>
                </span>
              </div>
            </div>
          </article>
        <?php endforeach; ?>

        <?php if (!$pictures): ?>
          <p class="muted">No pictures yet. Click <b>Create</b> to upload your first photo.</p>
        <?php endif; ?>
      </section>

    </main>
  </div>
</body>

</html>