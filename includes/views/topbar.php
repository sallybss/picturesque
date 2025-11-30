<?php

function render_topbar_userbox(array $meRow): void {
  $name = htmlspecialchars($meRow['display_name'] ?? 'You');
  $avatar = !empty($meRow['avatar_photo'])
    ? img_from_db($meRow['avatar_photo'])
    : 'https://placehold.co/28x28?text=%20';

  echo '
  <div class="topbar-userbox">
    <a href="./profile.php" class="userbox" title="Go to my profile">
      <span class="avatar" style="background-image:url(\'' . $avatar . '\');"></span>
      <span class="userbox-name">' . $name . '</span>
    </a>
  </div>';
}