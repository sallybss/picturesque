<?php
require __DIR__ . '/../includes/flash.php';
$_SESSION = [];
session_destroy();
set_flash('ok','You are logged out.');
header('Location: ./login.php'); exit;