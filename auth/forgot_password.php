<?php
require_once __DIR__ . '/../includes/init.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Forgot Password · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <link rel="stylesheet" href="../public/css/main.css?v=2">
</head>
<body>
  <div class="auth-wrap">
    <div class="auth-left" style="--auth-bg:url('https://images.unsplash.com/photo-1501785888041-af3ef285b470?auto=format&fit=crop&w=1200&q=80');"></div>

    <div class="auth-right">
      <div class="auth-card">

        <h1>Forgot Password</h1>
        <p class="p-muted">Enter your login email and we will send you a reset link.</p>

        <?php if ($m = get_flash('err')): ?>
          <div class="flash err"><?= htmlspecialchars($m) ?></div>
        <?php endif; ?>

        <?php if ($m = get_flash('ok')): ?>
          <div class="flash ok"><?= htmlspecialchars($m) ?></div>
        <?php endif; ?>

        <form method="post" action="../actions/auth/post_forgot_password.php" autocomplete="off">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

          <label class="label">Email</label>
          <input
            class="input"
            type="email"
            name="login_email"
            placeholder="you@example.com"
            required
          >

          <button class="btn btn-primary" type="submit">Send reset link</button>

          <p class="note">
            Remembered your password?
            <a href="./login.php">Back to Sign In</a>
          </p>
        </form>

      </div>
    </div>
  </div>
</body>
</html>