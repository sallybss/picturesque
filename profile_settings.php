<?php
require_once __DIR__ . '/includes/init.php';

$me = Auth::requireUserOrRedirect('./auth/login.php');

$profiles = new ProfileRepository();
$row = $profiles->getLoginEmailAndRole($me);
if (!$row) {
  header('Location: ./index.php');
  exit;
}

$isAdmin = ($row['role'] ?? '') === 'admin';
$currentEmail = $row['login_email'] ?? '';

?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Profile Settings · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=1">
</head>

<body>
  <?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>
  <?php if ($m = get_flash('ok')): ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>

  <button class="hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false">☰</button>

  <div class="layout">
    <?php render_sidebar(['isAdmin' => $isAdmin]); ?>

    <main class="content">
      <div class="settings-wrap">
        <h1 class="page-title">Profile Settings</h1>
        <p class="sub">Update your login email and password.</p>

        <div class="about-card">
          <div class="pad">
            <form method="post" action="./actions/update_credentials.php" autocomplete="off">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

              <div class="row">
                <label class="label" for="login_email">Login Email</label>
                <input class="input" id="login_email" type="email" name="login_email" value="<?= htmlspecialchars($currentEmail) ?>" required>
              </div>

              <div class="row sub"><b>Change Password</b></div>

              <div class="row">
                <label class="label" for="current_password">Current Password</label>
                <input class="input" id="current_password" type="password" name="current_password" placeholder="Enter current password if changing password">
              </div>

              <div class="row">
                <label class="label" for="new_password">New Password</label>
                <input class="input" id="new_password" type="password" name="new_password" placeholder="Minimum 8 characters">
              </div>

              <div class="row">
                <label class="label" for="confirm_password">Confirm New Password</label>
                <input class="input" id="confirm_password" type="password" name="confirm_password" placeholder="Repeat new password">
              </div>

              <div class="btns">
                <button class="btn-primary" type="submit">Save Changes</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </main>
  </div>
</body>

</html>