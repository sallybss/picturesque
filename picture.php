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
  } elseif (isset($byId[(int)$pid])) {
    $byId[(int)$pid]['children'][] = &$node;
  } else {
    $rootComments[] = &$node;
  }
}
unset($node);

function render_comment(array $c, int $depth, int $picture_id, bool $isAdmin): void {
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

      <form id="rf-<?= (int)$c['comment_id'] ?>" class="reply-form" method="post"
            action="./actions/user/post_comment.php" style="display:none;">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" name="picture_id" value="<?= (int)$picture_id ?>">
        <input type="hidden" name="parent_comment_id" value="<?= (int)$c['comment_id'] ?>">
        <textarea name="comment_content" rows="2" class="input"
                  placeholder="Write a reply…" maxlength="500" required></textarea>
        <div class="reply-actions">
          <button type="submit" class="btn-primary">Reply</button>
        </div>
      </form>

      <?php if (!empty($c['children'])): ?>
        <div class="c-children">
          <?php foreach ($c['children'] as $child) {
            render_comment($child, $depth + 1, $picture_id, $isAdmin);
          } ?>
        </div>
      <?php endif; ?>
    </div>
<?php
}

$cssPath = __DIR__ . '/public/css/main.css';
$ver = file_exists($cssPath) ? filemtime($cssPath) : time();

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
    <?php if ($m = get_flash('ok')): ?><div class="flash flash-ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
    <?php if ($m = get_flash('err')): ?><div class="flash flash-err"><?= htmlspecialchars($m) ?></div><?php endif; ?>
  </div>

  <div class="layout">
    <?php render_sidebar(['isAdmin' => $isAdmin]); ?>

    <main class="content">
      <div class="content-top">
        <div class="content-spacer"></div>

        <div class="top-actions">
          <button class="hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false">☰</button>

          <div class="user-settings">
            <?php render_topbar_userbox($meRow); ?>

            <button class="user-menu-toggle" id="userMenuToggle" aria-label="Display settings" aria-expanded="false">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#ffffffff" viewBox="0 0 256 256">
                <path d="M64,105V40a8,8,0,0,0-16,0v65a32,32,0,0,0,0,62v49a8,8,0,0,0,16,0V167a32,32,0,0,0,0-62Zm-8,47a16,16,0,1,1,16-16A16,16,0,0,1,56,152Zm80-95V40a8,8,0,0,0-16,0V57a32,32,0,0,0,0,62v97a8,8,0,0,0,16,0V119a32,32,0,0,0,0-62Zm-8,47a16,16,0,1,1,16-16A16,16,0,0,1,128,104Zm104,64a32.06,32.06,0,0,0-24-31V40a8,8,0,0,0-16,0v97a32,32,0,0,0,0,62v17a8,8,0,0,0,16,0V199A32.06,32.06,0,0,0,232,168Zm-32,16a16,16,0,1,1,16-16A16,16,0,0,1,200,184Z"></path>
              </svg>
            </button>

            <div class="user-menu" id="userMenu">
              <div class="user-menu-section">
                <span class="user-menu-title">Theme</span>
                <button type="button" class="user-menu-item" data-theme="light">Light mode</button>
                <button type="button" class="user-menu-item" data-theme="dark">Dark mode</button>
              </div>

              <div class="user-menu-section">
                <span class="user-menu-title">Font size</span>
                <button type="button" class="user-menu-item" data-font="small">Small</button>
                <button type="button" class="user-menu-item" data-font="medium">Medium</button>
                <button type="button" class="user-menu-item" data-font="large">Large</button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="single-wrap">

        <div class="card">
        <img src="<?= htmlspecialchars(img_from_db($pic['picture_url'])) ?>" alt="">

          <div class="pad">
            <h2 class="title"><?= htmlspecialchars($pic['picture_title']) ?></h2>

            <?php if (!empty($pic['picture_description'])): ?>
              <p class="muted"><?= htmlspecialchars($pic['picture_description']) ?></p>
            <?php endif; ?>

            <div class="counts">
              by <b><?= htmlspecialchars($pic['display_name']) ?></b>
              &nbsp;·&nbsp; ❤ <?= (int)$pic['like_count'] ?>
              &nbsp;·&nbsp; 💬 <?= (int)$pic['comment_count'] ?>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="pad">

            <div class="topbar" style="margin-bottom:12px;">
              <h3 class="subtitle">Comments (<?= (int)$pic['comment_count'] ?>)</h3>
            </div>

            <form class="comment-form" method="post" action="./actions/user/post_comment.php">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
              <input type="hidden" name="picture_id" value="<?= (int)$picture_id ?>">

              <div class="label-row">
                <label class="label">Write a comment…</label>
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

              <p class="note" style="font-size:12px; margin-top:4px;">
                You can post up to <strong>2 comments per minute</strong>.
              </p>

              <div class="form-actions" style="margin-top:12px;">
                <button type="submit" class="btn-primary">Post</button>
              </div>
            </form>

            <div id="comments" style="margin-top:22px;">
              <?php if (!$rootComments): ?>
                <p class="muted">No comments yet. Be the first!</p>
              <?php else:
                foreach ($rootComments as $root) render_comment($root, 0, $picture_id, $isAdmin);
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
      document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMenu(); });

      document.addEventListener('click', e => {
        const btn = e.target.closest('.js-reply');
        if (!btn) return;

        const form = document.getElementById(btn.dataset.target);
        if (!form) return;

        form.style.display = form.style.display === 'block' ? 'none' : 'block';
      });
    })();

    document.addEventListener('DOMContentLoaded', () => {
      const MAX = 500;
      const textarea = document.getElementById('mainComment');
      const counter  = document.getElementById('commentCount');

      if (!textarea || !counter) return;

      function update() {
        let text = textarea.value;
        if (text.length > MAX) text = textarea.value = text.slice(0, MAX);

        counter.textContent = `${text.length} / ${MAX}`;

        if (text.length >= MAX) {
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

  <div
    class="rate-modal-backdrop"
    id="commentLimitModal"
    data-seconds-left="<?= (int)$commentLimitSeconds ?>"
    <?= $commentLimitSeconds > 0 ? '' : 'hidden' ?>>
    <div class="rate-modal">
      <h2>Too many comments</h2>
      <p>You reached the limit of <strong>2 comments per minute</strong>.</p>
      <p>
        Next comment allowed in
        <strong><span id="commentCountdown">00:00</span></strong>.
      </p>
      <p class="rate-limit-note">
        This helps us reduce spam and keep conversations clean.
      </p>
      <div style="text-align:right; margin-top: 10px;">
        <button type="button" id="commentLimitClose" class="btn-primary">OK</button>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const modal = document.getElementById('commentLimitModal');
      if (!modal) return;

      let remaining = parseInt(modal.dataset.secondsLeft || '0', 10);
      const elCountdown = document.getElementById('commentCountdown');
      const btnClose    = document.getElementById('commentLimitClose');

      function format(sec) {
        const m = Math.floor(sec / 60);
        const s = sec % 60;
        return `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
      }

      function tick() {
        if (!elCountdown) return;

        if (remaining <= 0) {
          modal.hidden = true;
          return;
        }

        elCountdown.textContent = format(remaining--);
        setTimeout(tick, 1000);
      }

      if (remaining > 0) {
        modal.hidden = false;
        tick();
      } else {
        modal.hidden = true;
      }

      btnClose?.addEventListener('click', () => {
        modal.hidden = true;
      });
    });

    (function() {
      const body = document.body;
      const menuToggle = document.getElementById('userMenuToggle');
      const menu = document.getElementById('userMenu');

      if (!menuToggle || !menu) return;

      const THEME_KEY = 'pq_theme';
      const FONT_KEY  = 'pq_font';

      function applyTheme(theme) {
        body.classList.remove('theme-light', 'theme-dark');
        body.classList.add('theme-' + theme);
        localStorage.setItem(THEME_KEY, theme);
      }

      function applyFont(size) {
        body.classList.remove('font-small', 'font-medium', 'font-large');
        body.classList.add('font-' + size);
        localStorage.setItem(FONT_KEY, size);
      }

      const savedTheme = localStorage.getItem(THEME_KEY) || 'light';
      const savedFont  = localStorage.getItem(FONT_KEY) || 'medium';
      applyTheme(savedTheme);
      applyFont(savedFont);

      function closeMenu() {
        menu.classList.remove('open');
        menuToggle.setAttribute('aria-expanded', 'false');
      }

      function openMenu() {
        menu.classList.add('open');
        menuToggle.setAttribute('aria-expanded', 'true');
      }

      menuToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        if (menu.classList.contains('open')) {
          closeMenu();
        } else {
          openMenu();
        }
      });

      document.addEventListener('click', (e) => {
        if (!menu.contains(e.target) && e.target !== menuToggle) {
          closeMenu();
        }
      });

      menu.querySelectorAll('[data-theme]').forEach(btn => {
        btn.addEventListener('click', () => {
          applyTheme(btn.getAttribute('data-theme'));
        });
      });

      menu.querySelectorAll('[data-font]').forEach(btn => {
        btn.addEventListener('click', () => {
          applyFont(btn.getAttribute('data-font'));
        });
      });
    })();
  </script>

</body>
</html>
