// actions/toggle_feature.php
<?php
require_once __DIR__ . '/../includes/init.php';

$me = Auth::requireUserOrRedirect('../home_guest.php');
if (!hash_equals(csrf_token(), $_POST['csrf'] ?? '')) { set_flash('err','Bad CSRF'); header('Location: '.($_POST['return_to'] ?? '../index.php')); exit; }

$isAdmin = strtolower(trim((new ProfileRepository())->getHeader($me)['role'] ?? '')) === 'admin';
if (!$isAdmin) { set_flash('err','Forbidden'); header('Location: '.($_POST['return_to'] ?? '../index.php')); exit; }

$pictureId = (int)($_POST['picture_id'] ?? 0);
$mode = $_POST['mode'] ?? 'toggle';

$repo = new FeaturedRepository();

if ($mode === 'unpin') {
  $repo->removeThisWeek($pictureId);
  set_flash('ok','Removed from Hot this week.');
} else {

  if ($repo->isFeaturedThisWeek($pictureId)) {
    $repo->removeThisWeek($pictureId);
    set_flash('ok','Removed from Hot this week.');
  } else {
    if ($repo->countThisWeek() >= 10) {
      set_flash('err','You already have 10 hot picks for this week.');
    } else {
      $repo->addThisWeek($pictureId, (int)$me);
      set_flash('ok','Pinned to Hot this week!');
    }
  }
}

header('Location: '.($_POST['return_to'] ?? '../index.php'));
