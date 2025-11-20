<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// load config from core/env.php
$ENV = require __DIR__ . '/core/env.php';
define('BASE_PATH', rtrim($ENV['base_path'] ?? '', '/'));

// always project-relative (no leading slash)
function url(string $path = ''): string {
    return './' . ltrim($path, '/');
}

function asset(string $path): string {
    return url($path);
}

function img_from_db(?string $v): string {
    // Fallback placeholder
    if (!$v) {
        return asset('public/images/placeholder-photo.jpg');
    }

    // If DB already has full http(s) url → use as-is
    if (preg_match('~^https?://~i', $v)) {
        return $v;
    }

    // If DB already has a project-relative path like "./something"
    if ($v[0] === '.') {
        return $v;
    }

    // If DB has a leading "/" (absolute from web root), make it project-relative "./..."
    if ($v[0] === '/') {
        return '.' . $v;
    }

    // If DB already starts with "uploads/" or "public/uploads/", don't prepend again
    if (strpos($v, 'uploads/') === 0 || strpos($v, 'public/uploads/') === 0) {
        return asset($v);          // e.g. "uploads/abc.jpg" -> "./uploads/abc.jpg"
    }

    // Otherwise, treat it as just a filename and store under uploads/
    return asset('uploads/' . $v); // e.g. "abc.jpg" -> "./uploads/abc.jpg"
}


function redirect(string $to): void {
    if (preg_match('~^https?://~i', $to)) {
        header('Location: ' . $to);
    } else {
        header('Location: ' . url($to));
    }
    exit;
}

// --- core classes ---
require_once __DIR__ . '/core/flash.php';
require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/db_class.php';
require_once __DIR__ . '/core/auth_class.php';
require_once __DIR__ . '/core/paths_class.php';

// --- repositories ---
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
