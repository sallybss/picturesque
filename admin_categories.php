<?php
require __DIR__ . '/includes/init.php';

$me = Auth::requireUserOrRedirect('./auth/login.php');

$profiles = new ProfileRepository();
$meRow = $profiles->getHeader($me);
$isAdmin = strtolower(trim($meRow['role'] ?? '')) === 'admin';
if (!$isAdmin) { set_flash('err','Admins only.'); header('Location: ./index.php'); exit; }

require __DIR__ . '/includes/categories_repository.php';
$catsRepo = new CategoriesRepository();

$conn = DB::get();
$cats = $conn->query("SELECT category_id, category_name, slug, active FROM categories ORDER BY active DESC, category_name")
    ->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Manage Categories · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css">
</head>
<body>
<?php if ($m = get_flash('ok')): ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>
<?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>

<h1 style="margin:1rem">Categories</h1>

<form action="actions/admin_category_add.php" method="post" style="margin:1rem 1rem 2rem">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
  <label>Name</label>
  <input type="text" name="name" required>
  <button type="submit">Add</button>
</form>

<table style="margin:0 1rem; border-collapse:collapse; width:calc(100% - 2rem)">
  <tr>
    <th style="text-align:left;border-bottom:1px solid #eee;padding:.5rem">ID</th>
    <th style="text-align:left;border-bottom:1px solid #eee;padding:.5rem">Name</th>
    <th style="text-align:left;border-bottom:1px solid #eee;padding:.5rem">Slug</th>
    <th style="text-align:left;border-bottom:1px solid #eee;padding:.5rem">Active</th>
    <th style="text-align:left;border-bottom:1px solid #eee;padding:.5rem">Toggle</th>
  </tr>
  <?php foreach ($cats as $c): ?>
  <tr>
    <td style="padding:.5rem"><?= (int)$c['category_id'] ?></td>
    <td style="padding:.5rem"><?= htmlspecialchars($c['category_name']) ?></td>
    <td style="padding:.5rem"><?= htmlspecialchars($c['slug']) ?></td>
    <td style="padding:.5rem"><?= (int)$c['active'] ? 'Yes' : 'No' ?></td>
    <td style="padding:.5rem">
      <form action="actions/admin_category_toggle.php" method="post" style="display:inline">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" name="category_id" value="<?= (int)$c['category_id'] ?>">
        <button type="submit"><?= (int)$c['active'] ? 'Hide' : 'Show' ?></button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
</body>
</html>
