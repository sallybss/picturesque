<?php
require_once __DIR__ . '/../../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !check_csrf($_POST['csrf'] ?? null)) {
    set_flash('err', 'Invalid request.');
    redirect('../../admin.php');
}

$me = Auth::requireAdminOrRedirect('../../index.php');

$pictureId = (int)($_POST['picture_id'] ?? 0);
if ($pictureId <= 0) {
    set_flash('err','Missing picture.');
    redirect('../../admin.php');
}

$repo = new PictureRepository();
$row = $repo->getOwnerAndUrl($pictureId);

if (!$row) {
    set_flash('err','Picture not found.');
    redirect('../../admin.php');
}

$ok = $repo->deleteById($pictureId);
if (!$ok) {
    set_flash('err', 'Could not delete picture (database error).');
    redirect('../../admin.php');
}

$file = dirname(__DIR__) . '/../uploads/' . $row['picture_url'];
if (is_file($file)) {
    @unlink($file);
}

set_flash('ok','Picture deleted.');
header('Location: ../../admin_user_posts.php?id=' . (int)$row['owner_id'] . '&afterDelete=1');
exit;
