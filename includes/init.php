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


function app_base_path(): string {
   
    $script = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');    
    $app    = rtrim(dirname($script), '/\\');                     
    return ($app === '' ? '/' : $app . '/');
}

function redirect(string $to): void {
    header('Location: ' . app_base_path() . ltrim($to, '/'));
    exit;
}
