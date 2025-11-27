<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/views/topbar.php';
require_once __DIR__ . '/includes/views/sidebar.php';

$me = Auth::requireUserOrRedirect('./auth/login.php');

$profiles = new ProfileRepository();
$meRow    = $profiles->getHeader($me);
$isAdmin  = strtolower(trim($meRow['role'] ?? '')) === 'admin';

$picture_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($picture_id <= 0) {
  header('Location: ./index.php');
  exit;
}

$pictures = new PictureRepository();
$pic = $pictures->getOneWithCountsAndAuthor($picture_id);
if (!$pic) {
  header('Location: ./index.php');
  exit;
}

$commentsRepo = new CommentRepository();
$rows = $commentsRepo->listForPictureWithAuthors($picture_id);

$byId = [];
foreach ($rows as $r) {
  $r['children'] = [];
  $byId[(int)$r['comment_id']] = $r;
}

$rootComments = [];
foreach ($byId as $id => &$node) {
  $pid = $node['parent_id'];
  if ($pid === null) {
    $rootComments[] = &$node;
  } else {
    $pid = (int)$pid;
    if (isset($byId[$pid])) {
      $byId[$pid]['children'][] = &$node;
    } else {
      $rootComments[] = &$node;
    }
  }
}
unset($node);

function render_comment(array $c, int $depth, int $picture_id, bool $isAdmin): void
{
    $avatar = img_from_db($c['avatar_photo']);
    $d = max(0, min(4, $depth));
    ?>
    <div class="comment" id="c-<?= (int)$c['comment_id'] ?>" data-depth="<?= $d ?>">
      <div class="c-head">
        <div class="c-head-left">
          <img class="c-avatar" src="<?= $avatar ?>" alt="">
          <b class="c-name"><?= htmlspecialchars($c['display_name']) ?></b>
          <span class="c-time"><?= htmlspecialchars(substr($c['created_at'], 0, 16)) ?></span>
        </div>

        <?php if ($isAdmin): ?>
          <form method="post" action="./actions/admin/comment_delete.php" style="display:inline;">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="comment_id" value="<?= (int)$c['comment_id'] ?>">
            <input type="hidden" name="picture_id" value="<?= (int)$picture_id ?>">
            <button class="iconbtn danger"
                    onclick="return confirm('Delete this comment? Replies will be removed too.');">
              Delete
            </button>
          </form>
        <?php endif; ?>
      </div>

      <div class="c-body"><?= nl2br(htmlspecialchars($c['comment_content'])) ?></div>

      <div class="c-actions">
        <button type="button" class="link-btn js-reply" data-target="rf-<?= (int)$c['comment_id'] ?>">Reply</button>
      </div>

      <form id="rf-<?= (int)$c['comment_id'] ?>" class="reply-form" method="post" action="./actions/user/post_comment.php" style="display:none;">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" name="picture_id" value="<?= (int)$picture_id ?>">
        <input type="hidden" name="parent_comment_id" value="<?= (int)$c['comment_id'] ?>">
        <textarea name="comment_content" rows="2" class="input" placeholder="Write a reply…" required maxlength="500"></textarea>
        <div class="reply-actions"><button type="submit" class="btn">Reply</button></div>
      </form>

      <?php if (!empty($c['children'])): ?>
        <div class="c-children">
          <?php foreach ($c['children'] as $child) render_comment($child, $depth + 1, $picture_id, $isAdmin); ?>
        </div>
      <?php endif; ?>
    </div>
    <?php
}

$cssPath = __DIR__ . '/public/css/main.css';
$ver = file_exists($cssPath) ? filemtime($cssPath) : time();

// --- Comment rate limit modal data ---
$commentLimitSeconds = 0;

if (
    isset($_SESSION['comment_rate_limit_until'], $_SESSION['comment_rate_limit_picture']) &&
    (int)$_SESSION['comment_rate_limit_picture'] === $picture_id
) {
    $commentLimitSeconds = (int)$_SESSION['comment_rate_limit_until'] - time();

    if ($commentLimitSeconds <= 0) {
        unset($_SESSION['comment_rate_limit_until'], $_SESSION['comment_rate_limit_picture']);
        $commentLimitSeconds = 0;
    } elseif ($commentLimitSeconds > 60) {
        $commentLimitSeconds = 60;
    }
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($pic['picture_title'] ?? 'Picture') ?> · Picturesque</title>
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
        <div class="top-actions">
          <button class="hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false">☰</button>
          <?php render_topbar_userbox($meRow); ?>
        </div>
      </div>

      <div class="single-wrap">
        <div class="card">
          <img src="<?= img_from_db($pic['picture_url']) ?>" alt="">
          <div class="pad">
            <h2 class="title"><?= htmlspecialchars($pic['picture_title']) ?></h2>
            <?php if (!empty($pic['picture_description'])): ?>
              <p class="muted"><?= htmlspecialchars($pic['picture_description']) ?></p>
            <?php endif; ?>
            <div class="counts">
              by <b><?= htmlspecialchars($pic['display_name']) ?></b> · ❤ <?= (int)$pic['like_count'] ?> · 💬 <?= (int)$pic['comment_count'] ?>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="pad">
            <div class="topbar">
              <h3 class="subtitle">Comments (<?= (int)$pic['comment_count'] ?>)</h3>
            </div>
            <form class="comment-form" method="post" action="./actions/user/post_comment.php">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
              <input type="hidden" name="picture_id" value="<?= (int)$picture_id ?>">

              <div class="contact-label" style="display:flex; justify-content:space-between; align-items:center;">
                <span>Write a comment…</span>
                <span id="commentCount" class="field-counter">0 / 500</span>
              </div>

              <textarea
                name="comment_content"
                id="mainComment"
                rows="3"
                class="input"
                placeholder="Write a comment…"
                maxlength="500"
                required></textarea>

              <p class="note" style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                You can post up to <strong>2 comments</strong> per minute.
              </p>

              <div class="form-actions"><button type="submit" class="btn">Post</button></div>
            </form>

            <div id="comments">
              <?php if (!$rootComments): ?>
                <p class="muted">No comments yet. Be the first!</p>
              <?php else: foreach ($rootComments as $root) render_comment($root, 0, $picture_id, $isAdmin);
              endif; ?>
            </div>
          </div>
        </div>
      </div>
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

      document.addEventListener('click', function(e) {
        const btn = e.target.closest('.js-reply');
        if (!btn) return;

        const formId = btn.dataset.target;
        if (!formId) return;

        const f = document.getElementById(formId);
        if (!f) return;

        if (!f.style.display || f.style.display === 'none') {
          f.style.display = 'block';
        } else {
          f.style.display = 'none';
        }
      });
    })();

    document.addEventListener('DOMContentLoaded', () => {
      const COMMENT_MAX = 500;
      const textarea = document.getElementById('mainComment');
      const counter = document.getElementById('commentCount');

      if (!textarea || !counter) return;

      function update() {
        let text = textarea.value;
        if (text.length > COMMENT_MAX) {
          text = text.slice(0, COMMENT_MAX);
          textarea.value = text;
        }
        counter.textContent = `${text.length} / ${COMMENT_MAX}`;

        if (text.length >= COMMENT_MAX) {
          textarea.classList.add('at-limit');
          counter.classList.add('at-limit');
        } else {
          textarea.classList.remove('at-limit');
          counter.classList.remove('at-limit');
        }
      }

      textarea.addEventListener('input', update);
      update();
    });
  </script>

  <!-- Comment rate-limit modal -->
  <div
    id="commentLimitModal"
    data-seconds-left="<?= (int)$commentLimitSeconds ?>"
    style="
      display: <?= $commentLimitSeconds > 0 ? 'flex' : 'none' ?>;
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.55);
      align-items: center;
      justify-content: center;
      z-index: 9999;
    "
  >
    <div
      style="
        background: #ffffff;
        border-radius: 12px;
        padding: 24px 20px 18px;
        max-width: 420px;
        width: 90%;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.35);
        font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      "
    >
      <h2 style="font-size: 1.2rem; margin: 0 0 8px; color: #111827;">
        Too many comments
      </h2>

      <p style="margin: 0 0 8px; color: #4b5563; font-size: 0.95rem;">
        You reached the limit of <strong>2 comments per minute</strong>.
      </p>

      <p style="margin: 0 0 8px; color: #374151; font-size: 0.95rem;">
        Next comment allowed in
        <strong><span id="commentCountdown">00:00</span></strong>.
      </p>

      <p style="margin: 0 0 12px; color: #6b7280; font-size: 0.8rem;">
        This helps us reduce spam and keep conversations clean. ✨
      </p>

      <div style="text-align: right;">
        <button
          type="button"
          id="commentLimitClose"
          class="btn"
          style="padding: 6px 14px; font-size: 0.9rem;"
        >
          OK
        </button>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const modal = document.getElementById('commentLimitModal');
      if (!modal) return;

      let remaining = parseInt(modal.dataset.secondsLeft || '0', 10);
      const countdownEl = document.getElementById('commentCountdown');
      const closeBtn    = document.getElementById('commentLimitClose');

      function formatSeconds(sec) {
        const m = Math.floor(sec / 60);
        const s = sec % 60;
        return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
      }

      function tick() {
        if (!countdownEl) return;

        if (remaining <= 0) {
          modal.style.display = 'none';
          return;
        }

        countdownEl.textContent = formatSeconds(remaining);
        remaining -= 1;
        setTimeout(tick, 1000);
      }

      if (remaining > 0) {
        modal.style.display = 'flex';
        tick();
      } else {
        modal.style.display = 'none';
      }

      closeBtn?.addEventListener('click', () => {
        modal.style.display = 'none';
      });
    });
  </script>

</body>

</html>