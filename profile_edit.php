<?php
require __DIR__ . '/includes/flash.php';
require __DIR__ . '/includes/db.php';

if (empty($_SESSION['profile_id'])) { header('Location: ./auth/login.php'); exit; }

$me = (int)$_SESSION['profile_id'];
$conn = db();

$stmt = $conn->prepare("SELECT display_name, email, avatar_photo FROM profiles WHERE profile_id = ?");
$stmt->bind_param('i', $me);
$stmt->execute();
$meRow = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

$avatarSrc = !empty($meRow['avatar_photo']) ? 'uploads/'.htmlspecialchars($meRow['avatar_photo']) : 'https://placehold.co/96x96?text=%20';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Edit Profile · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=7">
</head>
<body>

<?php if ($m = get_flash('ok')): ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
<?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

<div class="layout">
  <aside class="sidenav">
    <div class="brand">PICTURESQUE</div>
    <a class="create-btn" href="./create.php">⭐ Create</a>
    <nav class="nav">
      <a href="./index.php">🏠 Home</a>
      <a href="./profile.php">👤 My Profile</a>
      <div class="rule"></div>
      <a href="./auth/logout.php">↩︎ Logout</a>
    </nav>
  </aside>

  <main class="content">
    <div class="form-card">
      <h2 style="margin:0 0 10px">Edit Profile</h2>
      <div style="display:flex;align-items:center;gap:12px;margin:10px 0 18px">
        <img src="<?= $avatarSrc ?>" style="width:64px;height:64px;border-radius:999px;object-fit:cover" alt="">
        <span class="muted">Upload a new avatar (JPG/PNG/WEBP)</span>
      </div>

      <form action="./actions/post_profile_update.php" method="post" enctype="multipart/form-data">
        <div class="form-row">
          <label class="label-sm">Display name</label>
          <input class="input-sm" name="display_name" value="<?= htmlspecialchars($meRow['display_name'] ?? '') ?>" required>
        </div>

        <div class="form-row">
          <label class="label-sm">Public email (optional)</label>
          <input class="input-sm" name="email" value="<?= htmlspecialchars($meRow['email'] ?? '') ?>">
        </div>

        <div class="form-row">
          <label class="label-sm">Avatar image</label>
          <input type="file" name="avatar" accept="image/*">
        </div>

        <div class="form-row">
          <button class="btn-primary" type="submit" name="submit">Save changes</button>
          <a class="link" style="margin-left:10px" href="./profile.php">Cancel</a>
        </div>
      </form>
    </div>
  </main>
</div>

</body>
</html>