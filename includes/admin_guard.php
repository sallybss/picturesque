<?php
require_once __DIR__ . '/flash.php';

function require_admin(mysqli $conn, int $me): void {
  // Temporary rule: only profile_id 1 is admin
  if ($me !== 1) {
    set_flash('err', 'Admins only.');
    header('Location: ./index.php');
    exit;
  }
}