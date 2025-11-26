<?php
require_once __DIR__ . '/../includes/init.php';

$token = trim($_GET['token'] ?? '');

$valid = false;
if ($token !== '') {
    $resets = new PasswordResetRepository();
    $row = $resets->findValidByToken($token);
    $valid = (bool)$row;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Reset Password · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../public/css/main.css?v=2">
</head>
<body>
  <div class="auth-wrap">
    <div class="auth-left" style="--auth-bg:url('https://images.unsplash.com/photo-1501785888041-af3ef285b470?auto=format&fit=crop&w=1200&q=80');"></div>

    <div class="auth-right">
      <div class="auth-card">

        <h1>Reset Password</h1>

        <?php if ($m = get_flash('err')): ?>
          <div class="flash err"><?= htmlspecialchars($m) ?></div>
        <?php endif; ?>

        <?php if ($m = get_flash('ok')): ?>
          <div class="flash ok"><?= htmlspecialchars($m) ?></div>
        <?php endif; ?>

        <?php if (!$valid): ?>
          <p class="p-muted">This reset link is invalid or has expired. Please request a new one.</p>
          <p class="note"><a href="./forgot_password.php">Back to Forgot Password</a></p>
        <?php else: ?>

          <p class="p-muted">Choose a new password for your account.</p>

          <form method="post" action="../actions/auth/post_reset_password.php" autocomplete="off">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <label class="label">New password</label>
            <div class="password-field">
              <input
                class="input password-input"
                type="password"
                name="password"
                minlength="8"
                maxlength="32"
                placeholder="••••••••"
                required
              >
              <button type="button" class="password-toggle" aria-label="Show password">
                <i class="fa-solid fa-eye"></i>
              </button>
            </div>
            <div class="input-hint">Password must be 8–32 characters.</div>

            <label class="label">Confirm password</label>
            <input
              class="input"
              type="password"
              name="password_confirm"
              minlength="8"
              maxlength="32"
              required
            >

            <button class="btn btn-primary" type="submit">Set new password</button>

            <p class="note">
              Remembered your password?
              <a href="./login.php">Back to Sign In</a>
            </p>
          </form>

        <?php endif; ?>

      </div>
    </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      document.querySelectorAll(".password-toggle").forEach(btn => {
        btn.addEventListener("click", () => {
          const input = btn.parentElement.querySelector(".password-input");
          const icon  = btn.querySelector("i");

          if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
          } else {
            input.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
          }
        });
      });
    });
  </script>
</body>
</html>