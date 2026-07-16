<?php
$pageTitle = 'مدیریت خدمات';
require_once __DIR__ . '/includes/header.php';

if (!hasPermission('editor')) {
    redirect(ADMIN_URL . '/index.php');
}

if (isset($_GET['delete'])) {
    remove('services', (int)$_GET['delete']);
    redirect(ADMIN_URL . '/services.php?msg=deleted');
}

$services = getAll('services', '1=1', 'sort_order ASC');
$msg = $_GET['msg'] ?? '';
?>

<div class="page-title-bar">
    <h1><i class="fas fa-cogs"></i> مدیریت خدمات</h1>
    <a href="<?= ADMIN_URL ?>/service-edit.php" class="btn-admin btn-green"><i class="fas fa-plus"></i> خدمت جدید</a>
</div>

<?php if ($msg == 'deleted'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> خدمت حذف شد.</div>
<?php endif; ?>
<?php if ($msg == 'saved'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> خدمت ذخیره شد.</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <?php if (count($services) > 0): ?>
        <div class="table-responsive">
            <table>
                <tr><th>آیکون</th><th>عنوان</th><th>توضیح کوتاه</th><th>ترتیب</th><th>وضعیت</th><th>عملیات</th></tr>
                <?php foreach ($services as $service): ?>
                <tr>
                    <td><i class="<?= $service['icon'] ?>" style="font-size:1.5rem;color:#6dc051;"></i></td>
                    <td><strong><?= sanitize($service['title']) ?></strong></td>
                    <td><?= sanitize(mb_substr($service['short_description'] ?: $service['description'], 0, 60)) ?></td>
                    <td><?= $service['sort_order'] ?></td>
                    <td><span class="badge <?= $service['status'] ? 'badge-success' : 'badge-danger' ?>"><?= $service['status'] ? 'فعال' : 'غیرفعال' ?></span></td>
                    <td>
                        <div class="btn-group">
                            <a href="<?= ADMIN_URL ?>/service-edit.php?id=<?= $service['id'] ?>" class="btn-admin btn-blue"><i class="fas fa-edit"></i></a>
                            <a href="?delete=<?= $service['id'] ?>" class="btn-admin btn-red" onclick="return confirm('آیا مطمئن هستید؟')"><i class="fas fa-trash"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><i class="fas fa-cogs"></i><p>خدمتی وجود ندارد.</p></div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
