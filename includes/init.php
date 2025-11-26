<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$ENV = require __DIR__ . '/core/env.php';
define('BASE_PATH', rtrim($ENV['base_path'] ?? '', '/'));

function url(string $path = ''): string {
    return './' . ltrim($path, '/');
}

function asset(string $path): string {
    return url($path);
}

function img_from_db(?string $v): string {
    if (!$v) {
        return asset('public/images/placeholder-photo.jpg');
    }

    if (preg_match('~^https?://~i', $v)) {
        return $v;
    }

    if ($v[0] === '.') {
        return $v;
    }

    if ($v[0] === '/') {
        return '.' . $v;
    }

    if (strpos($v, 'uploads/') === 0 || strpos($v, 'public/uploads/') === 0) {
        return asset($v); 
    }

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


require_once __DIR__ . '/core/flash.php';
require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/db_class.php';
require_once __DIR__ . '/core/auth_class.php';
require_once __DIR__ . '/core/paths_class.php';


require_once __DIR__ . '/repositories/base_repository.php';
require_once __DIR__ . '/repositories/profile_repository.php';
require_once __DIR__ . '/repositories/picture_repository.php';
require_once __DIR__ . '/repositories/comment_repository.php';
require_once __DIR__ . '/repositories/like_repository.php';
require_once __DIR__ . '/repositories/pages_repository.php';
require_once __DIR__ . '/repositories/search_repository.php';
require_once __DIR__ . '/repositories/featured_repository.php';
require_once __DIR__ . '/repositories/categories_repository.php';
require_once __DIR__ . '/repositories/contact_repository.php';
require_once __DIR__ . '/repositories/password_reset_repository.php';
