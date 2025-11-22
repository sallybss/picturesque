<?php
require_once __DIR__ . '/../../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../../create.php');
}

if (isset($_POST['csrf']) && !check_csrf($_POST['csrf'])) {
    set_flash('err','Invalid request.');
    redirect('../../create.php');
}

$me = Auth::requireUserOrRedirect('../../auth/login.php');
$title = trim($_POST['picture_title'] ?? $_POST['title'] ?? '');
$title = mb_substr($title, 0, 50); 
$desc  = trim($_POST['picture_description'] ?? $_POST['desc'] ?? '');
$desc  = mb_substr($desc, 0, 250);
$file  = $_FILES['photo'] ?? null;
$catId = (int)($_POST['category_id'] ?? 0);
$redirect = isset($_POST['redirect']) ? (string)$_POST['redirect'] : '';

if ($redirect === '' && !empty($_SERVER['HTTP_REFERER'])) {
    $path = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH) ?? '';
    if (preg_match('~/(mada|sally)(?:/|$)~', $path, $m)) {
        $redirect = '../../' . $m[1] . '/index.php';
    }
}

if ($redirect === '') {
    $redirect = '../../index.php';
}

if ($catId <= 0) {
    set_flash('err', 'Please choose a category.');
    redirect('../../create.php');
}

$catsRepo = new CategoriesRepository();
if (!$catsRepo->isActive($catId)) {
    set_flash('err', 'Invalid category.');
    redirect('../../create.php');
}

if ($title === '' || !$file || empty($file['name'])) {
    set_flash('err', 'Please select a photo and enter a title.');
    redirect('../../create.php');
}

if (!isset($file['error'])) {
    set_flash('err', 'No file field named "photo" was submitted.');
    redirect('../../create.php');
}

$code = $file['error'];
if ($code !== UPLOAD_ERR_OK) {
    $map = [
        UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds upload_max_filesize in php.ini.',
        UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds MAX_FILE_SIZE in the HTML form.',
        UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder (upload_tmp_dir).',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
    ];
    $msg = $map[$code] ?? ('Unknown upload error: '.$code);
    set_flash('err', 'Upload failed: ' . $msg);
    redirect('../../create.php');
}

$tmp = $file['tmp_name'];
if (!is_uploaded_file($tmp)) {
    set_flash('err', 'No file uploaded.');
    redirect('../../create.php');
}

$finfo   = new finfo(FILEINFO_MIME_TYPE);
$mime    = $finfo->file($tmp);
$allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];
if (!isset($allowed[$mime])) {
    set_flash('err', 'Only JPG/PNG/GIF/WEBP allowed.');
    redirect('../../create.php');
}

$uploadDir = dirname(__DIR__) . '/../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

$ext      = $allowed[$mime];
$basename = time() . '_' . bin2hex(random_bytes(4));
$name     = $basename . '.' . $ext;
$dest     = $uploadDir . $name;

if (!move_uploaded_file($tmp, $dest)) {
    set_flash('err', 'Could not save the file.');
    redirect('../../create.php');
}
@chmod($dest, 0644);

$storedPath = $name;

$repo = new PictureRepository();
$repo->create($me, $title, $desc, $storedPath, $catId);

set_flash('ok', 'Picture posted!');
redirect($redirect);
