<?php
function render_sidebar(array $opts = []): void {
  $isAdmin   = (bool)($opts['isAdmin'] ?? false);
  $isGuest   = (bool)($opts['isGuest'] ?? false);
  $homeCount = $opts['homeCount'] ?? null;

  $cur     = basename($_SERVER['PHP_SELF']);
  $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
  $logo    = $baseUrl . '/images/logo.png';

  $homeHref   = $isGuest ? './home_guest.php' : './index.php';
  $createHref = $isGuest ? './auth/login.php' : './create.php';

  echo '<aside class="sidenav">';

  echo '<div class="brand">
          <a href="' . htmlspecialchars($homeHref) . '" class="brand-link" style="text-decoration:none;">
            <img src="' . htmlspecialchars($logo) . '" alt="Picturesque logo" class="brand-logo">
          </a>
        </div>';

  echo '<a class="create-btn" href="' . htmlspecialchars($createHref) . '">☆ Create</a>';

  echo '<nav class="nav">';

  $homeActive = ($cur === ($isGuest ? 'home_guest.php' : 'index.php')) ? 'active' : '';
  echo '<a href="' . htmlspecialchars($homeHref) . '" class="' . $homeActive . '">
          <svg class="ico" viewBox="0 0 256 256" fill="currentColor" aria-hidden="true">
            <path d="M219.31,108.68l-80-80a16,16,0,0,0-22.62,0l-80,80A15.87,15.87,0,0,0,32,120v96a8,8,0,0,0,8,8h64a8,8,0,0,0,8-8V160h32v56a8,8,0,0,0,8,8h64a8,8,0,0,0,8-8V120A15.87,15.87,0,0,0,219.31,108.68ZM208,208H160V152a8,8,0,0,0-8-8H104a8,8,0,0,0-8,8v56H48V120l80-80,80,80Z"></path>
          </svg>
          Home' . ($homeCount !== null ? '<span class="badge">' . (int)$homeCount . '</span>' : '') . '
        </a>';

        $rulesActive = ($cur === 'rules.php') ? 'active' : '';
        echo '<a href="./rules.php" class="'.$rulesActive.'">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="ico">
            <path d="M12 2l8 4v6c0 5-3.8 9.5-8 10-4.2-.5-8-5-8-10V6l8-4z"/>
             <path d="M9 12l2 2 4-4"/>
          </svg>
                Rules &amp; Regulations
              </a>';

  if (!$isGuest) {
    $active = in_array($cur, ['profile.php','profile_edit.php','profile_settings.php']) ? 'active' : '';
    echo '<a href="./profile.php" class="' . $active . '">
            <svg class="ico" viewBox="0 0 256 256" fill="currentColor" aria-hidden="true">
              <path d="M230.92,212c-15.23-26.33-38.7-45.21-66.09-54.16a72,72,0,1,0-73.66,0C63.78,166.78,40.31,185.66,25.08,212a8,8,0,1,0,13.85,8c18.84-32.56,52.14-52,89.07-52s70.23,19.44,89.07,52a8,8,0,1,0,13.85-8ZM72,96a56,56,0,1,1,56,56A56.06,56.06,0,0,1,72,96Z"></path>
            </svg>
            My Profile
          </a>';
  }

  if (!$isGuest && $isAdmin) {
    $active = ($cur === 'admin.php') ? 'active' : '';
    echo '<a href="./admin.php" class="' . $active . '">
            <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
            Admin
          </a>';
  }

  echo '<div class="rule"></div>';

  if (!$isGuest && $isAdmin) {
    $active = ($cur === 'settings.php') ? 'active' : '';
    echo '<a href="./settings.php" class="' . $active . '">
            <svg class="ico" viewBox="0 0 256 256" fill="none" stroke="currentColor" stroke-width="13" stroke-linecap="round" stroke-linejoin="round">
              <path d="M230.59,151.43l-14.88-8.59a87.94,87.94,0,0,0,0-29.68l14.88-8.59a8,8,0,0,0,3-10.94l-16-27.71a8,8,0,0,0-10.94-3L192.09,72.94a87.76,87.76,0,0,0-25.68-14.83V40a8,8,0,0,0-8-8H97.59a8,8,0,0,0-8,8V58.11A87.76,87.76,0,0,0,63.91,72.94L45.35,62.92a8,8,0,0,0-10.94,3l-16,27.71a8,8,0,0,0,3,10.94l14.88,8.59a87.94,87.94,0,0,0,0,29.68l-14.88,8.59a8,8,0,0,0-3,10.94l16,27.71a8,8,0,0,0,10.94,3l18.56-10a87.76,87.76,0,0,0,25.68,14.83V216a8,8,0,0,0,8,8h60.82a8,8,0,0,0,8-8V197.89a87.76,87.76,0,0,0,25.68-14.83l18.56,10a8,8,0,0,0,10.94-3l16-27.71A8,8,0,0,0,230.59,151.43ZM128,168a40,40,0,1,1,40-40A40,40,0,0,1,128,168Z"/>
            </svg>
            Settings
          </a>';
  }

  $active = ($cur === 'about.php') ? 'active' : '';
  echo '<a href="./about.php" class="' . $active . '">About</a>';

  $active = ($cur === 'contact.php') ? 'active' : '';
  echo '<a href="./contact.php" class="' . $active . '">Contact</a>';

  if ($isGuest) {
    $active = ($cur === 'login.php') ? 'active' : '';
    echo '<a href="./auth/login.php" class="' . $active . '">
            <svg class="ico" viewBox="0 0 256 256" fill="currentColor" aria-hidden="true">
              <path d="M120,216a8,8,0,0,1-8,8H48a8,8,0,0,1-8-8V40a8,8,0,0,1,8-8h64a8,8,0,0,1,0,16H56V208h56A8,8,0,0,1,120,216Zm109.66-93.66-40-40a8,8,0,0,0-11.32,11.32L204.69,120H112a8,8,0,0,0,0,16h92.69l-26.35,26.34a8,8,0,0,0,11.32,11.32l40-40A8,8,0,0,0,229.66,122.34Z"></path>
            </svg>
            Login
          </a>';

    $active = ($cur === 'register.php') ? 'active' : '';
    echo '<a href="./auth/register.php" class="' . $active . '">
            <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="8" r="4"></circle>
              <path d="M20 21a8 8 0 0 0-16 0"></path>
              <path d="M16 11h6M19 8v6"></path>
            </svg>
            Register
          </a>';
  } else {
    echo '<a href="./auth/logout.php">
            <svg class="ico" viewBox="0 0 256 256" fill="currentColor" aria-hidden="true">
              <path d="M120,216a8,8,0,0,1-8,8H48a8,8,0,0,1-8-8V40a8,8,0,0,1,8-8h64a8,8,0,0,1,0,16H56V208h56A8,8,0,0,1,120,216Zm109.66-93.66-40-40a8,8,0,0,0-11.32,11.32L204.69,120H112a8,8,0,0,0,0,16h92.69l-26.35,26.34a8,8,0,0,0,11.32,11.32l40-40A8,8,0,0,0,229.66,122.34Z"></path>
            </svg>
            Logout
          </a>';
  }

  echo '</nav></aside>';
}
