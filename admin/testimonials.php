<?php
$pageTitle = 'مدیریت نظرات مشتریان';
require_once __DIR__ . '/includes/header.php';

if (!hasPermission('editor')) {
    redirect(ADMIN_URL . '/index.php');
}

if (isset($_GET['delete'])) {
    remove('testimonials', (int)$_GET['delete']);
    redirect(ADMIN_URL . '/testimonials.php?msg=deleted');
}

$testimonials = getAll('testimonials', '1=1', 'created_at DESC');
$msg = $_GET['msg'] ?? '';
$editItem = isset($_GET['edit']) ? getById('testimonials', (int)$_GET['edit']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken();
    $data = [
        'name' => trim($_POST['name']),
        'role' => trim($_POST['role']),
        'content' => trim($_POST['content']),
        'rating' => (int)$_POST['rating'],
        'status' => isset($_POST['status']) ? 1 : 0,
    ];
    if (isset($_FILES['image']['tmp_name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
        $uploaded = uploadImage($_FILES['image'], 'test_');
        if ($uploaded) $data['image'] = $uploaded;
    }
    if (isset($_POST['id']) && $_POST['id']) {
        update('testimonials', (int)$_POST['id'], $data);
    } else {
        insert('testimonials', $data);
    }
    redirect(ADMIN_URL . '/testimonials.php?msg=saved');
}
?>

<div class="page-title-bar">
    <h1><i class="fas fa-comments"></i> مدیریت نظرات مشتریان</h1>
</div>

<?php if ($msg == 'deleted'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> نظر حذف شد.</div>
<?php endif; ?>
<?php if ($msg == 'saved'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> ذخیره شد.</div>
<?php endif; ?>

<!-- Add/Edit Form -->
<div class="card">
    <div class="card-header">
        <h2><?= $editItem ? 'ویرایش نظر' : 'افزودن نظر جدید' ?></h2>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            <?php if ($editItem): ?>
            <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
            <?php endif; ?>
            <div class="form-row">
                <div class="form-group">
                    <label>نام *</label>
                    <input type="text" name="name" value="<?= sanitize($editItem['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>سمت / نقش</label>
                    <input type="text" name="role" value="<?= sanitize($editItem['role'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>متن نظر *</label>
                <textarea name="content" rows="4" required><?= sanitize($editItem['content'] ?? '') ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>تصویر</label>
                    <div class="upload-area-wrapper">
                        <div class="upload-area" onclick="document.getElementById('testimonial_image').click()">
                            <i class="fas fa-cloud-upload-alt" style="font-size:2rem;margin-bottom:5px;"></i><br>
                            <span>کلیک کنید یا فایل را بکشید</span>
                        </div>
                        <input type="file" name="image" id="testimonial_image" accept="image/*" onchange="previewImage(this, 'imgPreview')" style="display:none;">
                        <div class="upload-preview" id="imgPreview">
                            <?php if (!empty($editItem['image'])): ?>
                            <img src="<?= getImageUrl($editItem['image']) ?>" alt="">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>امتیاز (1-5)</label>
                    <select name="rating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                        <option value="<?= $i ?>" <?= ($editItem['rating'] ?? 5) == $i ? 'selected' : '' ?>><?= $i ?> ستاره</option>
                        <?php endfor; ?>
                    </select>
                    <label style="margin-top:15px;display:flex;align-items:center;gap:8px;">
                        <input type="checkbox" name="status" <?= ($editItem['status'] ?? 1) ? 'checked' : '' ?>> فعال
                    </label>
                </div>
            </div>
            <button type="submit" class="btn-admin btn-green"><i class="fas fa-save"></i> <?= $editItem ? 'بروزرسانی' : 'افزودن' ?></button>
            <?php if ($editItem): ?>
            <a href="<?= ADMIN_URL ?>/testimonials.php" class="btn-admin btn-gray">انصراف</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- List -->
<div class="card">
    <div class="card-body">
        <?php if (count($testimonials) > 0): ?>
        <div class="table-responsive">
            <table>
                <tr><th>تصویر</th><th>نام</th><th>سمت</th><th>متن</th><th>امتیاز</th><th>وضعیت</th><th>عملیات</th></tr>
                <?php foreach ($testimonials as $t): ?>
                <tr>
                    <td>
                        <?php if ($t['image']): ?>
                        <img src="<?= getImageUrl($t['image']) ?>" class="table-img" alt="">
                        <?php else: ?>
                        <span style="color:#aaa">—</span>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= sanitize($t['name']) ?></strong></td>
                    <td><?= sanitize($t['role']) ?></td>
                    <td><?= sanitize(mb_substr($t['content'], 0, 50)) ?>...</td>
                    <td><?php for ($i = 0; $i < $t['rating']; $i++): ?><i class="fas fa-star" style="color:#f90;font-size:0.8rem;"></i><?php endfor; ?></td>
                    <td><span class="badge <?= $t['status'] ? 'badge-success' : 'badge-danger' ?>"><?= $t['status'] ? 'فعال' : 'غیرفعال' ?></span></td>
                    <td>
                        <div class="btn-group">
                            <a href="?edit=<?= $t['id'] ?>" class="btn-admin btn-blue"><i class="fas fa-edit"></i></a>
                            <a href="?delete=<?= $t['id'] ?>" class="btn-admin btn-red" onclick="return confirm('آیا مطمئن هستید؟')"><i class="fas fa-trash"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><i class="fas fa-comments"></i><p>نظری وجود ندارد.</p></div>
        <?php endif; ?>
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
