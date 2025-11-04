<?php
// includes/topbar.php
require_once __DIR__ . '/init.php';

function render_topbar_userbox(array $meRow): void {
  $name = htmlspecialchars($meRow['display_name'] ?? 'You');
  $avatar = !empty($meRow['avatar_photo'])
    ? img_from_db($meRow['avatar_photo'])
    : 'https://placehold.co/28x28?text=%20';

  echo '
  <div class="topbar" 
       style="display:flex; justify-content:flex-end; align-items:center; margin:8px 0 12px; margin-left:auto;">
    <a href="./profile.php" class="userbox" title="Go to my profile"
       style="display:inline-flex; align-items:center; gap:10px; text-decoration:none; color:inherit;">
      <span class="avatar" style="
        width:32px; height:32px; border-radius:50%;
        background-image:url(\'' . $avatar . '\');
        background-size:cover; background-position:center;
        border:2px solid #e0e0e0; flex-shrink:0;"></span>
      <span class="username" style="font-weight:600; color:#333;">' . $name . '</span>
    </a>
  </div>';
}