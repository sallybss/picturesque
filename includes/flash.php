<?php
// includes/flash.php

// Make sure sessions are available everywhere
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

/* ============================
 * CSRF protection helpers
 * ============================
 */

// Create/get the CSRF token
if (!function_exists('csrf_token')) {
  function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
      // 16 bytes = 32 hex chars, plenty strong for CSRF tokens
      $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
  }
}

// Primary CSRF checker (canonical)
if (!function_exists('csrf_check')) {
  function csrf_check(?string $token): bool {
    return isset($_SESSION['csrf'])
      && is_string($token)
      && $token !== ''
      && hash_equals($_SESSION['csrf'], $token);
  }
}

// Back-compat alias for places that call check_csrf()
if (!function_exists('check_csrf')) {
  function check_csrf(?string $token): bool {
    return csrf_check($token);
  }
}

/* ============================
 * Flash message helpers
 * ============================
 */

if (!function_exists('set_flash')) {
  function set_flash(string $type, string $msg): void {
    $_SESSION['flash'][$type] = $msg;
  }
}

if (!function_exists('get_flash')) {
  function get_flash(string $type): ?string {
    if (!empty($_SESSION['flash'][$type])) {
      $m = $_SESSION['flash'][$type];
      unset($_SESSION['flash'][$type]);
      return $m;
    }
    return null;
  }
}