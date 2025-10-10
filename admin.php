<?php
require __DIR__ . '/includes/flash.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/admin_guard.php';

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
$meRow = $stmtMe->get_result()->fetch_assoc();
$isAdmin = (($meRow['role'] ?? '') === 'admin');
$stmtMe->close();

$cur = basename($_SERVER['PHP_SELF']); // for active nav item

// Optional search by email/name
$q = trim($_GET['q'] ?? '');
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
    -- quick stats
    (SELECT COUNT(*) FROM pictures p WHERE p.profile_id=pr.profile_id) AS posts
  FROM profiles pr
";
$types = '';
$params = [];
if ($q !== '') {
    $sql .= " WHERE pr.login_email LIKE ? OR pr.display_name LIKE ? ";
    $types .= 'ss';
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
        <!-- Sidebar -->
  <aside class="sidenav">
    <div class="brand">PICTURESQUE</div>

    <a class="create-btn" href="./create.php">☆ Create</a>

    <nav class="nav">
      <a href="./index.php" class="<?= $cur === 'index.php'   ? 'active' : '' ?>">
        <svg class="ico" viewBox="0 0 256 256" fill="currentColor" aria-hidden="true">
         <path d="M219.31,108.68l-80-80a16,16,0,0,0-22.62,0l-80,80A15.87,15.87,0,0,0,32,120v96a8,8,0,0,0,8,8h64a8,8,0,0,0,8-8V160h32v56a8,8,0,0,0,8,8h64a8,8,0,0,0,8-8V120A15.87,15.87,0,0,0,219.31,108.68ZM208,208H160V152a8,8,0,0,0-8-8H104a8,8,0,0,0-8,8v56H48V120l80-80,80,80Z"></path>
        </svg>
        Home
        <span class="badge">5</span> <!-- notification optional, can remove -->
      </a>

      <a href="./profile.php" class="<?= in_array($cur, ['profile.php','profile_edit.php']) ? 'active' : '' ?>">
        <svg class="ico" viewBox="0 0 256 256" fill="currentColor" aria-hidden="true">
         <path d="M230.92,212c-15.23-26.33-38.7-45.21-66.09-54.16a72,72,0,1,0-73.66,0C63.78,166.78,40.31,185.66,25.08,212a8,8,0,1,0,13.85,8c18.84-32.56,52.14-52,89.07-52s70.23,19.44,89.07,52a8,8,0,1,0,13.85-8ZM72,96a56,56,0,1,1,56,56A56.06,56.06,0,0,1,72,96Z"></path>
        </svg>
        My Profile
      </a>

        <?php if ($isAdmin): ?>
          <a href="./admin.php" class="<?= $cur === 'admin.php' ? 'active' : '' ?>">🛡️ Admin</a>
        <?php endif; ?>

      <div class="rule"></div>

      <a href="./settings.php" class="<?= $cur === 'settings.php'   ? 'active' : '' ?>">
        <span class="icon">⚙️</span>
        Settings
      </a>
      <a href="./auth/logout.php">
        <svg class="ico" viewBox="0 0 256 256" fill="currentColor" aria-hidden="true">
         <path d="M120,216a8,8,0,0,1-8,8H48a8,8,0,0,1-8-8V40a8,8,0,0,1,8-8h64a8,8,0,0,1,0,16H56V208h56A8,8,0,0,1,120,216Zm109.66-93.66-40-40a8,8,0,0,0-11.32,11.32L204.69,120H112a8,8,0,0,0,0,16h92.69l-26.35,26.34a8,8,0,0,0,11.32,11.32l40-40A8,8,0,0,0,229.66,122.34Z"></path>
        </svg>
        Logout
      </a>
    </nav>
  </aside>


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
                    $a = !empty($u['avatar_photo']) ? $publicUploads . htmlspecialchars($u['avatar_photo']) : 'https://placehold.co/32x32?text=%20';
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