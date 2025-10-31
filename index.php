<?php
require_once __DIR__ . '/includes/init.php';

function url_from_db(string $path): string
{
  return url($path);
}

$me = Auth::requireUserOrRedirect('./home_guest.php');

$paths = new Paths();

$profiles = new ProfileRepository();
$meRow = $profiles->getHeader($me);

$isAdmin = strtolower(trim($meRow['role'] ?? '')) === 'admin';

$meAvatarUrl = !empty($meRow['avatar_photo'])
  ? url_from_db($meRow['avatar_photo'])
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

$featuredRepo = new FeaturedRepository();
$hot = $featuredRepo->listForWeek();
$hotIds     = array_column($hot, 'pic_id');
$hotIdsSet  = array_fill_keys($hotIds, true);
$hotCount   = count($hotIds);

// $base = BASE_PATH;
// $cssFile = __DIR__ . '/public/css/main.css';
// $cssVer  = @filemtime($cssFile) ?: time();
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Home · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php
  $cssPath = __DIR__ . '/public/css/main.css';
  $ver = file_exists($cssPath) ? filemtime($cssPath) : time();
  ?>
  <link rel="stylesheet" href="./public/css/main.css?v=<?= $ver ?>">
</head>

<body>
  <?php if ($m = get_flash('ok')): ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
  <?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

  <div class="layout">
    <?php render_sidebar(['isAdmin' => $isAdmin]); ?>

    <main class="content">
      <div class="content-top">
        <form method="get" action="index.php" class="search-wrap">
          <?php if ($cat !== ''): ?>
            <input type="hidden" name="cat" value="<?= htmlspecialchars($cat) ?>">
          <?php endif; ?>
          <?php if ($sort !== ''): ?>
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
          <?php endif; ?>

          <input class="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search photos or username">
        </form>

        <a href="./profile.php" class="userbox" title="Go to my profile">
          <span class="avatar"
            title="<?= htmlspecialchars($meRow['display_name'] ?? 'You') ?>"
            style="background-image:url('<?= $meAvatarUrl ?>');"></span>
          <span class="username"><?= htmlspecialchars($meRow['display_name'] ?? 'You') ?></span>
        </a>
      </div>


      <div class="controls-row">
        <div class="pills">
          <?php
          // build helper for query strings
          $qs = function (array $params) {
            return http_build_query(array_filter($params, fn($v) => $v !== '' && $v !== null));
          };
          ?>

          <a class="pill<?= $cat === '' ? ' is-selected' : '' ?>"
            href="index.php?<?= $qs(['q' => $q, 'sort' => $sort]) ?>">All</a>

          <?php foreach ($cats as $c): ?>
            <?php
            $href = 'index.php?' . $qs([
              'cat'  => $c['slug'],
              'q'    => $q,
              'sort' => $sort,
            ]);
            ?>
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
              ? url_from_db($u['avatar_photo'])
              : 'https://placehold.co/56x56?text=%20';
          ?>
            <a class="person" href="profile.php?id=<?= (int)$u['profile_id'] ?>">
              <img src="<?= $avatar ?>" alt="">
              <span><?= htmlspecialchars($u['display_name']) ?></span>
            </a>
          <?php endforeach; ?>

        </div>
      <?php endif; ?>


      <!-- after your controls row, before the main feed -->
      <?php if (!empty($hot)): ?>
        <h2 class="section-title">🔥 Hot this week</h2>
        <div class="hot-row">
          <?php foreach ($hot as $p):
            $cover = img_from_db($p['pic_url']);

          ?>
            <a class="hot-card" href="picture.php?id=<?= (int)$p['pic_id'] ?>">
              <img src="<?= $cover ?>" alt="">
              <span class="hot-title"><?= htmlspecialchars($p['pic_title'] ?? 'Untitled') ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <section class="feed">
        <?php foreach ($pictures as $p): ?>
          <?php
          $coverUrl  = img_from_db($p['pic_url']);
          $avatarUrl = img_from_db($p['author_avatar']);
          ?>
          <article class="card">
            <img src="<?= $coverUrl ?>" alt="">

            <div class="card-body">
              <?php if ($isAdmin):
                $isHot = isset($hotIdsSet[$p['pic_id']]);
                $disablePin = (!$isHot && $hotCount >= 10);
              ?>
                <form method="post" action="./actions/toggle_feature.php" style="display:inline">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                  <input type="hidden" name="picture_id" value="<?= (int)$p['pic_id'] ?>">
                  <input type="hidden" name="return_to" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">

                  <?php if ($isHot): ?>
                    <input type="hidden" name="mode" value="unpin">
                    <button type="submit" class="pill" style="margin-top:6px;">🔥 Unpin</button>
                  <?php else: ?>
                    <button type="submit" class="pill" style="margin-top:6px;" <?= $disablePin ? 'disabled' : '' ?>>
                      📌 Pin to Hot
                    </button>
                  <?php endif; ?>
                </form>
              <?php endif; ?>

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