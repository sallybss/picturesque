<?php
require_once __DIR__ . '/../includes/init.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Sign In · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../public/css/main.css?v=2">
</head>
<body>
  <div class="auth-wrap">
    
    <div class="auth-left" style="--auth-bg:url('https://images.unsplash.com/photo-1501785888041-af3ef285b470?auto=format&fit=crop&w=1200&q=80');"></div>

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

        <form method="post" action="../actions/auth/post_login.php" autocomplete="off">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

          <label class="label">Email</label>
          <input 
            class="input" 
            type="email" 
            name="login_email" 
            placeholder="noelwilson@gmail.com" 
            required
          >

          <label class="label">Password</label>
          <div class="password-field">
            <input
              class="input password-input"
              type="password"
              name="password"
              placeholder="********"
              required
            >
            <button type="button" class="password-toggle" aria-label="Show password">
              <i class="fa-solid fa-eye"></i>
            </button>
          </div>

          <div class="row">
            <span></span>
            <a class="link" href="./forgot_password.php">Forgot Password?</a>
          </div>

          <button class="btn btn-primary" type="submit">Sign In</button>

          <p class="note">Not registered? <a href="../home_guest.php">Take a look and change your mind</a></p>
          <p class="note">New User? <a href="./register.php">Sign Up</a></p>
        </form>

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