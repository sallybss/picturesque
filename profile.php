<?php
require __DIR__ . '/includes/flash.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/sidebar.php';

if (empty($_SESSION['profile_id'])) {
  header('Location: ./auth/login.php');
  exit;
}

$me   = (int)$_SESSION['profile_id'];
$conn = db();

/* Base URLs for assets (works in XAMPP subfolder paths) */
$baseUrl       = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); // e.g. /picturesque/picturesque
$publicUploads = $baseUrl . '/uploads/';
$publicImages  = $baseUrl . '/images/';

/* --- Load my profile (NOW includes role + cover_photo) --- */
$sql = "
  SELECT
    pr.profile_id,
    pr.display_name,
    pr.email,
    pr.avatar_photo,
    pr.cover_photo,
    pr.role,           -- << added
    pr.created_at
  FROM profiles pr
  WHERE pr.profile_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $me);
$stmt->execute();
$meRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* Compute admin from DB role */
$isAdmin = (($meRow['role'] ?? 'user') === 'admin');

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
$avatarSrc = !empty($meRow['avatar_photo'])
  ? $publicUploads . htmlspecialchars($meRow['avatar_photo'])
  : 'https://placehold.co/96x96?text=%20';

$coverSrc = !empty($meRow['cover_photo'])
  ? $publicUploads . htmlspecialchars($meRow['cover_photo'])
  : $publicImages . 'default-cover.jpg'; // put a file at /images/default-cover.jpg
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>My Profile · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=9">
  <style>
    /* Minimal cover styling (you can move to main.css) */
    .profile-cover{position:relative;height:200px;border-radius:12px;overflow:hidden;margin-bottom:16px;background:#f2f3f5}
    .profile-cover .cover-image{width:100%;height:100%;object-fit:cover;display:block}
    .profile-cover .cover-actions{position:absolute;right:12px;bottom:12px;display:flex;gap:8px}
    .btn-ghost.small{background:rgba(255,255,255,.9);padding:6px 10px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer}
  </style>
</head>

<body>
  <?php if ($m = get_flash('ok')): ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
  <?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

  <div class="layout">
    <!-- Sidebar component -->
    <?php render_sidebar(['isAdmin' => $isAdmin]); ?>

    <!-- Content -->
    <main class="content">

      <!-- Cover banner (with Change & Reset like LinkedIn) -->
      <div class="profile-cover">
        <img src="<?= $coverSrc ?>" alt="Cover Photo" class="cover-image">

        <div class="cover-actions">
          <!-- Change Cover -->
          <form method="post" action="./actions/update_cover.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input id="coverInput" type="file" name="cover" accept="image/*" hidden onchange="this.form.submit()">
            <label for="coverInput" class="btn-ghost small">Change Cover</label>
          </form>

          <!-- Reset to default (nulls the field so fallback shows) -->
          <form method="post" action="./actions/update_cover.php">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="action" value="reset">
            <button type="submit" class="btn-ghost small">Reset</button>
          </form>
        </div>
      </div>

      <!-- Profile header -->
      <section class="profile-hero">
        <img class="profile-avatar" src="<?= $avatarSrc ?>" alt="">
        <div class="profile-info">
          <div class="profile-name"><?= htmlspecialchars($meRow['display_name'] ?? 'You') ?></div>
          <?php if (!empty($meRow['email'])): ?>
            <div class="profile-email"><?= htmlspecialchars($meRow['email']) ?></div>
          <?php endif; ?>
          <div class="profile-stats">
            <span><b><?= (int)$stats['pictures'] ?></b> Posts</span>
            <span><b><?= (int)$stats['likes'] ?></b> Likes</span>
            <span><b><?= (int)$stats['comments'] ?></b> Comments</span>
          </div>
          <div class="profile-actions" style="display:flex; gap:8px; flex-wrap:wrap">
            <a class="btn-primary" href="./profile_edit.php">Edit Profile</a>
            <a class="btn-ghost" href="./profile_settings.php">Profile Settings</a>
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