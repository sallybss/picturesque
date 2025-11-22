<?php
function render_sidebar(array $opts = []): void
{
  $isAdmin = (bool)($opts['isAdmin'] ?? false);
  $isGuest = (bool)($opts['isGuest'] ?? false);

  $cur     = basename($_SERVER['PHP_SELF']);
  $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
  $logo    = $baseUrl . '/images/logo.png';
  $homeHref   = $isGuest ? './home_guest.php' : './index.php';
  $homeMatch  = $isGuest ? 'home_guest.php'   : 'index.php';
  $createHref = $isGuest ? './auth/login.php' : './create.php';

  echo '<aside class="sidenav" id="appSidebar">';

  echo '<button class="close-btn" id="closeSidebar" aria-label="Close menu">×</button>';

  echo '<div class="brand">
          <a href="' . htmlspecialchars($homeHref) . '">
            <img src="' . htmlspecialchars($logo) . '" class="brand-logo" alt="Picturesque logo">
          </a>
        </div>';

  echo '<a class="create-btn" href="' . htmlspecialchars($createHref) . '">☆ Create</a>';

  echo '<nav class="nav">';
  echo '<div class="nav-top">';
  echo nav_item($homeHref, 'Home', $homeMatch, $cur, icon_home());
  echo nav_item('./about.php', 'About', 'about.php', $cur, icon_info());

  if (!$isGuest) {
    echo nav_item(
      './profile.php',
      'My Profile',
      ['profile.php', 'profile_edit.php', 'profile_settings.php'],
      $cur,
      icon_user()
    );
  }

  echo nav_item('./contact.php', 'Contact', 'contact.php', $cur, icon_contact());

  if (!$isGuest && $isAdmin) {
    echo '<div class="rule"></div>';
    echo nav_item('./admin.php', 'Admin', 'admin.php', $cur, icon_admin());
    echo nav_item('./settings.php', 'Advanced', 'settings.php', $cur, icon_settings());
  }
  echo '</div>'; 

  echo '<div class="nav-spacer"></div>';
  echo '<div class="nav-bottom">';
  echo nav_item('./rules.php', 'Rules & Regulations', 'rules.php', $cur, icon_rules());

  if ($isGuest) {
    echo nav_item('./auth/login.php', 'Login', 'login.php', $cur, icon_login());
    echo nav_item('./auth/register.php', 'Register', 'register.php', $cur, icon_login());
  } else {
    echo nav_item('./auth/logout.php', 'Logout', 'logout.php', $cur, icon_logout());
  }
  echo '</div>';
  echo '</nav></aside>';
}

function nav_item(string $href, string $label, $match, string $cur, string $icon): string
{
    if (is_array($match)) {
        $isActive = in_array($cur, $match, true);
    } else {
        $isActive = ($cur === $match);
    }

    $class = $isActive ? 'active' : '';

    return '<a href="' . htmlspecialchars($href) . '" class="' . $class . '">'
         . $icon
         . '<span>' . htmlspecialchars($label) . '</span>'
         . '</a>';
}

function icon_home() {
  return '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#000000" viewBox="0 0 256 256"><path d="M217.9,110.1l-80-80a14,14,0,0,0-19.8,0l-80,80A13.92,13.92,0,0,0,34,120v96a6,6,0,0,0,6,6h64a6,6,0,0,0,6-6V158h36v58a6,6,0,0,0,6,6h64a6,6,0,0,0,6-6V120A13.92,13.92,0,0,0,217.9,110.1ZM210,210H158V152a6,6,0,0,0-6-6H104a6,6,0,0,0-6,6v58H46V120a2,2,0,0,1,.58-1.42l80-80a2,2,0,0,1,2.84,0l80,80A2,2,0,0,1,210,120Z"></path></svg>';
}

function icon_info() {
  return '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#000000" viewBox="0 0 256 256"><path d="M232,50H160a38,38,0,0,0-32,17.55A38,38,0,0,0,96,50H24a6,6,0,0,0-6,6V200a6,6,0,0,0,6,6H96a26,26,0,0,1,26,26,6,6,0,0,0,12,0,26,26,0,0,1,26-26h72a6,6,0,0,0,6-6V56A6,6,0,0,0,232,50ZM96,194H30V62H96a26,26,0,0,1,26,26V204.31A37.86,37.86,0,0,0,96,194Zm130,0H160a37.87,37.87,0,0,0-26,10.32V88a26,26,0,0,1,26-26h66Z"></path></svg>';
}

function icon_user() {
  return '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#000000" viewBox="0 0 256 256"><path d="M229.19,213c-15.81-27.32-40.63-46.49-69.47-54.62a70,70,0,1,0-63.44,0C67.44,166.5,42.62,185.67,26.81,213a6,6,0,1,0,10.38,6C56.4,185.81,90.34,166,128,166s71.6,19.81,90.81,53a6,6,0,1,0,10.38-6ZM70,96a58,58,0,1,1,58,58A58.07,58.07,0,0,1,70,96Z"></path></svg>';
}

function icon_contact() {
  return '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#000000" viewBox="0 0 256 256"><path d="M224,50H32a6,6,0,0,0-6,6V192a14,14,0,0,0,14,14H216a14,14,0,0,0,14-14V56A6,6,0,0,0,224,50ZM208.58,62,128,135.86,47.42,62ZM216,194H40a2,2,0,0,1-2-2V69.64l86,78.78a6,6,0,0,0,8.1,0L218,69.64V192A2,2,0,0,1,216,194Z"></path></svg>';
}

function icon_admin() {
  return '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#000000" viewBox="0 0 256 256"><path d="M198,112a6,6,0,0,1-6,6H152a6,6,0,0,1,0-12h40A6,6,0,0,1,198,112Zm-6,26H152a6,6,0,0,0,0,12h40a6,6,0,0,0,0-12Zm38-82V200a14,14,0,0,1-14,14H40a14,14,0,0,1-14-14V56A14,14,0,0,1,40,42H216A14,14,0,0,1,230,56Zm-12,0a2,2,0,0,0-2-2H40a2,2,0,0,0-2,2V200a2,2,0,0,0,2,2H216a2,2,0,0,0,2-2ZM133.81,166.51a6,6,0,0,1-11.62,3C119.34,158.38,108.08,150,96,150s-23.33,8.38-26.19,19.5a6,6,0,0,1-11.62-3A38,38,0,0,1,76.78,143a30,30,0,1,1,38.45,0A38,38,0,0,1,133.81,166.51ZM96,138a18,18,0,1,0-18-18A18,18,0,0,0,96,138Z"></path></svg>';
}

function icon_settings() {
  return '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#000000" viewBox="0 0 256 256"><path d="M128,82a46,46,0,1,0,46,46A46.06,46.06,0,0,0,128,82Zm0,80a34,34,0,1,1,34-34A34,34,0,0,1,128,162Zm108-54.4a6,6,0,0,0-2.92-4L202.64,86.22l-.42-.71L202.1,51.2A6,6,0,0,0,200,46.64a110.12,110.12,0,0,0-36.07-20.31,6,6,0,0,0-4.84.45L128.46,43.86h-1L96.91,26.76a6,6,0,0,0-4.86-.44A109.92,109.92,0,0,0,56,46.68a6,6,0,0,0-2.12,4.55l-.16,34.34c-.14.23-.28.47-.41.71L22.91,103.57A6,6,0,0,0,20,107.62a104.81,104.81,0,0,0,0,40.78,6,6,0,0,0,2.92,4l30.42,17.33.42.71.12,34.31A6,6,0,0,0,56,209.36a110.12,110.12,0,0,0,36.07,20.31,6,6,0,0,0,4.84-.45l30.61-17.08h1l30.56,17.1A6.09,6.09,0,0,0,162,230a5.83,5.83,0,0,0,1.93-.32,109.92,109.92,0,0,0,36-20.36,6,6,0,0,0,2.12-4.55l.16-34.34c.14-.23.28-.47.41-.71l30.42-17.29a6,6,0,0,0,2.92-4.05A104.81,104.81,0,0,0,236,107.6Zm-11.25,35.79L195.32,160.1a6.07,6.07,0,0,0-2.28,2.3c-.59,1-1.21,2.11-1.86,3.14a6,6,0,0,0-.91,3.16l-.16,33.21a98.15,98.15,0,0,1-27.52,15.53L133,200.88a6,6,0,0,0-2.93-.77h-.14c-1.24,0-2.5,0-3.74,0a6,6,0,0,0-3.07.76L93.45,217.43a98,98,0,0,1-27.56-15.49l-.12-33.17a6,6,0,0,0-.91-3.16c-.64-1-1.27-2.08-1.86-3.14a6,6,0,0,0-2.27-2.3L31.3,143.4a93,93,0,0,1,0-30.79L60.68,95.9A6.07,6.07,0,0,0,63,93.6c.59-1,1.21-2.11,1.86-3.14a6,6,0,0,0,.91-3.16l.16-33.21A98.15,98.15,0,0,1,93.41,38.56L123,55.12a5.81,5.81,0,0,0,3.07.76c1.24,0,2.5,0,3.74,0a6,6,0,0,0,3.07-.76l29.65-16.56a98,98,0,0,1,27.56,15.49l.12,33.17a6,6,0,0,0,.91,3.16c.64,1,1.27,2.08,1.86,3.14a6,6,0,0,0,2.27,2.3L224.7,112.6A93,93,0,0,1,224.73,143.39Z"></path></svg>';
}

function icon_rules() {
  return '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#000000" viewBox="0 0 256 256"><path d="M98,136a6,6,0,0,1,6-6h64a6,6,0,0,1,0,12H104A6,6,0,0,1,98,136Zm6-26h64a6,6,0,0,0,0-12H104a6,6,0,0,0,0,12Zm126,82a30,30,0,0,1-30,30H88a30,30,0,0,1-30-30V64a18,18,0,0,0-36,0c0,6.76,5.58,11.19,5.64,11.23A6,6,0,1,1,20.4,84.8C20,84.48,10,76.85,10,64A30,30,0,0,1,40,34H176a30,30,0,0,1,30,30V170h10a6,6,0,0,1,3.6,1.2C220,171.52,230,179.15,230,192Zm-124,0c0-6.76-5.59-11.19-5.64-11.23A6,6,0,0,1,104,170h90V64a18,18,0,0,0-18-18H64a29.82,29.82,0,0,1,6,18V192a18,18,0,0,0,36,0Zm112,0a14.94,14.94,0,0,0-4.34-10H115.88A24.83,24.83,0,0,1,118,192a29.87,29.87,0,0,1-6,18h88A18,18,0,0,0,218,192Z"></path></svg>';
}

function icon_logout() {
  return '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#000000" viewBox="0 0 256 256"><path d="M118,216a6,6,0,0,1-6,6H48a6,6,0,0,1-6-6V40a6,6,0,0,1,6-6h64a6,6,0,0,1,0,12H54V210h58A6,6,0,0,1,118,216Zm110.24-92.24-40-40a6,6,0,0,0-8.48,8.48L209.51,122H112a6,6,0,0,0,0,12h97.51l-29.75,29.76a6,6,0,1,0,8.48,8.48l40-40A6,6,0,0,0,228.24,123.76Z"></path></svg>';
}

function icon_login() {
  return '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#000000" viewBox="0 0 256 256"><path d="M140.24,132.24l-40,40a6,6,0,0,1-8.48-8.48L121.51,134H24a6,6,0,0,1,0-12h97.51L91.76,92.24a6,6,0,0,1,8.48-8.48l40,40A6,6,0,0,1,140.24,132.24ZM200,34H136a6,6,0,0,0,0,12h58V210H136a6,6,0,0,0,0,12h64a6,6,0,0,0,6-6V40A6,6,0,0,0,200,34Z"></path></svg>';
}
?>
