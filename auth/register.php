<?php require __DIR__ . '/../includes/flash.php'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Sign Up · Picturesque</title>
  <link rel="stylesheet" href="../public/css/main.css">
</head>
<body>
  <div class="auth-wrap">
    <div class="auth-left"></div>
    <div class="auth-right">
      <div class="auth-card">
        <h1>Create Account</h1>
        <p class="p-muted">Join Picturesque to share your photos.</p>

        <?php if ($m = get_flash('err')): ?>
          <div class="flash err"><?= htmlspecialchars($m) ?></div>
        <?php endif; ?>

        <form method="post" action="../actions/post_register.php">
          <label class="label">Email (login)</label>
          <input class="input" type="email" name="login_email" required>

          <label class="label">Display name</label>
          <input class="input" type="text" name="display_name" required>

          <label class="label">Password</label>
          <input class="input" type="password" name="password" required>

          <button class="btn" type="submit">Sign Up</button>
          <p class="note">Already have an account? <a href="./login.php">Sign In</a></p>
        </form>
      </div>
    </div>
  </div>
</body>
</html>