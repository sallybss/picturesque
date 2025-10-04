<?php
require __DIR__ . '/includes/flash.php';
require __DIR__ . '/includes/db.php';

if (empty($_SESSION['profile_id'])) { header('Location: ./auth/login.php'); exit; }

$me = (int)$_SESSION['profile_id'];
$conn = db();

// search (optional)
$q = trim($_GET['q'] ?? '');
$like = '%'.$q.'%';


$sql = "
  SELECT
    p.picture_id,
    p.profile_id,
    p.picture_title,
    p.picture_description,
    p.picture_url,
    p.created_at,
    pr.display_name,
    COALESCE(l.cnt,0)  AS like_count,
    COALESCE(c.cnt,0)  AS comment_count,
    CASE WHEN ml.like_id IS NULL THEN 0 ELSE 1 END AS liked_by_me
  FROM pictures p
  JOIN profiles pr ON pr.profile_id = p.profile_id
  LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM likes    GROUP BY picture_id) l ON l.picture_id = p.picture_id
  LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM comments GROUP BY picture_id) c ON c.picture_id = p.picture_id
  LEFT JOIN likes ml ON ml.picture_id = p.picture_id AND ml.profile_id = ?
";

$types = 'i';
$params = [$me];

if ($q !== '') {
  $sql .= " WHERE p.picture_title LIKE ? OR p.picture_description LIKE ? ";
  $types .= 'ss';
  $params[] = $like;
  $params[] = $like;
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$pictures = [];
while ($row = $res->fetch_assoc()) { $pictures[] = $row; }
$stmt->close();
$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Home · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=6">
</head>
<body>

<?php if ($m = get_flash('ok')): ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
<?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

<div class="layout">
  <!-- Sidebar -->
  <aside class="sidenav">
    <div class="brand">PICTURESQUE</div>

    <a class="create-btn" href="./create.php">☆ Create</a>

    <nav class="nav">
      <a href="./index.php">Home</a>
      <a href="./profile.php">My Profile</a>
      <div class="rule"></div>
      <a href="./auth/logout.php">↩︎ Logout</a>
    </nav>
  </aside>

  <!-- Content -->
  <main class="content">
    <div class="content-top">
      <form method="get" action="index.php" class="search-wrap">
        <input class="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search">
      </form>

      <div class="userbox">
        <span class="avatar" title="<?= htmlspecialchars($_SESSION['display_name'] ?? 'You') ?>"></span>
        <span class="username"><?= htmlspecialchars($_SESSION['display_name'] ?? 'You') ?></span>
      </div>
    </div>

    <div class="pills">
      <span class="pill">Discovery</span>
      <span class="pill">Abstract</span>
      <span class="pill">Sci-fi</span>
      <span class="pill">Landscape</span>
      <span class="pill">+</span>
    </div>   <!-- Good if we can make dynamic -->

    <section class="feed">
      <?php foreach ($pictures as $p): ?>
        <article class="card">
          <img src="uploads/<?= htmlspecialchars($p['picture_url']) ?>" alt="">
          <div class="card-body">
            <div class="author-row">
              <span class="mini-avatar"></span>
              <span class="author"><?= htmlspecialchars($p['display_name']) ?></span>
            </div>

            <div class="card-title"><?= htmlspecialchars($p['picture_title']) ?></div>

            <?php if (!empty($p['picture_description'])): ?>
              <div class="card-desc">
                <?= htmlspecialchars($p['picture_description']) ?>
              </div>
            <?php endif; ?>

            <div class="meta">
              <span class="counts">
                <form method="post" action="./actions/toggle_like.php" style="display:inline">
                  <input type="hidden" name="picture_id" value="<?= (int)$p['picture_id'] ?>">
                  <button type="submit" class="iconbtn">
                    <?php if ((int)$p['liked_by_me'] === 1): ?>❤<?php else: ?>♡<?php endif; ?>
                    <?= (int)$p['like_count'] ?>
                  </button>
                </form>
                <a class="muted" href="picture.php?id=<?= (int)$p['picture_id'] ?>">💬 <?= (int)$p['comment_count'] ?></a>
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