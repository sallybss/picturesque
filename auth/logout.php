<?php
require_once __DIR__ . '/../includes/init.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$_SESSION = [];
session_destroy();

set_flash('ok', 'You are logged out.');
header('Location: ./home_guest.php');
exit;
