<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$ENV = require __DIR__ . '/env.php';
define('BASE_PATH', rtrim($ENV['base_path'] ?? '', '/'));  

function url(string $path = ''): string {
    return (BASE_PATH === '' ? '' : BASE_PATH) . '/' . ltrim($path, '/');
}

function asset(string $path): string {
    return url($path);
}

function img_from_db(?string $v): string {
    if (!$v) return asset('public/images/placeholder-photo.jpg'); 
    if ($v[0] === '/') return url($v);
    return url('uploads/' . ltrim($v, '/'));
}
function redirect(string $to): void {
    if (preg_match('~^https?://~i', $to)) {
        header('Location: ' . $to);
    } else {
        header('Location: ' . url($to));
    }
    exit;
}




require_once __DIR__ . '/flash.php';
require_once __DIR__ . '/db_class.php';
require_once __DIR__ . '/auth_class.php';
require_once __DIR__ . '/paths_class.php';
require_once __DIR__ . '/sidebar.php';

require_once __DIR__ . '/profile_repository.php';
require_once __DIR__ . '/picture_repository.php';
require_once __DIR__ . '/comment_repository.php';
require_once __DIR__ . '/like_repository.php';
require_once __DIR__ . '/pages_repository.php';
require_once __DIR__ . '/search_repository.php';
require_once __DIR__ . '/featured_repository.php';
