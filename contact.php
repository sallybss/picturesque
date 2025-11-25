<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/views/topbar.php';
require_once __DIR__ . '/includes/views/sidebar.php';

$me = Auth::requireUserOrRedirect('./auth/login.php');

$profiles = new ProfileRepository();
$meRow = $profiles->getById($me);
if (!$meRow) {
  header('Location: ./index.php');
  exit;
}

$row = $profiles->getLoginEmailAndRole($me);
$isAdmin      = strtolower(trim($meRow['role'] ?? '')) === 'admin';
$prefillName  = $meRow['display_name'] ?? '';
$prefillEmail = $row['login_email'] ?? '';   

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
  <div id="flash-stack" class="flash-stack">
    <?php if ($m = get_flash('ok')): ?>
      <div class="flash flash-ok"><?= htmlspecialchars($m) ?></div>
    <?php endif; ?>

    <?php if ($m = get_flash('err')): ?>
      <div class="flash flash-err"><?= htmlspecialchars($m) ?></div>
    <?php endif; ?>
  </div>

  <div class="layout">
    <?php render_sidebar(['isAdmin' => $isAdmin]); ?>

    <main class="content">
      <div class="content-top">
        <div class="top-actions" style="display:flex; align-items:center; justify-content:space-between; width:100%;">
          <button class="hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false">☰</button>
          <?php render_topbar_userbox($meRow); ?>
        </div>
      </div>

      <section class="contact-shell">
        <div class="contact-split">
          <div class="contact-media">
            <img src="./images/contact-side.jpg" alt="Contact"
              onerror="this.src='https://images.unsplash.com/photo-1519681393784-d120267933ba?q=80&w=1600&auto=format&fit=crop'">
          </div>

          <div class="contact-pane">
            <h1>Contact us</h1>
            <p class="sub">Don’t hesitate to get in touch for anything!</p>

            <form method="post" action="./actions/user/contact_submit.php" novalidate>
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

              <div class="contact-grid2">
                <div>
                  <div class="contact-label">Username</div>
                  <input class="contact-input" name="name" value="<?= htmlspecialchars($prefillName) ?>" readonly>
                </div>
                <div>
                  <div class="contact-label">Company (Optional)</div>
                  <input class="contact-input" name="company">
                </div>
              </div>

              <div class="contact-grid2">
                <div>
                  <div class="contact-label">Email</div>
                  <input class="contact-input" type="email" name="email" value="<?= htmlspecialchars($prefillEmail) ?>" readonly>
                </div>
                <div>
                  <div class="contact-label" style="display:flex; justify-content:space-between;">
                    Subject
                    <span id="subjectCount" class="field-counter">0 / 100</span>
                  </div>
                  <input class="contact-input" id="subjectInput" name="subject" maxlength="100" required>
                </div>
              </div>

              <div>
                <div class="contact-label" style="display:flex; justify-content:space-between;">
                  Message
                  <span id="msgCount" class="field-counter">0 / 500</span>
                </div>
                <textarea class="contact-textarea" id="msgInput" name="message" maxlength="500" required></textarea>
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

  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const flashes = document.querySelectorAll('.flash-stack .flash');
      if (!flashes.length) return;

      setTimeout(() => {
        flashes.forEach(flash => {
          flash.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
          flash.style.opacity = '0';
          flash.style.transform = 'translateY(-6px)';

          setTimeout(() => flash.remove(), 500);
        });
      }, 2000);
    });

    (function() {
      const body = document.body;
      const btn = document.getElementById('hamburger');
      const backdrop = document.getElementById('sidebarBackdrop');
      const closeBtn = document.getElementById('closeSidebar');

      function openMenu() {
        body.classList.add('sidebar-open');
        btn?.setAttribute('aria-expanded', 'true');
      }

      function closeMenu() {
        body.classList.remove('sidebar-open');
        btn?.setAttribute('aria-expanded', 'false');
      }

      function toggle() {
        body.classList.contains('sidebar-open') ? closeMenu() : openMenu();
      }

      btn?.addEventListener('click', toggle);
      backdrop?.addEventListener('click', closeMenu);
      closeBtn?.addEventListener('click', closeMenu);
      document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeMenu();
      });
    })();

    document.addEventListener("DOMContentLoaded", () => {

  const subject = document.getElementById("subjectInput");
  const subjectCount = document.getElementById("subjectCount");
  const SUBJECT_MAX = 100;

  if (subject) {
    subject.addEventListener("input", () => {
      let text = subject.value;
      if (text.length > SUBJECT_MAX) {
        text = text.slice(0, SUBJECT_MAX);
        subject.value = text;
      }
      subjectCount.textContent = `${text.length} / ${SUBJECT_MAX}`;

      if (text.length >= SUBJECT_MAX) {
        subject.classList.add("at-limit");
        subjectCount.classList.add("at-limit");
      } else {
        subject.classList.remove("at-limit");
        subjectCount.classList.remove("at-limit");
      }
    });
  }

  /* ===== MESSAGE (500 chars) ===== */
  const msg = document.getElementById("msgInput");
  const msgCount = document.getElementById("msgCount");
  const MSG_MAX = 500;

  if (msg) {
    msg.addEventListener("input", () => {
      let text = msg.value;
      if (text.length > MSG_MAX) {
        text = text.slice(0, MSG_MAX);
        msg.value = text;
      }
      msgCount.textContent = `${text.length} / ${MSG_MAX}`;

      if (text.length >= MSG_MAX) {
        msg.classList.add("at-limit");
        msgCount.classList.add("at-limit");
      } else {
        msg.classList.remove("at-limit");
        msgCount.classList.remove("at-limit");
      }
    });
  }
});

  </script>
</body>

</html>