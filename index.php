<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/views/topbar.php';
require_once __DIR__ . '/includes/views/sidebar.php';


function url_from_db(string $path): string
{
  return url($path);
}

$me = Auth::requireUserOrRedirect('./home_guest.php');

$paths    = new Paths();
$profiles = new ProfileRepository();
$meRow    = $profiles->getHeader($me);
$isAdmin  = strtolower(trim($meRow['role'] ?? '')) === 'admin';

$q    = trim($_GET['q'] ?? '');
$cat  = trim($_GET['cat'] ?? '');
$sort = $_GET['sort'] ?? 'new';
if (!in_array($sort, ['new', 'old'], true)) $sort = 'new';

$catsRepo = new CategoriesRepository();
$cats     = $catsRepo->listActive();

$raw = strtolower(trim($q));
if ($raw !== '') {
  if (substr($raw, 0, 1) === '#')    $raw = substr($raw, 1);
  if (substr($raw, 0, 4) === 'cat:') $raw = substr($raw, 4);
  foreach ($cats as $c) {
    $name = strtolower(trim($c['name']));
    $slug = strtolower(trim($c['slug']));
    if ($raw === $name || $raw === $slug) {
      $cat = $slug;
      $q = '';
      break;
    }
  }
}

$search  = new SearchRepository();
$people  = $q !== '' ? $search->peopleByDisplayNameLike('%' . ltrim($q, '@') . '%') : [];

$picturesRepo = new PictureRepository();
$pictures     = $picturesRepo->feed($me, $q, $cat, $sort);

$featuredRepo = new FeaturedRepository();
$hot          = $featuredRepo->listForWeek();
$hotIds       = array_column($hot, 'pic_id');
$hotIdsSet    = array_fill_keys($hotIds, true);
$hotCount     = count($hotIds);
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Home · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $cssPath = __DIR__ . '/public/css/main.css';
  $ver = file_exists($cssPath) ? filemtime($cssPath) : time(); ?>
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
        <form method="get" action="index.php" class="search-wrap">
          <?php if ($cat !== ''):  ?><input type="hidden" name="cat" value="<?= htmlspecialchars($cat)  ?>"><?php endif; ?>
          <?php if ($sort !== ''): ?><input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>"><?php endif; ?>
          <input class="search" name="q" list="search-suggest" value="<?= htmlspecialchars($q) ?>"
            placeholder="Search photos, username, or #category">
          <datalist id="search-suggest">
            <?php foreach ($cats as $c): ?>
              <option value="#<?= htmlspecialchars($c['slug']) ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </datalist>
          <button class="btn-primary search-btn" type="submit">Go</button>
        </form>

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

      <div class="controls-row">
        <div class="pills">
          <?php
          $qs = function (array $params) {
            return http_build_query(array_filter($params, fn($v) => $v !== '' && $v !== null));
          };
          ?>
          <a class="pill<?= $cat === '' ? ' is-selected' : '' ?>"
            href="index.php?<?= $qs(['q' => $q, 'sort' => $sort]) ?>">All</a>

          <?php foreach ($cats as $c): ?>
            <?php $href = 'index.php?' . $qs(['cat' => $c['slug'], 'q' => $q, 'sort' => $sort]); ?>
            <a class="pill<?= $cat === $c['slug'] ? ' is-selected' : '' ?>" href="<?= $href ?>">
              <?= htmlspecialchars($c['name']) ?>
            </a>
          <?php endforeach; ?>
        </div>

        <form class="filter-form" method="get" action="index.php">
          <?php if ($q   !== ''): ?><input type="hidden" name="q" value="<?= htmlspecialchars($q)   ?>"><?php endif; ?>
          <?php if ($cat !== ''): ?><input type="hidden" name="cat" value="<?= htmlspecialchars($cat) ?>"><?php endif; ?>
          <label for="sort" class="filter-label">Sort by:</label>
          <div class="filter-select-wrap">
            <select name="sort" id="sort" class="filter-select" onchange="this.form.submit()">
              <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Newest → Oldest</option>
              <option value="old" <?= $sort === 'old' ? 'selected' : '' ?>>Oldest → Newest</option>
            </select>
          </div>
        </form>
      </div>

      <?php if ($q !== '' && !empty($people)): ?>
        <h2 class="section-title">People</h2>
        <div class="people-row">
          <?php foreach ($people as $u):
            $avatar = !empty($u['avatar_photo']) ? url_from_db($u['avatar_photo']) : 'https://placehold.co/56x56?text=%20';
          ?>
            <a class="person" href="profile.php?id=<?= (int)$u['profile_id'] ?>">
              <img src="<?= $avatar ?>" alt="">
              <span><?= htmlspecialchars($u['display_name']) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($hot)): ?>
        <h2 class="section-title">🔥 Hot this week</h2>
        <div class="hot-row">
          <?php foreach ($hot as $p):
            $cover = img_from_db($p['pic_url']);
          ?>
            <a class="hot-card" href="picture.php?id=<?= (int)$p['pic_id'] ?>">
              <img src="<?= $cover ?>" alt="">
              <span class="hot-title"><?= htmlspecialchars($p['pic_title'] ?? 'Untitled') ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <section class="feed">
        <?php foreach ($pictures as $p): ?>
          <?php
          $picId        = (int)$p['pic_id'];
          $coverUrl     = img_from_db($p['pic_url']);
          $authorId     = isset($p['author_id']) ? (int)$p['author_id'] : ((int)($p['profile_id'] ?? 0));
          $authorName   = $p['author_display_name'] ?? $p['display_name'] ?? 'Unknown';
          $rawAvatar    = $p['author_avatar'] ?? $p['avatar_photo'] ?? '';
          $authorAvatar = $rawAvatar ? img_from_db($rawAvatar) : 'https://placehold.co/26x26?text=%20';
          ?>
          <article class="card" id="pic-<?= $picId ?>">
            <a class="card-cover" href="picture.php?id=<?= $picId ?>">
              <img src="<?= htmlspecialchars($coverUrl) ?>" alt="">
            </a>

            <div class="card-body">
              <?php if ($isAdmin):
                $isHot = isset($hotIdsSet[$p['pic_id']]);
                $disablePin = (!$isHot && $hotCount >= 10);
              ?>
                <form method="post" action="./actions/admin/toggle_feature.php" style="display:inline">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                  <input type="hidden" name="picture_id" value="<?= (int)$p['pic_id'] ?>">
                  <?php if ($isHot): ?>
                    <input type="hidden" name="mode" value="unpin">
                    <button type="submit" class="pill" style="margin-top:6px;">🔥 Unpin</button>
                  <?php else: ?>
                    <button type="submit" class="pill" style="margin-top:6px;" <?= $disablePin ? 'disabled' : '' ?>>
                      📌 Pin to Hot
                    </button>
                  <?php endif; ?>
                </form>
              <?php endif; ?>

              <div class="card-title">
                <a class="card-title-link" href="picture.php?id=<?= $picId ?>">
                  <?= htmlspecialchars(truncate_text($p['pic_title'] ?? '', 60)) ?>
                </a>
              </div>

              <?php if (!empty($p['pic_desc'])): ?>
                <div class="card-desc">
                  <?= htmlspecialchars(truncate_text($p['pic_desc'] ?? '', 140)) ?>
                </div>
              <?php endif; ?>

              <div class="author-row">
                <?php if ($authorId > 0): ?>
                  <a class="author-link" href="profile.php?id=<?= $authorId ?>">
                    <img class="author-avatar" src="<?= $authorAvatar ?>" alt="avatar of <?= htmlspecialchars($authorName) ?>">
                    <span class="author-name"><?= htmlspecialchars($authorName) ?></span>
                  </a>
                <?php else: ?>
                  <div class="author-link" style="pointer-events:none;">
                    <img class="author-avatar" src="<?= $authorAvatar ?>" alt="">
                    <span class="author-name"><?= htmlspecialchars($authorName) ?></span>
                  </div>
                <?php endif; ?>
              </div>

              <div class="meta">
                <span class="counts">
                  <form method="post" action="./actions/user/toggle_like.php" style="display:inline">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="picture_id" value="<?= (int)$p['pic_id'] ?>">
                    <button type="submit" class="iconbtn">
                      <?= ((int)$p['liked_by_me'] === 1) ? '❤' : '♡' ?> <?= (int)$p['like_count'] ?>
                    </button>
                  </form>
                  <a class="muted" href="picture.php?id=<?= (int)$p['pic_id'] ?>">💬 <?= (int)$p['comment_count'] ?></a>
                </span>
              </div>
            </div>
          </article>
        <?php endforeach; ?>

        <?php if (!$pictures): ?>
          <p class="muted">No pictures yet. Click <b>Create</b> to upload your first photo.</p>
        <?php endif; ?>
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
    (function() {
      const body = document.body;
      const menuToggle = document.getElementById('userMenuToggle');
      const menu = document.getElementById('userMenu');

      if (!menuToggle || !menu) return;

      const THEME_KEY = 'pq_theme';
      const FONT_KEY = 'pq_font';

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
      const savedFont = localStorage.getItem(FONT_KEY) || 'medium';
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