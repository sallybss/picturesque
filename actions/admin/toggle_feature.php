<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../index.php');
    exit;
}

$me = Auth::requireUserOrRedirect('../../auth/login.php');

if (!check_csrf($_POST['csrf'] ?? null)) {
    set_flash('err', 'Bad CSRF token.');
    header('Location: ../../index.php');
    exit;
}

$profiles = new ProfileRepository();
$isAdmin  = strtolower(trim($profiles->getHeader($me)['role'] ?? '')) === 'admin';

if (!$isAdmin) {
    set_flash('err', 'Forbidden.');
    header('Location: ../../index.php');
    exit;
}

$pictureId = (int)($_POST['picture_id'] ?? 0);
$mode      = $_POST['mode'] ?? 'toggle';

if ($pictureId <= 0) {
    set_flash('err', 'Missing picture.');
    header('Location: ../../index.php');
    exit;
}

$repo = new FeaturedRepository();

if ($mode === 'unpin') {
    $repo->removeThisWeek($pictureId);
    set_flash('ok', 'Removed from Hot this week.');
} else {
    if ($repo->isFeaturedThisWeek($pictureId)) {
        $repo->removeThisWeek($pictureId);
        set_flash('ok', 'Removed from Hot this week.');
    } else {
        if ($repo->countThisWeek() >= 10) {
            set_flash('err', 'You already have 10 hot picks for this week.');
        } else {
            $repo->addThisWeek($pictureId, (int)$me);
            set_flash('ok', 'Pinned to Hot this week!');
        }
    }
}

header('Location: ../../index.php');
exit;
