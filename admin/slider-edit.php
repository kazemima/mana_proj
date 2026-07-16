<?php
$pageTitle = 'ویرایش اسلایدر';
require_once __DIR__ . '/includes/header.php';

if (!hasPermission('editor')) {
    redirect(ADMIN_URL . '/index.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$item = $id ? getById('sliders', $id) : null;
if ($id && !$item) redirect(ADMIN_URL . '/sliders.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken();
    $data = [
        'title' => trim($_POST['title']),
        'subtitle' => trim($_POST['subtitle']),
        'description' => trim($_POST['description']),
        'link' => trim($_POST['link']),
        'btn_text' => trim($_POST['btn_text']),
        'sort_order' => (int)$_POST['sort_order'],
        'status' => isset($_POST['status']) ? 1 : 0,
    ];

    if (isset($_FILES['image']['tmp_name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
        $uploaded = uploadImage($_FILES['image'], 'slider_');
        if ($uploaded) $data['image'] = $uploaded;
    }

    if ($id) {
        update('sliders', $id, $data);
    } else {
        insert('sliders', $data);
    }
    redirect(ADMIN_URL . '/sliders.php?msg=saved');
}
?>

<div class="page-title-bar">
    <h1><i class="fas fa-images"></i> <?= $id ? 'ویرایش اسلاید' : 'اسلاید جدید' ?></h1>
    <a href="<?= ADMIN_URL ?>/sliders.php" class="btn-admin btn-gray"><i class="fas fa-arrow-right"></i> بازگشت</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            <div class="form-row">
                <div class="form-group">
                    <label>عنوان</label>
                    <input type="text" name="title" value="<?= sanitize($item['title'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>زیرعنوان</label>
                    <input type="text" name="subtitle" value="<?= sanitize($item['subtitle'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>لینک</label>
                    <input type="text" name="link" value="<?= sanitize($item['link'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>متن دکمه</label>
                    <input type="text" name="btn_text" value="<?= sanitize($item['btn_text'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>توضیحات</label>
                <textarea name="description" rows="3"><?= sanitize($item['description'] ?? '') ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>تصویر</label>
                    <div class="upload-area-wrapper">
                        <div class="upload-area" onclick="document.getElementById('slider_image').click()">
                            <i class="fas fa-cloud-upload-alt" style="font-size:2rem;margin-bottom:5px;"></i><br>
                            <span>کلیک کنید یا فایل را بکشید</span>
                        </div>
                        <input type="file" name="image" id="slider_image" accept="image/*" onchange="previewImage(this, 'imgPreview')" style="display:none;">
                        <div class="upload-preview" id="imgPreview">
                            <?php if (!empty($item['image'])): ?>
                            <img src="<?= getImageUrl($item['image']) ?>" alt="">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>ترتیب</label>
                    <input type="number" name="sort_order" value="<?= $item['sort_order'] ?? 0 ?>">
                    <label style="margin-top:15px;display:flex;align-items:center;gap:8px;">
                        <input type="checkbox" name="status" <?= ($item['status'] ?? 1) ? 'checked' : '' ?>> فعال
                    </label>
                </div>
            </div>
            <button type="submit" class="btn-admin btn-green"><i class="fas fa-save"></i> ذخیره</button>
        </form>
    </div>
</div>

<script>
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) { preview.innerHTML = '<img src="' + e.target.result + '" alt="">'; };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
