<?php
require_once __DIR__ . '/includes/init.php';
require __DIR__.'/includes/categories_repository.php';
$catsRepo = new CategoriesRepository();
$cats = $catsRepo->listActive();

$me  = Auth::requireUserOrRedirect('./auth/login.php');

$pid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($pid <= 0) { set_flash('err','Invalid picture.'); header('Location: ./profile.php'); exit; }

$paths = new Paths();

$pictures = new PictureRepository();
$pic = $pictures->getEditableByOwner($pid, $me);
if (!$pic) { set_flash('err','Picture not found or not yours.'); header('Location: ./profile.php'); exit; }

$currentImgUrl = img_from_db($pic['picture_url'] ?? null);

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Edit · Picturesque</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./public/css/main.css">
</head>
<body>
  <div class="create-page">
    <div class="create-header">
      <h1>Edit post</h1>
      <a href="./profile.php" class="btn-ghost pill">Back to profile</a>
    </div>

    <?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>
    <?php if ($m = get_flash('ok')):  ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>

    <form method="post" action="./actions/update_picture.php" enctype="multipart/form-data" class="create-form">
      <input type="hidden" name="picture_id" value="<?= (int)$pic['picture_id'] ?>">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <input type="hidden" name="reset_image" id="resetImage" value="">

      <input id="photo" class="file-input" type="file" name="photo" accept="image/*">

      <div class="dropzone" id="dropzone">
        <div class="dz-empty" id="dzEmpty">
          <svg class="dz-icon" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 16V6m0 0l-4 4m4-4l4 4M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"
              fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
          <p class="dz-text">Drag &amp; drop a new photo or <button type="button" class="dz-link" id="browseBtn">browse</button></p>
        </div>

        <img id="preview" class="dz-preview" alt="Selected image preview" hidden>
        <button type="button" class="dz-remove" id="removeBtn" aria-label="Remove image" hidden>×</button>
      </div>

      <p class="muted" style="margin-top:6px;">Leave image empty to keep the current photo.</p>

      <div class="form-row">
        <label class="label">Title</label>
        <input class="input" type="text" name="title" value="<?= htmlspecialchars($pic['picture_title']) ?>" required>
      </div>

      <div class="form-row">
        <label class="label">Description</label>
        <textarea class="input textarea" name="desc" rows="5"><?= htmlspecialchars($pic['picture_description']) ?></textarea>
      </div>

      <div class="form-row">
  <label class="label">Category</label>
  <select class="input" name="category_id" required>
    <option value="">Choose a category…</option>
    <?php foreach ($cats as $c): ?>
      <option value="<?= (int)$c['category_id'] ?>"
        <?= isset($picture) && (int)$picture['category_id']===(int)$c['category_id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($c['name']) ?>
      </option>
    <?php endforeach; ?>
  </select>
</div>


      <div class="actions">
        <a href="./profile.php" class="btn-ghost wide">Cancel</a>
        <button class="btn-primary wide" type="submit" name="submit">Save changes</button>
      </div>
    </form>
  </div>

  <script>
    const input = document.getElementById('photo');
    const dz = document.getElementById('dropzone');
    const dzEmpty = document.getElementById('dzEmpty');
    const preview = document.getElementById('preview');
    const removeBtn = document.getElementById('removeBtn');
    const browseBtn = document.getElementById('browseBtn');
    const resetInput = document.getElementById('resetImage');
    const originalUrl = "<?= htmlspecialchars($currentImgUrl) ?>";


    (function () {
      preview.src = originalUrl;
      preview.hidden = false;
      removeBtn.hidden = false;
      dzEmpty.hidden = true;
      dz.classList.add('has-image');
    })();

    browseBtn.addEventListener('click', () => input.click());
    dz.addEventListener('click', (e) => { if (e.target === dz) input.click(); });

    input.addEventListener('change', function () {
      const file = this.files[0];
      if (file) setFile(file);
    });

    ['dragenter','dragover'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.add('is-drag'); }));
    ['dragleave','drop'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.remove('is-drag'); }));
    dz.addEventListener('drop', (e) => {
      const file = e.dataTransfer.files[0];
      if (file) setFile(file);
    });

    function setFile(file) {
      if (!file.type.startsWith('image/')) { alert('Please choose an image file.'); return; }
      if (file.size > 10 * 1024 * 1024) { alert('Max size is 10MB.'); return; }

      const reader = new FileReader();
      reader.onload = (e) => {
        preview.src = e.target.result;
        preview.hidden = false;
        removeBtn.hidden = false;
        dzEmpty.hidden = true;
        dz.classList.add('has-image');
      };
      reader.readAsDataURL(file);

      const dt = new DataTransfer();
      dt.items.add(file);
      input.files = dt.files;

      if (resetInput) resetInput.value = "";
    }

    function clearFile() {
      input.value = '';
      preview.src = '';
      preview.hidden = true;
      removeBtn.hidden = true;
      dzEmpty.hidden = false;
      dz.classList.remove('has-image');
      if (resetInput) resetInput.value = "1";
    }

    removeBtn.addEventListener('click', (e) => { e.preventDefault(); clearFile(); });

    const chips = document.getElementById('chips');
    const tagsInput = document.getElementById('tagsInput');
    const addBtn = document.getElementById('addTag');

    function syncTags() {
      const selected = [...chips.querySelectorAll('.chip.is-selected')]
        .map(b => b.textContent.trim())
        .filter(t => t !== '+');
      tagsInput.value = selected.join(',');
    }

    chips.addEventListener('click', (e) => {
      if (e.target.classList.contains('chip') && !e.target.classList.contains('add')) {
        e.target.classList.toggle('is-selected');
        syncTags();
      }
    });

    addBtn.addEventListener('click', () => {
      const val = prompt('Add a tag');
      if (val && val.trim()) {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'chip is-selected';
        b.textContent = val.trim();
        chips.insertBefore(b, addBtn);
        syncTags();
      }
    });
  </script>
</body>
</html>
