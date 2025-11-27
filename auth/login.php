<?php
require_once __DIR__ . '/../includes/init.php';

// --- Brute-force lock info from session ---
$lockEmail  = $_SESSION['login_lock_email'] ?? null;
$lockUntil  = (int)($_SESSION['login_lock_until'] ?? 0);
$now        = time();
$isLocked   = $lockEmail && $lockUntil > $now;
$lockRemain = max(0, $lockUntil - $now);

// If lock expired, clean it up
if (!$isLocked) {
    unset($_SESSION['login_lock_email'], $_SESSION['login_lock_until']);
}

// Simple math for CAPTCHA (used in modal)
$captchaA = random_int(1, 9);
$captchaB = random_int(1, 9);
$_SESSION['login_captcha_answer'] = $captchaA + $captchaB;
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

        <form
          id="loginForm"
          method="post"
          action="../actions/auth/post_login.php"
          autocomplete="off"
          data-locked="<?= $isLocked ? '1' : '0' ?>"
        >
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
          <input type="hidden" name="login_captcha" id="loginCaptchaHidden">

          <label class="label">Email</label>
          <input 
            class="input" 
            type="email" 
            name="login_email" 
            placeholder="noelwilson@gmail.com"
            value="<?= htmlspecialchars($lockEmail ?? '') ?>"
            required
            <?= $isLocked ? 'readonly' : '' ?>
          >

          <label class="label">Password</label>
          <div class="password-field">
            <input
              class="input password-input"
              type="password"
              name="password"
              placeholder="********"
              required
              <?= $isLocked ? 'readonly' : '' ?>
            >
            <button
              type="button"
              class="password-toggle"
              aria-label="Show password"
              <?= $isLocked ? 'disabled' : '' ?>
            >
              <i class="fa-solid fa-eye"></i>
            </button>
          </div>

          <div class="row">
            <span></span>
            <a class="link" href="./forgot_password.php">Forgot Password?</a>
          </div>

          <button
            class="btn btn-primary"
            type="submit"
            <?= $isLocked ? 'disabled' : '' ?>
            style="<?= $isLocked ? 'opacity:0.6; cursor:not-allowed;' : '' ?>"
          >
            Sign In
          </button>

          <?php if ($isLocked): ?>
            <p class="note" style="font-size: 12px; color:#b91c1c; margin-top:8px;">
              Login is temporarily disabled for this account due to too many failed attempts.
              Please try again later.
            </p>
          <?php endif; ?>

          <p class="note">Not registered? <a href="../home_guest.php">Take a look and change your mind</a></p>
          <p class="note">New User? <a href="./register.php">Sign Up</a></p>
        </form>

      </div>
    </div>

  </div>

  <!-- CAPTCHA MODAL -->
  <div class="captcha-modal-backdrop" id="captchaModalBackdrop"></div>
  <div class="captcha-modal" id="captchaModal" aria-hidden="true">
    <div class="captcha-modal__card">
      <h2 class="captcha-modal__title">Are you human?</h2>
      <p class="p-muted">Please solve this quick question to continue.</p>
      <p class="captcha-question">What is <?= $captchaA ?> + <?= $captchaB ?> ?</p>
      <input
        class="input"
        type="number"
        id="captchaInput"
        placeholder="Answer"
      >
      <div class="captcha-error" id="captchaError">Please enter the answer.</div>
      <div class="captcha-actions">
        <button type="button" class="btn-secondary" id="captchaCancel">Cancel</button>
        <button type="button" class="btn btn-primary" id="captchaConfirm">Verify &amp; Sign In</button>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      // Password toggle
      document.querySelectorAll(".password-toggle").forEach(btn => {
        if (btn.disabled) return; // locked state
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

      const form           = document.getElementById("loginForm");
      if (!form) return;

      const isLocked       = form.dataset.locked === "1";
      if (isLocked) {
        // Don't attach CAPTCHA modal if login is locked
        return;
      }

      const modal          = document.getElementById("captchaModal");
      const backdrop       = document.getElementById("captchaModalBackdrop");
      const captchaInput   = document.getElementById("captchaInput");
      const captchaHidden  = document.getElementById("loginCaptchaHidden");
      const captchaError   = document.getElementById("captchaError");
      const btnConfirm     = document.getElementById("captchaConfirm");
      const btnCancel      = document.getElementById("captchaCancel");

      function openModal() {
        modal.classList.add("is-open");
        backdrop.classList.add("is-open");
        modal.setAttribute("aria-hidden", "false");
        captchaError.style.display = "none";
        captchaInput.value = "";
        setTimeout(() => captchaInput.focus(), 50);
      }

      function closeModal() {
        modal.classList.remove("is-open");
        backdrop.classList.remove("is-open");
        modal.setAttribute("aria-hidden", "true");
      }

      form.addEventListener("submit", (e) => {
        // If CAPTCHA already answered, let it go
        if (captchaHidden.value) {
          return;
        }
        e.preventDefault();
        openModal();
      });

      btnCancel.addEventListener("click", () => {
        closeModal();
      });

      btnConfirm.addEventListener("click", () => {
        const val = captchaInput.value.trim();
        if (!val) {
          captchaError.style.display = "block";
          return;
        }
        captchaError.style.display = "none";
        captchaHidden.value = val;
        closeModal();
        form.submit();
      });

      document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && modal.classList.contains("is-open")) {
          closeModal();
        }
      });

      backdrop.addEventListener("click", closeModal);
    });
  </script>

</body>
</html>