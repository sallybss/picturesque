<?php
require __DIR__ . '/includes/flash.php';
require __DIR__ . '/includes/db.php';

if (empty($_SESSION['profile_id'])) { header('Location: ./auth/login.php'); exit; }

$conn = db();

// search (optional)
$q = trim($_GET['q'] ?? '');
$params = [];
$sql = "
  SELECT
    p.picture_id, p.profile_id, p.picture_title, p.picture_description, p.picture_url, p.created_at,
    pr.display_name,
    COALESCE(l.cnt,0) AS like_count,
    COALESCE(c.cnt,0) AS comment_count
  FROM pictures p
  JOIN profiles pr ON pr.profile_id = p.profile_id
  LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM likes GROUP BY picture_id) l ON l.picture_id = p.picture_id
  LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM comments GROUP BY picture_id) c ON c.picture_id = p.picture_id
";
if ($q !== '') {
  $sql .= " WHERE p.picture_title LIKE ? OR p.picture_description LIKE ? ";
  $like = '%'.$q.'%';
  $params = [$like, $like];
}
$sql .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) { $stmt->bind_param('ss', ...$params); }
$stmt->execute();
$res = $stmt->get_result();
$pictures = [];
while ($row = $res->fetch_assoc()) { $pictures[] = $row; }
$stmt->close(); $conn->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Home · Picturesque</title>
  <link rel="stylesheet" href="./public/css/main.css">
</head>
<body>

<!-- Header -->
<div class="header">
  <div class="brand">PICTURESQUE</div>
  <div class="header-right">
    <form method="get" action="index.php">
      <input class="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search">
    </form>
    <a href="./create.php"><button class="cta" type="button">Create</button></a>
    <span class="avatar" title="<?= htmlspecialchars($_SESSION['display_name'] ?? 'You') ?>"></span>
    <span><?= htmlspecialchars($_SESSION['display_name'] ?? 'You') ?></span>
    <a class="link" href="./auth/logout.php">Logout</a>
  </div>
</div>

<?php if ($m = get_flash('ok')): ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
<?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

<div class="shell">
  <!-- Sidebar -->
  <aside class="sidebar">
    <a class="side-btn" href="./create.php">⭐ Create</a>
    <div class="menu">
  <a href="./index.php">🏠 Home</a>
  <a href="./profile.php">👤 My Profile</a>
  <div class="muted">— — —</div>
  <a href="./auth/logout.php">↩︎ Logout</a>
</div>
  </aside>

  <!-- Main -->
  <main>
    <div class="pills">
      <span class="pill">Discovery</span>
      <span class="pill">Abstract</span>
      <span class="pill">Landscape</span>
      <span class="pill">Portrait</span>
      <span class="pill">+</span>
    </div>

    <section class="feed">
      <?php foreach ($pictures as $p): ?>
        <article class="card">
          <img src="uploads/<?= htmlspecialchars($p['picture_url']) ?>" alt="">
          <div class="card-body">
            <div class="card-title"><?= htmlspecialchars($p['picture_title']) ?></div>
            <?php if ($p['picture_description']): ?>
              <div style="color:#6b7280;font-size:13px;margin-top:4px;">
                <?= htmlspecialchars($p['picture_description']) ?>
              </div>
            <?php endif; ?>
            <div class="meta">
              <span><?= htmlspecialchars($p['display_name']) ?></span>
              <span class="counts">❤ <?= (int)$p['like_count'] ?> &nbsp; 💬 <?= (int)$p['comment_count'] ?></span>
            </div>
          </div>
        </article>
      <?php endforeach; ?>

      <?php if (!$pictures): ?>
        <p style="color:#6b7280">No pictures yet. Click <b>Create</b> to upload your first photo.</p>
      <?php endif; ?>
    </section>
  </main>
</div>

</body>
</html>