<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
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

