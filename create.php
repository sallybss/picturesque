<?php
require __DIR__ . '/includes/flash.php';
if (empty($_SESSION['profile_id'])) { header('Location: ./auth/login.php'); exit; }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Create · Picturesque</title>
  <link rel="stylesheet" href="./public/css/main.css">
  <style>
    .container{max-width:900px;margin:30px auto;background:#fff;border:1px solid #eee;border-radius:14px;padding:20px}
    .row{margin:10px 0}
    .input, textarea{width:100%;padding:12px;border:1px solid #e5e7eb;border-radius:10px}
    .btn{padding:12px 16px;border:none;border-radius:10px;background:#8ec6df;color:#fff;font-weight:600;cursor:pointer}
    .top{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
  </style>
</head>
<body>
  <div class="container">
    <div class="top">
      <h2>Create a post</h2>
      <a href="./index.php" class="link">Back to feed</a>
    </div>

    <?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>
    <?php if ($m = get_flash('ok')): ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>

    <form method="post" action="./actions/post_picture.php" enctype="multipart/form-data">
      <div class="row">
        <label>Photo</label><br>
        <input type="file" name="photo" accept="image/*" required>
      </div>
      <div class="row">
        <label>Title</label>
        <input class="input" type="text" name="picture_title" required>
      </div>
      <div class="row">
        <label>Description</label>
        <textarea class="input" name="picture_description" rows="4"></textarea>
      </div>
      <button class="btn" type="submit" name="submit">Post</button>
    </form>
  </div>
</body>
</html>