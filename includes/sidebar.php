<?php
// includes/sidebar.php

function render_sidebar(array $opts = []): void {
  $isAdmin   = $opts['isAdmin']   ?? false;
  $homeCount = $opts['homeCount'] ?? null;
  $cur       = basename($_SERVER['PHP_SELF']); // current filename

  echo '<aside class="sidenav">
    <div class="brand">PICTURESQUE</div>

    <a class="create-btn" href="./create.php">☆ Create</a>

    <nav class="nav">';

  // Home
  $active = ($cur === 'index.php') ? 'active' : '';
  echo '<a href="./index.php" class="'.$active.'">
          <svg class="ico" viewBox="0 0 256 256" fill="currentColor" aria-hidden="true">
            <path d="M219.31,108.68l-80-80a16,16,0,0,0-22.62,0l-80,80A15.87,15.87,0,0,0,32,120v96a8,8,0,0,0,8,8h64a8,8,0,0,0,8-8V160h32v56a8,8,0,0,0,8,8h64a8,8,0,0,0,8-8V120A15.87,15.87,0,0,0,219.31,108.68ZM208,208H160V152a8,8,0,0,0-8-8H104a8,8,0,0,0-8,8v56H48V120l80-80,80,80Z"></path>
          </svg>
          Home';
  if ($homeCount !== null) echo '<span class="badge">'.$homeCount.'</span>';
  echo '</a>';

  // Profile
  $active = in_array($cur, ['profile.php', 'profile_edit.php']) ? 'active' : '';
  echo '<a href="./profile.php" class="'.$active.'">
          <svg class="ico" viewBox="0 0 256 256" fill="currentColor" aria-hidden="true">
            <path d="M230.92,212c-15.23-26.33-38.7-45.21-66.09-54.16a72,72,0,1,0-73.66,0C63.78,166.78,40.31,185.66,25.08,212a8,8,0,1,0,13.85,8c18.84-32.56,52.14-52,89.07-52s70.23,19.44,89.07,52a8,8,0,1,0,13.85-8ZM72,96a56,56,0,1,1,56,56A56.06,56.06,0,0,1,72,96Z"></path>
          </svg>
          My Profile
        </a>';

  // Admin
  if ($isAdmin) {
    $active = ($cur === 'admin.php') ? 'active' : '';
    echo '<a href="./admin.php" class="'.$active.'">🛡️ Admin</a>';
  }

  echo '<div class="rule"></div>';

  // Settings (ADMIN ONLY)
  if ($isAdmin) {
    $active = ($cur === 'settings.php') ? 'active' : '';
    echo '<a href="./settings.php" class="'.$active.'">
            <span class="icon">⚙️</span>
            Settings
          </a>';
  }

  // About (everyone)
  $active = ($cur === 'about.php') ? 'active' : '';
  echo '<a href="./about.php" class="'.$active.'">About</a>';

  // Logout
  echo '<a href="./auth/logout.php">
          <svg class="ico" viewBox="0 0 256 256" fill="currentColor" aria-hidden="true">
            <path d="M120,216a8,8,0,0,1-8,8H48a8,8,0,0,1-8-8V40a8,8,0,0,1,8-8h64a8,8,0,0,1,0,16H56V208h56A8,8,0,0,1,120,216Zm109.66-93.66-40-40a8,8,0,0,0-11.32,11.32L204.69,120H112a8,8,0,0,0,0,16h92.69l-26.35,26.34a8,8,0,0,0,11.32,11.32l40-40A8,8,0,0,0,229.66,122.34Z"></path>
          </svg>
          Logout
        </a>';

  echo '</nav></aside>';
}
?>