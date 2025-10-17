<?php
// profile_settings.php
require __DIR__ . '/includes/flash.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/sidebar.php';

if (empty($_SESSION['profile_id'])) { header('Location: ./auth/login.php'); exit; }

$me   = (int)$_SESSION['profile_id'];
$conn = db();

/* Figure out if this user is admin (for sidebar only) */
$stmt = $conn->prepare("SELECT login_email, password_hash, role FROM profiles WHERE profile_id=?");
$stmt->bind_param('i', $me);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) { $conn->close(); header('Location: ./index.php'); exit; }
$isAdmin = (isset($row['role']) && $row['role'] === 'admin');
$currentEmail = $row['login_email'];

$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Profile Settings · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=14">
  <style>
    .settings-card{max-width:560px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;margin:24px auto;overflow:hidden}
    .pad{padding:16px}
    .label{display:block;margin:10px 0 6px;font-weight:600}
    .input,.password{width:100%;padding:12px;border:1px solid #e5e7eb;border-radius:10px}
    .btn-primary{padding:10px 14px;border:none;border-radius:10px;background:#8ec6df;color:#fff;font-weight:600;cursor:pointer}
    .btn-primary:hover{background:#7dbad6}
    .muted{color:#6b7280}
  </style>
</head>
<body>

<?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>
<?php if ($m = get_flash('ok')): ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>

<div class="layout">
  <?php render_sidebar(['isAdmin' => $isAdmin]); ?>

  <main class="content">
    <h1 style="margin-top:8px">Profile Settings</h1>
    <p class="muted" style="margin-bottom:16px">Update your login email and password.</p>

    <div class="settings-card">
      <form class="pad" method="post" action="./actions/update_credentials.php" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

        <label class="label">Login Email</label>
        <input class="input" type="email" name="login_email" value="<?= htmlspecialchars($currentEmail) ?>" required>

        <hr style="border:none;border-top:1px solid #e5e7eb;margin:18px 0">

        <div class="muted" style="margin-bottom:8px"><b>Change Password</b></div>

        <label class="label">Current Password</label>
        <input class="password" type="password" name="current_password" placeholder="Enter current password if changing password">

        <label class="label">New Password</label>
        <input class="password" type="password" name="new_password" placeholder="Minimum 8 characters">

        <label class="label">Confirm New Password</label>
        <input class="password" type="password" name="confirm_password" placeholder="Repeat new password">

        <div style="margin-top:16px">
          <button class="btn-primary" type="submit">Save Changes</button>
        </div>
      </form>
    </div>
  </main>
</div>
</body>
</html>