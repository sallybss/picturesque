<?php
require __DIR__ . '/includes/flash.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/sidebar.php';

if (empty($_SESSION['profile_id'])) {
  header('Location: ./home_guest.php');
  exit;
}

$me  = (int)$_SESSION['profile_id'];
$conn = db();

$baseUrl       = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$publicUploads = $baseUrl . '/uploads/';

$stmtMe = $conn->prepare('SELECT display_name, avatar_photo, role FROM profiles WHERE profile_id = ?');
$stmtMe->bind_param('i', $me);
$stmtMe->execute();
$meRow = $stmtMe->get_result()->fetch_assoc();
$stmtMe->close();

$isAdmin = strtolower(trim($meRow['role'] ?? '')) === 'admin';

$meAvatarUrl = !empty($meRow['avatar_photo'])
  ? $publicUploads . htmlspecialchars($meRow['avatar_photo'])
  : 'https://placehold.co/28x28?text=%20';

$q        = trim($_GET['q'] ?? '');
$qUser    = ltrim($q, '@');
$like     = '%' . $q . '%';
$likeUser = '%' . $qUser . '%';

$people = [];
if ($q !== '') {
  $stmtPeople = $conn->prepare('
    SELECT profile_id, display_name, avatar_photo
    FROM profiles
    WHERE display_name LIKE ?
    ORDER BY display_name
    LIMIT 20
  ');
  $stmtPeople->bind_param('s', $likeUser);
  $stmtPeople->execute();
  $people = $stmtPeople->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmtPeople->close();
}

if ($q === '') {
  $sql = "
    SELECT
      p.picture_id              AS pic_id,
      p.profile_id              AS pic_profile_id,
      p.picture_title           AS pic_title,
      p.picture_description     AS pic_desc,
      p.picture_url             AS pic_url,
      p.created_at              AS pic_created_at,
      pr.display_name           AS author_name,
      pr.avatar_photo           AS author_avatar,
      COALESCE(l.cnt, 0)        AS like_count,
      COALESCE(c.cnt, 0)        AS comment_count,
      CASE WHEN ml.like_id IS NULL THEN 0 ELSE 1 END AS liked_by_me
    FROM pictures p
    JOIN profiles pr ON pr.profile_id = p.profile_id
    LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM likes    GROUP BY picture_id) l ON l.picture_id = p.picture_id
    LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM comments GROUP BY picture_id) c ON c.picture_id = p.picture_id
    LEFT JOIN likes ml ON ml.picture_id = p.picture_id AND ml.profile_id = ?
    ORDER BY p.created_at DESC
  ";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('i', $me);

} else {
  $sql = "
    SELECT
      p.picture_id              AS pic_id,
      p.profile_id              AS pic_profile_id,
      p.picture_title           AS pic_title,
      p.picture_description     AS pic_desc,
      p.picture_url             AS pic_url,
      p.created_at              AS pic_created_at,
      pr.display_name           AS author_name,
      pr.avatar_photo           AS author_avatar,
      COALESCE(l.cnt, 0)        AS like_count,
      COALESCE(c.cnt, 0)        AS comment_count,
      CASE WHEN ml.like_id IS NULL THEN 0 ELSE 1 END AS liked_by_me
    FROM pictures p
    JOIN profiles pr ON pr.profile_id = p.profile_id
    LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM likes    GROUP BY picture_id) l ON l.picture_id = p.picture_id
    LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM comments GROUP BY picture_id) c ON c.picture_id = p.picture_id
    LEFT JOIN likes ml ON ml.picture_id = p.picture_id AND ml.profile_id = ?
    WHERE (p.picture_title LIKE ? OR p.picture_description LIKE ? OR pr.display_name LIKE ?)
    ORDER BY p.created_at DESC
  ";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('isss', $me, $like, $like, $likeUser);
}

$stmt->execute();
$pictures = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$cssPath = __DIR__ . '/public/css/main.css';
$ver = file_exists($cssPath) ? filemtime($cssPath) : time();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Home · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=12">
</head>
<body>

<?php if ($m = get_flash('ok')): ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
<?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

<div class="layout">
  <?php render_sidebar(['isAdmin' => $isAdmin]); ?>

  <main class="content">
    <div class="content-top">
      <form method="get" action="index.php" class="search-wrap">
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
        <span class="pill">Discovery</span>
        <span class="pill">Abstract</span>
        <span class="pill">Sci-fi</span>
        <span class="pill">Landscape</span>
        <span class="pill">+</span>
      </div>
      <button class="filter-btn" type="button">Filter</button>
    </div>

    <?php if ($q !== '' && !empty($people)): ?>
      <h2 class="section-title">People</h2>
      <div class="people-row">
        <?php foreach ($people as $u):
          $avatar = !empty($u['avatar_photo'])
            ? $publicUploads . htmlspecialchars($u['avatar_photo'])
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
      // Main cover image (the actual picture)
      $coverUrl = !empty($p['pic_url'])
        ? $publicUploads . htmlspecialchars($p['pic_url'])
        : './public/img/placeholder-photo.jpg'; // optional fallback image

      // Small author avatar
      $avatarUrl = !empty($p['author_avatar'])
        ? $publicUploads . htmlspecialchars($p['author_avatar'])
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
