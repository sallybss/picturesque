<?php
require __DIR__ . '/../includes/init.php';

$me = Auth::requireUserOrRedirect('../auth/login.php');
$profiles = new ProfileRepository();
$meRow = $profiles->getHeader($me);
if (strtolower($meRow['role'] ?? '') !== 'admin') { set_flash('err','Admins only.'); header('Location: ../index.php'); exit; }

if ($_SERVER['REQUEST_METHOD']!=='POST' || !check_csrf($_POST['csrf'] ?? '')) {
  set_flash('err','Invalid request.'); header('Location: ../admin_categories.php'); exit;
}

require __DIR__ . '/../includes/helpers.php'; // slugify()
$name = trim($_POST['name'] ?? '');
if ($name === '') { set_flash('err','Name required.'); header('Location: ../admin_categories.php'); exit; }

$slug = slugify($name);

try {
  $st = DB::get()->prepare("INSERT INTO categories (category_name, slug) VALUES (?, ?)");
  $st->bind_param('ss', $name, $slug);
  $st->execute();
  $st->close();
  set_flash('ok','Category added.');
} catch (mysqli_sql_exception $e) {
  set_flash('err','Could not add (duplicate name/slug?).');
}
header('Location: ../admin_categories.php');
