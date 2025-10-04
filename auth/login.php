<?php require __DIR__ . '/../includes/flash.php'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Sign In · Picturesque</title>
  <link rel="stylesheet" href="../public/css/main.css">
</head>
<body>
  <div class="auth-wrap">
    <div class="auth-left"></div>
    <div class="auth-right">
      <div class="auth-card">
        <h1>Sign In</h1>
        <p class="p-muted">Welcome! Please sign in/up to your account.</p>

        <?php if ($m = get_flash('err')): ?>
          <div class="flash err"><?= htmlspecialchars($m) ?></div>
        <?php endif; ?>
        <?php if ($m = get_flash('ok')): ?>
          <div class="flash ok"><?= htmlspecialchars($m) ?></div>
        <?php endif; ?>

        <form method="post" action="../actions/post_login.php">
          <label class="label">Email</label>
          <input class="input" type="email" name="login_email" placeholder="noelwilson@gmail.com" required>

          <label class="label">Password</label>
          <input class="input" type="password" name="password" placeholder="********" required>

          <div class="row">
            <span></span>
            <a class="link" href="#">Forgot Password?</a>
          </div>

          <button class="btn" type="submit">Sign In</button>
          <p class="note">New User? <a href="./register.php">Sign Up</a></p>
        </form>
      </div>
    </div>
  </div>
</body>
</html>