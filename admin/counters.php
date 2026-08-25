<?php
$pageTitle = 'مدیریت شمارنده‌ها';
require_once __DIR__ . '/includes/header.php';

if (isset($_GET['delete'])) {
    remove('counter_items', (int)$_GET['delete']);
    redirect(ADMIN_URL . '/counters.php?msg=deleted');
}

$counters = getAll('counter_items', '1=1', 'sort_order ASC');
$msg = $_GET['msg'] ?? '';
$editItem = isset($_GET['edit']) ? getById('counter_items', (int)$_GET['edit']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => trim($_POST['title']),
        'value' => (int)$_POST['value'],
        'suffix' => trim($_POST['suffix']),
        'icon' => trim($_POST['icon']),
        'sort_order' => (int)$_POST['sort_order'],
        'status' => isset($_POST['status']) ? 1 : 0,
    ];
    if (isset($_POST['id']) && $_POST['id']) {
        update('counter_items', (int)$_POST['id'], $data);
    } else {
        insert('counter_items', $data);
    }
    redirect(ADMIN_URL . '/counters.php?msg=saved');
}
?>

<div class="page-title-bar">
    <h1><i class="fas fa-sort-numeric-up"></i> مدیریت شمارنده‌ها</h1>
</div>

<?php if ($msg == 'deleted'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> شمارنده حذف شد.</div>
<?php endif; ?>
<?php if ($msg == 'saved'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> ذخیره شد.</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2><?= $editItem ? 'ویرایش شمارنده' : 'افزودن شمارنده جدید' ?></h2>
    </div>
    <div class="card-body">
        <form method="POST">
            <?php if ($editItem): ?>
            <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
            <?php endif; ?>
            <div class="form-row">
                <div class="form-group">
                    <label>عنوان *</label>
                    <input type="text" name="title" value="<?= sanitize($editItem['title'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>مقدار</label>
                    <input type="number" name="value" value="<?= $editItem['value'] ?? 0 ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>پسوند (مثلا +, K+, %)</label>
                    <input type="text" name="suffix" value="<?= sanitize($editItem['suffix'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>آیکون</label>
                    <input type="text" name="icon" value="<?= sanitize($editItem['icon'] ?? '') ?>" placeholder="fas fa-calendar">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>ترتیب</label>
                    <input type="number" name="sort_order" value="<?= $editItem['sort_order'] ?? 0 ?>">
                </div>
                <div class="form-group">
                    <label style="margin-top:20px;display:flex;align-items:center;gap:8px;">
                        <input type="checkbox" name="status" <?= ($editItem['status'] ?? 1) ? 'checked' : '' ?>> فعال
                    </label>
                </div>
            </div>
            <button type="submit" class="btn-admin btn-green"><i class="fas fa-save"></i> <?= $editItem ? 'بروزرسانی' : 'افزودن' ?></button>
            <?php if ($editItem): ?>
            <a href="<?= ADMIN_URL ?>/counters.php" class="btn-admin btn-gray">انصراف</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (count($counters) > 0): ?>
        <div class="table-responsive">
            <table>
                <tr><th>عنوان</th><th>مقدار</th><th>پسوند</th><th>آیکون</th><th>ترتیب</th><th>وضعیت</th><th>عملیات</th></tr>
                <?php foreach ($counters as $c): ?>
                <tr>
                    <td><strong><?= sanitize($c['title']) ?></strong></td>
                    <td><?= $c['value'] ?></td>
                    <td><?= sanitize($c['suffix']) ?></td>
                    <td><i class="<?= $c['icon'] ?>"></i></td>
                    <td><?= $c['sort_order'] ?></td>
                    <td><span class="badge <?= $c['status'] ? 'badge-success' : 'badge-danger' ?>"><?= $c['status'] ? 'فعال' : 'غیرفعال' ?></span></td>
                    <td>
                        <div class="btn-group">
                            <a href="?edit=<?= $c['id'] ?>" class="btn-admin btn-blue"><i class="fas fa-edit"></i></a>
                            <a href="?delete=<?= $c['id'] ?>" class="btn-admin btn-red" onclick="return confirm('آیا مطمئن هستید؟')"><i class="fas fa-trash"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><i class="fas fa-sort-numeric-up"></i><p>شمارنده‌ای وجود ندارد.</p></div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
