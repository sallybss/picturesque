<?php
require __DIR__ . '/../includes/init.php';

$me = Auth::requireUserOrRedirect('../auth/login.php');
$profiles = new ProfileRepository();
$meRow = $profiles->getHeader($me);
if (strtolower($meRow['role'] ?? '') !== 'admin') { set_flash('err','Admins only.'); header('Location: ../index.php'); exit; }

if ($_SERVER['REQUEST_METHOD']!=='POST' || !check_csrf($_POST['csrf'] ?? '')) {
  set_flash('err','Invalid request.'); header('Location: ../admin_categories.php'); exit;
}

$id = (int)($_POST['category_id'] ?? 0);
if ($id <= 0) { set_flash('err','Bad id.'); header('Location: ../admin_categories.php'); exit; }

$st = DB::get()->prepare("UPDATE categories SET active = 1 - active WHERE category_id=?");
$st->bind_param('i', $id);
$st->execute();
$st->close();

set_flash('ok','Toggled.');
header('Location: ../admin_categories.php');
