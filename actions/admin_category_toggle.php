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

$id = (int)($_POST['category_id'] ?? 0);
if ($id <= 0) {
    set_flash('err', 'Bad id.');
    redirect('../admin_categories.php');
}

$catsRepo = new CategoriesRepository();
$catsRepo->toggleActive($id);

set_flash('ok', 'Toggled.');
redirect('../admin_categories.php');
