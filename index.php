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
      <a href="./index.php" class="active">
        <svg class="ico" viewBox="0 0 256 256" fill="currentColor" aria-hidden="true">
         <path d="M219.31,108.68l-80-80a16,16,0,0,0-22.62,0l-80,80A15.87,15.87,0,0,0,32,120v96a8,8,0,0,0,8,8h64a8,8,0,0,0,8-8V160h32v56a8,8,0,0,0,8,8h64a8,8,0,0,0,8-8V120A15.87,15.87,0,0,0,219.31,108.68ZM208,208H160V152a8,8,0,0,0-8-8H104a8,8,0,0,0-8,8v56H48V120l80-80,80,80Z"></path>
        </svg>
        Home
        <span class="badge">5</span> <!-- notification optional, can remove -->
      </a>

      <a href="./profile.php">
        <svg class="ico" viewBox="0 0 256 256" fill="currentColor" aria-hidden="true">
         <path d="M230.92,212c-15.23-26.33-38.7-45.21-66.09-54.16a72,72,0,1,0-73.66,0C63.78,166.78,40.31,185.66,25.08,212a8,8,0,1,0,13.85,8c18.84-32.56,52.14-52,89.07-52s70.23,19.44,89.07,52a8,8,0,1,0,13.85-8ZM72,96a56,56,0,1,1,56,56A56.06,56.06,0,0,1,72,96Z"></path>
        </svg>
        My Profile
      </a>

      <div class="rule"></div>

      <a href="./settings.php">
        <span class="icon">⚙️</span>
        Settings
      </a>
      <a href="./auth/logout.php">
        <svg class="ico" viewBox="0 0 256 256" fill="currentColor" aria-hidden="true">
         <path d="M120,216a8,8,0,0,1-8,8H48a8,8,0,0,1-8-8V40a8,8,0,0,1,8-8h64a8,8,0,0,1,0,16H56V208h56A8,8,0,0,1,120,216Zm109.66-93.66-40-40a8,8,0,0,0-11.32,11.32L204.69,120H112a8,8,0,0,0,0,16h92.69l-26.35,26.34a8,8,0,0,0,11.32,11.32l40-40A8,8,0,0,0,229.66,122.34Z"></path>
        </svg>
        Logout
      </a>
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

    <div class="controls-row">
      <div class="pills">
        <span class="pill">Discovery</span>
        <span class="pill">Abstract</span>
        <span class="pill">Sci-fi</span>
        <span class="pill">Landscape</span>
        <span class="pill">+</span>
      </div>   <!-- Good if we can make dynamic -->

      <button class="filter-btn" type="button">
        <svg class="icon" viewBox="0 0 256 256" aria-hidden="true">
          <path fill="currentColor" d="M230.6,49.53A15.81,15.81,0,0,0,216,40H40A16,16,0,0,0,28.19,66.76l.08.09L96,139.17V216a16,16,0,0,0,24.87,13.32l32-21.34A16,16,0,0,0,160,194.66V139.17l67.74-72.32.08-.09A15.8,15.8,0,0,0,230.6,49.53ZM40,56h0Zm106.18,74.58A8,8,0,0,0,144,136v58.66L112,216V136a8,8,0,0,0-2.16-5.47L40,56H216Z"/>
        </svg>
        Filter 
        <svg class="chev" viewBox="0 0 256 256" aria-hidden="true">
          <path fill="currentColor" d="M213.66,101.66l-80,80a8,8,0,0,1-11.32,0l-80-80A8,8,0,0,1,53.66,90.34L128,164.69l74.34-74.35a8,8,0,0,1,11.32,11.32Z"/>
        </svg>
      </button>


    </div>

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