<?php
require_once __DIR__ . '/includes/init.php';

$me = Auth::requireUserOrRedirect('./auth/login.php');

$profiles = new ProfileRepository();
$meRow = $profiles->getById($me);
if (!$meRow) { header('Location: ./index.php'); exit; }

$isAdmin      = strtolower(trim($meRow['role'] ?? '')) === 'admin';
$prefillName  = $meRow['display_name'] ?? '';
$prefillEmail = $meRow['email'] ?? '';

$cssPath = __DIR__ . '/public/css/main.css';
$ver = file_exists($cssPath) ? filemtime($cssPath) : time();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Contact · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=<?= $ver ?>">
</head>
<body>
  <?php if ($m = get_flash('ok')): ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
  <?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

  <div class="layout">
    <?php render_sidebar(['isAdmin' => $isAdmin]); ?>

    <main class="content">
      <section class="contact-shell">
        <div class="contact-split">
          <div class="contact-media">
            <img src="./images/contact-side.jpg" alt="Contact"
                 onerror="this.src='https://images.unsplash.com/photo-1519681393784-d120267933ba?q=80&w=1600&auto=format&fit=crop'">
          </div>

          <div class="contact-pane">
            <h1>Contact us</h1>
            <p class="sub">Don’t hesitate to get in touch for anything!</p>

            <form method="post" action="./actions/contact_submit.php" novalidate>
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

              <div class="contact-grid2">
                <div>
                  <div class="contact-label">Name</div>
                  <input class="contact-input" name="name" required value="<?= htmlspecialchars($prefillName) ?>">
                </div>
                <div>
                  <div class="contact-label">Company (Optional)</div>
                  <input class="contact-input" name="company">
                </div>
              </div>

              <div class="contact-grid2">
                <div>
                  <div class="contact-label">Email</div>
                  <input class="contact-input" type="email" name="email" required value="<?= htmlspecialchars($prefillEmail) ?>">
                </div>
                <div>
                  <div class="contact-label">Subject</div>
                  <input class="contact-input" name="subject" required>
                </div>
              </div>

              <div>
                <div class="contact-label">Message</div>
                <textarea class="contact-textarea" name="message" required></textarea>
              </div>

              <div class="contact-actions">
                <button type="submit" class="btn-primary">Submit</button>
              </div>
            </form>
          </div>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
