<?php
function render_sidebar(array $opts = []): void
{
  $isAdmin   = (bool)($opts['isAdmin'] ?? false);
  $isGuest   = (bool)($opts['isGuest'] ?? false);
  $homeCount = $opts['homeCount'] ?? null;

  $cur     = basename($_SERVER['PHP_SELF']);
  $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
  $logo    = $baseUrl . '/images/logo.png';

  $homeHref   = $isGuest ? './home_guest.php' : './index.php';
  $createHref = $isGuest ? './auth/login.php' : './create.php';

  echo '<aside class="sidenav" id="appSidebar">';

  echo '<button class="close-btn" id="closeSidebar" aria-label="Close menu">×</button>';

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
  echo '<a href="./rules.php" class="' . $rulesActive . '">Rules &amp; Regulations</a>';

  if (!$isGuest) {
    $active = in_array($cur, ['profile.php', 'profile_edit.php', 'profile_settings.php']) ? 'active' : '';
    echo '<a href="./profile.php" class="' . $active . '">My Profile</a>';
  }

  if (!$isGuest && $isAdmin) {
    $active = ($cur === 'admin.php') ? 'active' : '';
    echo '<a href="./admin.php" class="' . $active . '">Admin</a>';
  }

  echo '<div class="rule"></div>';

  if (!$isGuest && $isAdmin) {
    $active = ($cur === 'settings.php') ? 'active' : '';
    echo '<a href="./settings.php" class="' . $active . '">Settings</a>';
  }

  $active = ($cur === 'about.php') ? 'active' : '';
  echo '<a href="./about.php" class="' . $active . '">About</a>';

  $active = ($cur === 'contact.php') ? 'active' : '';
  echo '<a href="./contact.php" class="' . $active . '">Contact</a>';

  if ($isGuest) {
    echo '<a href="./auth/login.php">Login</a><a href="./auth/register.php">Register</a>';
  } else {
    echo '<a href="./auth/logout.php">Logout</a>';
  }

  echo '</nav></aside>';
}
