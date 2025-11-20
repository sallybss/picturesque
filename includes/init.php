<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$ENV = require __DIR__ . '/env.php';
define('BASE_PATH', rtrim($ENV['base_path'] ?? '', '/'));  

// always project-relative (no leading slash)
function url(string $path = ''): string {
    return './' . ltrim($path, '/');
}

function asset(string $path): string {
    return url($path);
}

function img_from_db(?string $v): string {
    if (!$v) return asset('public/images/placeholder-photo.jpg');
    // if full http(s) or already project-relative path provided, pass through
    if (preg_match('~^https?://~i', $v)) return $v;
    if ($v[0] === '.') return $v;
    if ($v[0] === '/') return '.' . $v; // turn absolute into project-relative
    // DB contains only filenames -> point to uploads/
    return asset('uploads/' . $v);
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

require_once __DIR__ . '/base_repository.php';
require_once __DIR__ . '/profile_repository.php';
require_once __DIR__ . '/picture_repository.php';
require_once __DIR__ . '/comment_repository.php';
require_once __DIR__ . '/like_repository.php';
require_once __DIR__ . '/pages_repository.php';
require_once __DIR__ . '/search_repository.php';
require_once __DIR__ . '/featured_repository.php';
