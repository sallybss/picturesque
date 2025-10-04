<?php
session_start(); // keep sessions available everywhere

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