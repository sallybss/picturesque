<?php
require __DIR__ . '/includes/flash.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/sidebar.php';

/* Guests see the public gallery */
if (empty($_SESSION['profile_id'])) {
  header('Location: ./home_guest.php');
  exit;
}

$me = (int)$_SESSION['profile_id'];
$conn = db();

/* Build dynamic base URL for assets (works in subfolders) */
$baseUrl       = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$publicUploads = $baseUrl . '/uploads/';

/* Top-right avatar/name + role */
$stmtMe = $conn->prepare("SELECT display_name, avatar_photo, role FROM profiles WHERE profile_id = ?");
$stmtMe->bind_param('i', $me);
$stmtMe->execute();
$meRow = $stmtMe->get_result()->fetch_assoc();
$stmtMe->close();

$isAdmin = (strtolower(trim($meRow['role'] ?? '')) === 'admin');

$meAvatarUrl = !empty($meRow['avatar_photo'])
  ? $publicUploads . htmlspecialchars($meRow['avatar_photo'])
  : 'https://placehold.co/28x28?text=%20';

/* ==========================================================
   Search text + People search (runs when there's a query)
   ========================================================== */
$q        = trim($_GET['q'] ?? '');
$qUser    = ltrim($q, '@');          // allow typing "@name"
$like     = '%'.$q.'%';
$likeUser = '%'.$qUser.'%';

$people = [];
if ($q !== '') {
  // If you later add a 'username' column and want strict username support,
  // this already handles it (display_name OR username).
  $stmtPeople = $conn->prepare("
    SELECT profile_id, display_name, avatar_photo, username
    FROM profiles
    WHERE display_name LIKE ? OR username LIKE ?
    ORDER BY display_name
    LIMIT 20
  ");
  $stmtPeople->bind_param('ss', $like, $likeUser);
  $stmtPeople->execute();
  $people = $stmtPeople->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmtPeople->close();
}

/* ==========================================================
   Pictures query — now searches title, description, AND uploader
   (display_name). Typing @name works as well.
   ========================================================== */
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
    COALESCE(l.cnt,0)  AS like_count,
    COALESCE(c.cnt,0)  AS comment_count,
    CASE WHEN ml.like_id IS NULL THEN 0 ELSE 1 END AS liked_by_me
  FROM pictures p
  JOIN profiles pr ON pr.profile_id = p.profile_id
  LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM likes    GROUP BY picture_id) l ON l.picture_id = p.picture_id
  LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM comments GROUP BY picture_id) c ON c.picture_id = p.picture_id
  LEFT JOIN likes ml ON ml.picture_id = p.picture_id AND ml.profile_id = ?
";

$types  = 'i';
$params = [$me];

if ($q !== '') {
  // also match uploader's display_name (supports "@name")
  $sql   .= " WHERE (p.picture_title LIKE ? OR p.picture_description LIKE ? OR pr.display_name LIKE ?) ";
  $types .= 'sss';
  $params[] = $like;      // title
  $params[] = $like;      // description
  $params[] = $likeUser;  // display name (without leading '@')
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

/* cache-bust css */
$cssPath = __DIR__ . '/public/css/main.css';
$ver = file_exists($cssPath) ? filemtime($cssPath) : time();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Home · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=<?= $ver ?>">
  <style>
    /* Make the top-right userbox clickable without changing layout */
    a.userbox { text-decoration: none; color: inherit; display: flex; align-items: center; gap: 8px; }
    a.userbox:hover { opacity: .85; }

    /* Minimal styling for the People search strip */
    .section-title{ font-size:16px; font-weight:800; margin: 4px 0 8px; color:#111827; }
    .people-row{ display:flex; gap:12px; flex-wrap:wrap; margin:6px 0 16px; }
    .people-row .person{
      display:flex; align-items:center; gap:10px;
      padding:8px 10px; background:#fff; border:1px solid var(--line);
      border-radius:12px; text-decoration:none; color:#111827;
    }
    .people-row .person:hover{ background:#f9fafb; }
    .people-row .person img{
      width:28px; height:28px; border-radius:999px; object-fit:cover; background:#e5e7eb;
    }
    .people-row .person span{ font-weight:600; font-size:14px; }
  </style>
</head>
<body>

<?php if ($m = get_flash('ok')): ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
<?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

<div class="layout">
  <!-- Sidebar -->
  <?php render_sidebar(['isAdmin' => $isAdmin]); ?>

  <!-- Content -->
  <main class="content">
    <div class="content-top">
      <form method="get" action="index.php" class="search-wrap">
        <input class="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search photos or username">
      </form>

      <!-- Clickable avatar/name -> profile -->
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

    <!-- People matches (only when searching) -->
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
        <article class="card">
          <img src="<?= $publicUploads . htmlspecialchars($p['picture_url']) ?>" alt="">

          <div class="card-body">
          <a class="author-row" href="profile.php?id=<?= (int)$p['profile_id'] ?>" style="text-decoration:none; color:inherit;">
  <?php
    $avatarUrl = !empty($p['avatar_photo'])
      ? $publicUploads . htmlspecialchars($p['avatar_photo'])
      : 'https://placehold.co/24x24?text=%20';
  ?>
  <img class="mini-avatar" src="<?= $avatarUrl ?>" alt="<?= htmlspecialchars($p['display_name']) ?> avatar">
  <span class="author"><?= htmlspecialchars($p['display_name']) ?></span>
</a>

            <div class="card-title"><?= htmlspecialchars($p['picture_title']) ?></div>

            <?php if (!empty($p['picture_description'])): ?>
              <div class="card-desc"><?= htmlspecialchars($p['picture_description']) ?></div>
            <?php endif; ?>

            <div class="meta">
              <span class="counts">
                <form method="post" action="./actions/toggle_like.php" style="display:inline">
                  <input type="hidden" name="picture_id" value="<?= (int)$p['picture_id'] ?>">
                  <button type="submit" class="iconbtn">
                    <?= ((int)$p['liked_by_me'] === 1) ? '❤' : '♡' ?> <?= (int)$p['like_count'] ?>
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