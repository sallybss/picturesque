<?php
session_start(); // keep sessions available everywhere

function csrf_token(): string {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
  }
  return $_SESSION['csrf'];
}
function csrf_check(?string $token): bool {
  return isset($_SESSION['csrf']) && $token !== null && hash_equals($_SESSION['csrf'], $token);
}


function set_flash(string $type, string $msg): void {
  $_SESSION['flash'][$type] = $msg;
}
function get_flash(string $type): ?string {
  if (!empty($_SESSION['flash'][$type])) {
    $m = $_SESSION['flash'][$type];
    unset($_SESSION['flash'][$type]);
    return $m;
  }
  return null;
}