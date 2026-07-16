<?php
$pageTitle = 'مدیریت اسلایدر';
require_once __DIR__ . '/includes/header.php';

if (!hasPermission('editor')) {
    redirect(ADMIN_URL . '/index.php');
}

// Delete
if (isset($_GET['delete'])) {
    remove('sliders', (int)$_GET['delete']);
    redirect(ADMIN_URL . '/sliders.php?msg=deleted');
}

$sliders = getAll('sliders', '1=1', 'sort_order ASC');
$msg = $_GET['msg'] ?? '';
?>

<div class="page-title-bar">
    <h1><i class="fas fa-images"></i> مدیریت اسلایدر</h1>
    <a href="<?= ADMIN_URL ?>/slider-edit.php" class="btn-admin btn-green"><i class="fas fa-plus"></i> اسلاید جدید</a>
</div>

<?php if ($msg == 'deleted'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> اسلاید حذف شد.</div>
<?php endif; ?>
<?php if ($msg == 'saved'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> اسلاید ذخیره شد.</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <?php if (count($sliders) > 0): ?>
        <div class="table-responsive">
            <table>
                <tr><th>تصویر</th><th>عنوان</th><th>زیرعنوان</th><th>متن دکمه</th><th>ترتیب</th><th>وضعیت</th><th>عملیات</th></tr>
                <?php foreach ($sliders as $slide): ?>
                <tr>
                    <td>
                        <?php if ($slide['image']): ?>
                        <img src="<?= getImageUrl($slide['image']) ?>" class="table-img" alt="">
                        <?php else: ?>
                        <span style="color:#aaa">بدون تصویر</span>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= sanitize($slide['title']) ?></strong></td>
                    <td><?= sanitize($slide['subtitle']) ?></td>
                    <td><?= sanitize($slide['btn_text']) ?></td>
                    <td><?= $slide['sort_order'] ?></td>
                    <td><span class="badge <?= $slide['status'] ? 'badge-success' : 'badge-danger' ?>"><?= $slide['status'] ? 'فعال' : 'غیرفعال' ?></span></td>
                    <td>
                        <div class="btn-group">
                            <a href="<?= ADMIN_URL ?>/slider-edit.php?id=<?= $slide['id'] ?>" class="btn-admin btn-blue"><i class="fas fa-edit"></i></a>
                            <a href="?delete=<?= $slide['id'] ?>" class="btn-admin btn-red" onclick="return confirm('آیا مطمئن هستید؟')"><i class="fas fa-trash"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><i class="fas fa-images"></i><p>اسلایدی وجود ندارد.</p></div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
