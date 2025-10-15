<?php
require __DIR__ . '/includes/flash.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/admin_guard.php';
require __DIR__ . '/includes/sidebar.php'; // ✅ import reusable sidebar

if (empty($_SESSION['profile_id'])) {
    header('Location: ./auth/login.php');
    exit;
}

$me   = (int)$_SESSION['profile_id'];
$conn = db();
require_admin($conn, $me); // only admins

// current user (for avatar/name + sidebar)
$stmtMe = $conn->prepare("SELECT display_name, avatar_photo, role FROM profiles WHERE profile_id=?");
$stmtMe->bind_param('i', $me);
$stmtMe->execute();
$meRow   = $stmtMe->get_result()->fetch_assoc();
$isAdmin = (isset($meRow['role']) && $meRow['role'] === 'admin');
$stmtMe->close();

// Optional search by email/name
$q    = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';

// Load users 
$sql = "
  SELECT
    pr.profile_id,
    pr.display_name,
    pr.login_email,
    pr.email,
    pr.avatar_photo,
    pr.role,
    pr.status,
    pr.created_at,
    (SELECT COUNT(*) FROM pictures p WHERE p.profile_id=pr.profile_id) AS posts
  FROM profiles pr
";
$types  = '';
$params = [];
if ($q !== '') {
    $sql    .= " WHERE pr.login_email LIKE ? OR pr.display_name LIKE ? ";
    $types  .= 'ss';
    $params[] = $like;
    $params[] = $like;
}
$sql .= " ORDER BY pr.created_at DESC";

$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// base URL for uploads
$baseUrl       = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$publicUploads = $baseUrl . '/uploads/';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css?v=8">
</head>
<body>
  <div class="layout">
    <!-- ✅ Sidebar (reusable) -->
    <?php render_sidebar(['isAdmin' => $isAdmin]); ?>

    <!-- Main content -->
    <main class="content">
      <div class="content-top">
        <h1 class="page-title" style="margin:0">Overview</h1>

        <form class="search-wrap" method="get" action="admin.php">
          <input class="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search users">
        </form>

        <div class="userbox">
          <span class="avatar" style="background-image:url('<?= !empty($meRow['avatar_photo']) ? $publicUploads . htmlspecialchars($meRow['avatar_photo']) : 'https://placehold.co/28x28?text=%20' ?>');"></span>
          <span class="username"><?= htmlspecialchars($meRow['display_name'] ?? 'Admin') ?></span>
        </div>
      </div>

      <p class="admin-subtitle">Users (<?= count($users) ?> total)</p>

      <div class="admin-list">
        <?php foreach ($users as $u): ?>
          <?php
            $a = !empty($u['avatar_photo'])
              ? $publicUploads . htmlspecialchars($u['avatar_photo'])
              : 'https://placehold.co/32x32?text=%20';
          ?>
          <div class="admin-item">
            <div class="ai-left">
              <img class="ai-avatar" src="<?= $a ?>" alt="">
              <div class="ai-meta">
                <div class="ai-name"><?= htmlspecialchars($u['display_name']) ?></div>
                <div class="ai-sub">
                  <span><?= htmlspecialchars($u['login_email']) ?></span>
                  <span>• Joined <?= htmlspecialchars(substr($u['created_at'], 0, 10)) ?></span>
                  <span>• Posts <?= (int)$u['posts'] ?></span>
                </div>
              </div>
            </div>

            <div class="ai-center">
              <span class="status-badge status-<?= htmlspecialchars($u['status']) ?>"><?= htmlspecialchars($u['status']) ?></span>
              <span class="role-badge"><?= htmlspecialchars($u['role']) ?></span>
            </div>

            <div class="ai-actions">
              <?php if ((int)$u['profile_id'] !== $me && ($u['role'] ?? 'user') !== 'admin'): ?>
                <!-- Block / Unblock -->
                <form class="inline" method="post" action="./actions/admin_toggle_status.php">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                  <input type="hidden" name="profile_id" value="<?= (int)$u['profile_id'] ?>">
                  <button class="btn-ghost pill" type="submit">
                    <?= $u['status'] === 'blocked' ? 'Unblock' : 'Block' ?>
                  </button>
                </form>

                <!-- Delete user -->
                <form class="inline" method="post" action="./actions/admin_delete_user.php"
                      onsubmit="return confirm('Delete this user and all their posts/likes/comments? This cannot be undone.');">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                  <input type="hidden" name="profile_id" value="<?= (int)$u['profile_id'] ?>">
                  <button class="btn-danger pill" type="submit">Delete</button>
                </form>
              <?php endif; ?>

              <!-- View posts -->
              <a class="btn-ghost pill" href="./admin_user_posts.php?id=<?= (int)$u['profile_id'] ?>">View posts</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </main>
  </div>
</body>
</html>