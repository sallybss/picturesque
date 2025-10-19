<?php
require __DIR__ . '/includes/flash.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/sidebar.php';

if (empty($_SESSION['profile_id'])) {
  header('Location: ./auth/login.php');
  exit;
}

$profileId = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_SESSION['profile_id'] ?? 0);
if ($profileId <= 0) { header('Location: ./auth/login.php'); exit; }

$me   = (int)$_SESSION['profile_id'];
$conn = db();

$baseUrl       = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$publicUploads = $baseUrl . '/uploads/';
$publicImages  = $baseUrl . '/images/';

$stmt = $conn->prepare("SELECT role FROM profiles WHERE profile_id = ?");
$stmt->bind_param('i', $me);
$stmt->execute();
$myRow = $stmt->get_result()->fetch_assoc();
$stmt->close();
$iAmAdmin = (($myRow['role'] ?? 'user') === 'admin');

$stmt = $conn->prepare("
  SELECT profile_id, display_name, email, avatar_photo, cover_photo, role, created_at
  FROM profiles
  WHERE profile_id = ?
");
$stmt->bind_param('i', $profileId);
$stmt->execute();
$viewRow = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$viewRow) { header('Location: ./auth/login.php'); exit; }

$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM pictures WHERE profile_id = ?");
$stmt->bind_param('i', $profileId);
$stmt->execute();
$picturesCount = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
$stmt->close();

$stmt = $conn->prepare("
  SELECT COUNT(*) AS cnt
  FROM likes l
  JOIN pictures p ON p.picture_id = l.picture_id
  WHERE p.profile_id = ?
");
$stmt->bind_param('i', $profileId);
$stmt->execute();
$likesCount = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
$stmt->close();

$stmt = $conn->prepare("
  SELECT COUNT(*) AS cnt
  FROM comments c
  JOIN pictures p ON p.picture_id = c.picture_id
  WHERE p.profile_id = ?
");
$stmt->bind_param('i', $profileId);
$stmt->execute();
$commentsCount = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
$stmt->close();

$stmt = $conn->prepare("
  SELECT
    p.picture_id, p.picture_title, p.picture_description, p.picture_url, p.created_at,
    COALESCE(l.cnt,0) AS like_count,
    COALESCE(c.cnt,0) AS comment_count
  FROM pictures p
  LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM likes GROUP BY picture_id) l ON l.picture_id = p.picture_id
  LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM comments GROUP BY picture_id) c ON c.picture_id = p.picture_id
  WHERE p.profile_id = ?
  ORDER BY p.created_at DESC
");
$stmt->bind_param('i', $profileId);
$stmt->execute();
$res = $stmt->get_result();
$myPics = [];
while ($row = $res->fetch_assoc()) { $myPics[] = $row; }
$stmt->close();
$conn->close();

$avatarSrc = !empty($viewRow['avatar_photo'])
  ? $publicUploads . htmlspecialchars($viewRow['avatar_photo'])
  : 'https://placehold.co/96x96?text=%20';

$coverSrc = !empty($viewRow['cover_photo'])
  ? $publicUploads . htmlspecialchars($viewRow['cover_photo'])
  : $publicImages . 'default-cover.jpg';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($viewRow['display_name']) ?> · Profile</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=1">
</head>
<body>
  <?php if ($m = get_flash('ok')): ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
  <?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

  <div class="layout">
    <?php render_sidebar(['isAdmin' => $iAmAdmin]); ?>

    <main class="content">
      <div class="profile-cover">
        <img src="<?= $coverSrc ?>" alt="Cover Photo" class="cover-image">

        <?php if ($me === $profileId): ?>
          <div class="cover-actions">
            <form method="post" action="./actions/update_cover.php" enctype="multipart/form-data">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
              <input id="coverInput" type="file" name="cover" accept="image/*" hidden onchange="this.form.submit()">
              <label for="coverInput" class="btn-ghost small">Change Cover</label>
            </form>
            <form method="post" action="./actions/update_cover.php">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
              <input type="hidden" name="action" value="reset">
              <button type="submit" class="btn-ghost small">Reset</button>
            </form>
          </div>
        <?php endif; ?>
      </div>

      <section class="profile-hero">
        <img class="profile-avatar" src="<?= $avatarSrc ?>" alt="">
        <div class="profile-info">
          <div class="profile-name"><?= htmlspecialchars($viewRow['display_name']) ?></div>
          <?php if (!empty($viewRow['email'])): ?>
            <div class="profile-email"><?= htmlspecialchars($viewRow['email']) ?></div>
          <?php endif; ?>
          <div class="profile-stats">
            <span><b><?= (int)$picturesCount ?></b> Posts</span>
            <span><b><?= (int)$likesCount ?></b> Likes</span>
            <span><b><?= (int)$commentsCount ?></b> Comments</span>
          </div>
          <?php if ($me === $profileId): ?>
            <div class="profile-actions" style="display:flex; gap:8px; flex-wrap:wrap">
              <a class="btn-primary" href="./profile_edit.php">Edit Profile</a>
              <a class="btn-ghost" href="./profile_settings.php">Profile Settings</a>
            </div>
          <?php endif; ?>
        </div>
      </section>

      <h2 class="section-title"><?= ($me === $profileId ? 'My Photos' : 'Photos') ?></h2>
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
                <?php if ($me === $profileId): ?>
                  <a class="btn-ghost" href="./edit_picture.php?id=<?= (int)$p['picture_id'] ?>">Edit</a>
                  <form method="post" action="./actions/delete_picture.php" onsubmit="return confirm('Delete this photo? This cannot be undone.');" style="display:inline">
                    <input type="hidden" name="picture_id" value="<?= (int)$p['picture_id'] ?>">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <button type="submit" class="btn-danger">Delete</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>

        <?php if (!$myPics): ?>
          <p class="muted"><?= ($me === $profileId ? "You haven’t posted yet. Click " : "No photos yet.") ?><b><?= ($me === $profileId ? "Create" : "") ?></b></p>
        <?php endif; ?>
      </section>
    </main>
  </div>
</body>
</html>
