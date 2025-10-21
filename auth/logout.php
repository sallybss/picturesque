<?php
require __DIR__ . '/../includes/flash.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$_SESSION = [];
session_destroy();

set_flash('ok', 'You are logged out.');
header('Location: ./login.php');
exit;
