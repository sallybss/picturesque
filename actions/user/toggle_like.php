<?php
require_once __DIR__ . '/../../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../../index.php');
}

if (!check_csrf($_POST['csrf'] ?? null)) {
    redirect('../../index.php');
}

$me  = Auth::requireUserOrRedirect('../../auth/login.php');
$pic = (int)($_POST['picture_id'] ?? 0);

if ($pic <= 0) {
    redirect('../../index.php');
}

$likes = new LikeRepository();
$likes->toggle($pic, $me);

$back = $_SERVER['HTTP_REFERER'] ?? '../../index.php';
$back .= '#pic-' . $pic;

redirect($back);
