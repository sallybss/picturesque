<?php
require __DIR__ . '/includes/flash.php';
if (empty($_SESSION['profile_id'])) {
  header('Location: ./auth/login.php');
  exit;
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Create · Picturesque</title>
  <link rel="stylesheet" href="./public/css/main.css">
</head>

<body>
  <div class="create-page">
    <div class="create-header">
      <h1>Create a post</h1>
      <a href="./index.php" class="btn-ghost pill">Back to feed</a>
    </div>

    <?php if ($m = get_flash('err')): ?><div class="flash err"><?= htmlspecialchars($m) ?></div><?php endif; ?>
    <?php if ($m = get_flash('ok')): ?><div class="flash ok"><?= htmlspecialchars($m) ?></div><?php endif; ?>

    <form method="post" action="./actions/post_picture.php" enctype="multipart/form-data" class="create-form">
      <!-- Hidden file input + dropzone with preview -->
      <input id="photo" class="file-input" type="file" name="photo" accept="image/*" required>

      <div class="dropzone" id="dropzone">
        <!-- Empty state -->
        <div class="dz-empty" id="dzEmpty">
          <svg class="dz-icon" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 16V6m0 0l-4 4m4-4l4 4M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"
              fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
          <p class="dz-text">Drag &amp; drop your photo here or <button type="button" class="dz-link" id="browseBtn">browse</button></p>
        </div>

        <!-- Preview state -->
        <img id="preview" class="dz-preview" alt="Selected image preview" hidden>
        <button type="button" class="dz-remove" id="removeBtn" aria-label="Remove image" hidden>×</button>
      </div>

      <div class="form-row">
        <label class="label">Title</label>
        <input class="input" type="text" name="picture_title" placeholder="Unique view" required>
      </div>

      <div class="form-row">
        <label class="label">Description</label>
        <textarea class="input textarea" name="picture_description" rows="5" placeholder="Say something about your photo…"></textarea>
      </div>

      <!-- Tags -->
      <div class="form-row">
        <label class="label">Tags</label>

        <div class="chips" id="chips">
          <button type="button" class="chip">Abstract</button>
          <button type="button" class="chip">Sci-fi</button>
          <button type="button" class="chip">Landscape</button>
          <button type="button" class="chip add" id="addTag">+</button>
        </div>

        <!-- This will carry the selected tags on submit, comma-separated -->
        <input type="hidden" name="tags" id="tagsInput" value="">
      </div>

      <div class="actions">
        <a href="./index.php" class="btn-ghost wide">Cancel</a>
        <button class="btn-primary wide" type="submit" name="submit">Post</button>
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

    // Open file dialog
    browseBtn.addEventListener('click', () => input.click());
    dz.addEventListener('click', (e) => {
      if (e.target === dz) input.click();
    });

    // Handle input change
    input.addEventListener('change', function() {
      const file = this.files[0];
      if (file) setFile(file);
    });

    // Drag & drop
    ['dragenter', 'dragover'].forEach(ev => dz.addEventListener(ev, e => {
      e.preventDefault();
      dz.classList.add('is-drag');
    }));
    ['dragleave', 'drop'].forEach(ev => dz.addEventListener(ev, e => {
      e.preventDefault();
      dz.classList.remove('is-drag');
    }));
    dz.addEventListener('drop', (e) => {
      const file = e.dataTransfer.files[0];
      if (file) setFile(file);
    });

    // Apply file → preview
    function setFile(file) {
      if (!file.type.startsWith('image/')) {
        alert('Please choose an image file.');
        clearFile();
        return;
      }
      if (file.size > 10 * 1024 * 1024) {
        alert('Max size is 10MB.');
        clearFile();
        return;
      }

      const reader = new FileReader();
      reader.onload = (e) => {
        preview.src = e.target.result;
        preview.hidden = false;
        removeBtn.hidden = false;
        dzEmpty.hidden = true;
        dz.classList.add('has-image');
      };
      reader.readAsDataURL(file);

      // ensure dropped file is attached to the input
      if (!input.files.length || input.files[0] !== file) {
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
      }
    }

    // Remove current image
    function clearFile() {
      input.value = '';
      preview.src = '';
      preview.hidden = true;
      removeBtn.hidden = true;
      dzEmpty.hidden = false;
      dz.classList.remove('has-image');
    }
    removeBtn.addEventListener('click', (e) => {
      e.preventDefault();
      clearFile();
    });

    const chips = document.getElementById('chips');
    const tagsInput = document.getElementById('tagsInput');
    const addBtn = document.getElementById('addTag');

    function syncTags() {
      const selected = [...chips.querySelectorAll('.chip.is-selected')]
        .map(c => c.textContent.trim())
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