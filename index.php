<?php
require __DIR__ . '/includes/flash.php';
if (empty($_SESSION['profile_id'])) { header('Location: ./auth/login.php'); exit; }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Home · Picturesque</title>
  <link rel="stylesheet" href="./public/css/main.css">
</head>
<body style="padding:24px">
  <?php if ($m = get_flash('ok')): ?>
    <div class="flash ok"><?= htmlspecialchars($m) ?></div>
  <?php endif; ?>
  <h2>Welcome, <?= htmlspecialchars($_SESSION['display_name'] ?? 'User') ?>!</h2>
  <p><a class="link" href="./auth/logout.php">Logout</a></p>
</body>
</html>