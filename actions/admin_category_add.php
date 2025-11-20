<?php
require_once __DIR__ . '/../includes/init.php';

$me = Auth::requireUserOrRedirect('../auth/login.php');
$profiles = new ProfileRepository();
$meRow = $profiles->getHeader($me);

if (strtolower($meRow['role'] ?? '') !== 'admin') {
    set_flash('err', 'Admins only.');
    redirect('../index.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !check_csrf($_POST['csrf'] ?? '')) {
    set_flash('err', 'Invalid request.');
    redirect('../admin_categories.php');
}

$name = trim($_POST['name'] ?? '');
if ($name === '') {
    set_flash('err', 'Name required.');
    redirect('../admin_categories.php');
}

$slug = slugify($name); 

$catsRepo = new CategoriesRepository();
$ok = $catsRepo->create($name, $slug);

if ($ok) {
    set_flash('ok', 'Category added.');
} else {
    set_flash('err', 'Could not add (duplicate name/slug?).');
}

redirect('../admin_categories.php');
