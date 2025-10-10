<?php
require __DIR__ . '/includes/flash.php';
require __DIR__ . '/includes/db.php';

if (empty($_SESSION['profile_id'])) {
  header('Location: ./auth/login.php');
  exit;
}

$me = (int)$_SESSION['profile_id'];
$conn = db();

/* base URL for images */
$baseUrl       = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$publicUploads = $baseUrl . '/uploads/';


/* --- Load my profile + quick stats --- */
$sql = "
  SELECT
    pr.profile_id,
    pr.display_name,
    pr.email,          -- public email (nullable)
    pr.avatar_photo,   -- path or NULL
    pr.created_at
  FROM profiles pr
  WHERE pr.profile_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $me);
$stmt->execute();
$meRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* stats: pictures, total likes, total comments */
$stats = ['pictures' => 0, 'likes' => 0, 'comments' => 0];
$stmt = $conn->prepare("
  SELECT
    (SELECT COUNT(*) FROM pictures  WHERE profile_id = ?) AS pictures,
    (SELECT COUNT(*) FROM likes     l JOIN pictures p ON p.picture_id=l.picture_id WHERE p.profile_id=?) AS likes,
    (SELECT COUNT(*) FROM comments  c JOIN pictures p ON p.picture_id=c.picture_id WHERE p.profile_id=?) AS comments
");
$stmt->bind_param('iii', $me, $me, $me);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc() ?: $stats;
$stmt->close();

/* --- My pictures (newest first) + counts --- */
$stmt = $conn->prepare("
  SELECT
    p.picture_id, p.picture_title, p.picture_description, p.picture_url, p.created_at,
    COALESCE(l.cnt,0) AS like_count,
    COALESCE(c.cnt,0) AS comment_count
  FROM pictures p
  LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM likes    GROUP BY picture_id) l ON l.picture_id = p.picture_id
  LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM comments GROUP BY picture_id) c ON c.picture_id = p.picture_id
  WHERE p.profile_id = ?
  ORDER BY p.created_at DESC
");
$stmt->bind_param('i', $me);
$stmt->execute();
$res = $stmt->get_result();
$myPics = [];
while ($row = $res->fetch_assoc()) {
  $myPics[] = $row;
}
$stmt->close();
$conn->close();

/* helpers */
$avatarSrc = !empty($meRow['avatar_photo']) ? 'uploads/' . htmlspecialchars($meRow['avatar_photo']) : 'https://placehold.co/96x96?text=%20';
?>

<?php $cur = basename($_SERVER['PHP_SELF']); ?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>My Profile · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=7">
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
        <a href="./index.php" class="<?= $cur === 'index.php'   ? 'active' : '' ?>">
          <svg class="ico" viewBox="0 0 256 256" fill="currentColor" aria-hidden="true">
          <path d="M219.31,108.68l-80-80a16,16,0,0,0-22.62,0l-80,80A15.87,15.87,0,0,0,32,120v96a8,8,0,0,0,8,8h64a8,8,0,0,0,8-8V160h32v56a8,8,0,0,0,8,8h64a8,8,0,0,0,8-8V120A15.87,15.87,0,0,0,219.31,108.68ZM208,208H160V152a8,8,0,0,0-8-8H104a8,8,0,0,0-8,8v56H48V120l80-80,80,80Z"></path>
          </svg>
          Home
          <span class="badge">5</span> <!-- notification optional, can remove -->
        </a>

        <a href="./profile.php" class="<?= in_array($cur, ['profile.php','profile_edit.php']) ? 'active' : '' ?>">
          <svg class="ico" viewBox="0 0 256 256" fill="currentColor" aria-hidden="true">
          <path d="M230.92,212c-15.23-26.33-38.7-45.21-66.09-54.16a72,72,0,1,0-73.66,0C63.78,166.78,40.31,185.66,25.08,212a8,8,0,1,0,13.85,8c18.84-32.56,52.14-52,89.07-52s70.23,19.44,89.07,52a8,8,0,1,0,13.85-8ZM72,96a56,56,0,1,1,56,56A56.06,56.06,0,0,1,72,96Z"></path>
          </svg>
          My Profile
        </a>

        <div class="rule"></div>

        <a href="./settings.php" class="<?= $cur === 'settings.php'   ? 'active' : '' ?>">
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
      <!-- Profile header -->
      <section class="profile-hero">
        <img class="profile-avatar" src="<?= $avatarSrc ?>" alt="">
        <div class="profile-info">
          <div class="profile-name"><?= htmlspecialchars($meRow['display_name']) ?></div>
          <?php if (!empty($meRow['email'])): ?>
            <div class="profile-email"><?= htmlspecialchars($meRow['email']) ?></div>
          <?php endif; ?>
          <div class="profile-stats">
            <span><b><?= (int)$stats['pictures'] ?></b> Posts</span>
            <span><b><?= (int)$stats['likes'] ?></b> Likes</span>
            <span><b><?= (int)$stats['comments'] ?></b> Comments</span>
          </div>
          <div class="profile-actions">
            <a class="btn-primary" href="./profile_edit.php">Edit Profile</a>
          </div>
        </div>
      </section>

      <!-- Grid of my pictures -->
      <h2 class="section-title">My Photos</h2>
      <section class="feed">
        <?php foreach ($myPics as $p): ?>
          <article class="card">
            <img src="<?= $publicUploads . htmlspecialchars($p['picture_url']) ?>" alt="">

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

                <a class="btn-ghost" href="./edit_picture.php?id=<?= (int)$p['picture_id'] ?>">Edit</a>

                <form method="post"
                  action="./actions/delete_picture.php"
                  onsubmit="return confirm('Delete this photo? This cannot be undone.');"
                  style="display:inline">
                  <input type="hidden" name="picture_id" value="<?= (int)$p['picture_id'] ?>">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                  <button type="submit" class="btn-danger">Delete</button>
                </form>
              </div>
            </div>
          </article>
        <?php endforeach; ?>

        <?php if (!$myPics): ?>
          <p class="muted">You haven’t posted yet. Click <b>Create</b> to upload your first photo.</p>
        <?php endif; ?>
      </section>

    </main>
  </div>
</body>
</html>