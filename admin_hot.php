<?php
require_once __DIR__.'/includes/init.php';
$me = Auth::requireUserOrRedirect('./home_guest.php');
$profiles = new ProfileRepository();
$meRow = $profiles->getHeader($me);
$isAdmin = strtolower(trim($meRow['role'] ?? '')) === 'admin';
if (!$isAdmin) { http_response_code(403); exit('Forbidden'); }

$weekParam = $_GET['week'] ?? ''; 
$week = $weekParam ? DateTime::createFromFormat('Y-m-d', $weekParam) : new DateTime('monday next week');

$picturesRepo = new PictureRepository();
$recent = $picturesRepo->feed($me, '', '', 'new'); 

$featuredRepo = new FeaturedRepository();
$current = $featuredRepo->listForWeek($week); 
$currentIds = array_map(fn($r)=>$r['id'], $current);
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Admin · Hot Picks</title>
<link rel="stylesheet" href="./public/css/main.css">
<style>
.grid-choices {display:grid; gap:12px; grid-template-columns:repeat(auto-fill,minmax(180px,1fr));}
.choice {border:1px solid #eee; border-radius:12px; overflow:hidden; padding:6px;}
.choice img{width:100%; height:120px; object-fit:cover; border-radius:8px;}
.topbar{display:flex; align-items:center; gap:12px; margin-bottom:12px;}
.count-badge{margin-left:auto; font-size:12px; color:#666;}
</style>
</head>
<body>
<?php render_sidebar(['isAdmin'=>true]); ?>
<main class="content">
  <h1>🔥 Set Hot Picks</h1>

  <form class="topbar" method="get" action="admin_hot.php">
    <label>Week (Monday): <input type="date" name="week" value="<?= htmlspecialchars($week->format('Y-m-d')) ?>"></label>
    <button type="submit">Load</button>
    <span class="count-badge">Max 10 picks</span>
  </form>

  <form method="post" action="./actions/save_hot.php">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <input type="hidden" name="week" value="<?= htmlspecialchars($week->format('Y-m-d')) ?>">

    <div class="grid-choices">
      <?php foreach ($recent as $p):
        $cover = !empty($p['pic_url']) ? (new Paths())->uploads . htmlspecialchars($p['pic_url'])
                                       : './public/img/placeholder-photo.jpg';
        $checked = in_array($p['id'], $currentIds, true) ? 'checked' : '';
      ?>
      <label class="choice">
        <img src="<?= $cover ?>" alt="">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:6px;">
          <span style="font-size:12px;"><?= htmlspecialchars($p['pic_title'] ?? 'Untitled') ?></span>
          <input type="checkbox" name="picture_id[]" value="<?= (int)$p['id'] ?>" <?= $checked ?>>
        </div>
      </label>
      <?php endforeach; ?>
    </div>

    <p style="margin-top:12px;">
      <button type="submit">Save (max 10)</button>
    </p>
  </form>
</main>
</body>
</html>
