<?php
require_once __DIR__ . '/includes/init.php';

$paths = new Paths();
$publicUploads = $paths->uploads;

$sql = "
  SELECT
    p.picture_id,
    p.profile_id,
    p.picture_title,
    p.picture_description,
    p.picture_url,
    p.created_at,
    pr.display_name,
    pr.avatar_photo,
    COALESCE(l.cnt,0) AS like_count,
    COALESCE(c.cnt,0) AS comment_count
  FROM pictures p
  JOIN profiles pr ON pr.profile_id = p.profile_id
  LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM likes    GROUP BY picture_id) l ON l.picture_id = p.picture_id
  LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM comments GROUP BY picture_id) c ON c.picture_id = p.picture_id
  ORDER BY p.created_at DESC
  LIMIT 120
";

$res = DB::get()->query($sql);
$pictures = [];
while ($row = $res->fetch_assoc()) $pictures[] = $row;

$cssPath = __DIR__ . '/public/css/main.css';
$ver = file_exists($cssPath) ? filemtime($cssPath) : time();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Discover · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=<?= $ver ?>">
</head>
<body class="guest-locked">


<?php if ($m = get_flash('ok')): ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
<?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

<div class="guest-main">
  <?php
    render_sidebar([
      'isAdmin'   => false,
      'isGuest'   => true,
      'homeCount' => count($pictures),
    ]);
  ?>

  <main class="guest-content">
    <div class="content-top">
      <form method="get" action="home_guest.php" class="search-wrap">
        <input class="search" name="q" placeholder="Search" disabled title="Sign in to use search">
      </form>
      <a class="btn-ghost pill" href="./auth/login.php" style="text-decoration:none">Sign in</a>
    </div>

    <div class="controls-row">
      <div class="pills">
        <span class="pill">Discovery</span>
        <span class="pill">Abstract</span>
        <span class="pill">Sci-fi</span>
        <span class="pill">Landscape</span>
        <span class="pill">+</span>
      </div>
      <button class="filter-btn" type="button" disabled title="Sign in to filter">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 6h18M6 12h12M10 18h4" stroke-width="2" stroke-linecap="round"/></svg>
        Filter
        <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m6 9 6 6 6-6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
    </div>

    <section class="feed feed-locked">
      <?php foreach ($pictures as $p): ?>
        <article class="card">
          <img src="<?= $publicUploads . htmlspecialchars($p['picture_url']) ?>" alt="">
          <div class="card-body">
            <div class="author-row">
              <?php
                $avatarUrl = !empty($p['avatar_photo'])
                  ? $publicUploads . htmlspecialchars($p['avatar_photo'])
                  : 'https://placehold.co/24x24?text=%20';
              ?>
              <img class="mini-avatar" src="<?= $avatarUrl ?>" alt="<?= htmlspecialchars($p['display_name']) ?> avatar">
              <span class="author"><?= htmlspecialchars($p['display_name']) ?></span>
            </div>
            <div class="card-title"><?= htmlspecialchars($p['picture_title']) ?></div>
            <?php if (!empty($p['picture_description'])): ?>
              <div class="card-desc"><?= htmlspecialchars($p['picture_description']) ?></div>
            <?php endif; ?>
            <div class="meta">
              <span class="counts">
                <span class="muted" title="Sign in to like">♡ <?= (int)$p['like_count'] ?></span>
                <span class="muted" title="Sign in to comment">💬 <?= (int)$p['comment_count'] ?></span>
              </span>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </section>

    <div class="guest-cta">
      <span class="note-guest">To have full access to the gallery, please log in or create an account.</span>
      <a class="btn-ghost" href="./auth/register.php">Register</a>
      <a class="btn-primary" href="./auth/login.php">Sign in</a>
    </div>
  </main>
</div>
</body>
</html>
