<?php
require_once __DIR__ . '/../includes/init.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Sign Up · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- Main CSS -->
  <link rel="stylesheet" href="../public/css/main.css?v=2">
</head>
<body>
  <div class="auth-wrap">

    <div class="auth-left" style="--auth-bg:url('https://images.unsplash.com/photo-1501785888041-af3ef285b470?auto=format&fit=crop&w=1200&q=80');"></div>

    <div class="auth-right">
      <div class="auth-card">

        <h1>Create Account</h1>
        <p class="p-muted">Join Picturesque to share your photos.</p>

        <?php if ($m = get_flash('err')): ?>
          <div class="flash err"><?= htmlspecialchars($m) ?></div>
        <?php endif; ?>

        <?php if ($m = get_flash('ok')): ?>
          <div class="flash ok"><?= htmlspecialchars($m) ?></div>
        <?php endif; ?>

        <form method="post" action="../actions/auth/post_register.php" autocomplete="off">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

          <!-- EMAIL -->
          <label class="label">Email (login)</label>
          <input class="input" type="email" name="login_email" required>

          <!-- DISPLAY NAME -->
          <label class="label">Display name</label>
          <input class="input" type="text" name="display_name" required>

          <!-- PASSWORD -->
          <label class="label">Password</label>
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

          <button class="btn btn-primary" type="submit">Sign Up</button>

          <p class="note">
            Not registered? <a href="../home_guest.php">Take a look and change your mind</a>
          </p>

          <p class="note">
            Already have an account? <a href="./login.php">Sign In</a>
          </p>

        </form>
      </div>
    </div>

  </div>

  <!-- PASSWORD TOGGLE JS -->
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