<?php
$pageTitle = 'مدیریت دسته بندی‌ها';
require_once __DIR__ . '/includes/header.php';

if (!hasPermission('author')) {
    redirect(ADMIN_URL . '/index.php');
}

if (isset($_GET['delete'])) {
    remove('categories', (int)$_GET['delete']);
    redirect(ADMIN_URL . '/categories.php?msg=deleted');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken();
    $action = $_POST['action'] ?? '';
    if ($action == 'add') {
        insert('categories', [
            'name' => trim($_POST['name']),
            'slug' => makeSlug(trim($_POST['slug'] ?: $_POST['name'])),
            'sort_order' => (int)$_POST['sort_order'],
        ]);
    } elseif ($action == 'edit') {
        update('categories', (int)$_POST['id'], [
            'name' => trim($_POST['name']),
            'slug' => makeSlug(trim($_POST['slug'] ?: $_POST['name'])),
            'sort_order' => (int)$_POST['sort_order'],
        ]);
    }
    redirect(ADMIN_URL . '/categories.php?msg=saved');
}

$categories = getAll('categories', '1=1', 'sort_order ASC');
$msg = $_GET['msg'] ?? '';
$editCat = isset($_GET['edit']) ? getById('categories', (int)$_GET['edit']) : null;
?>

<div class="page-title-bar">
    <h1><i class="fas fa-folder"></i> مدیریت دسته بندی‌ها</h1>
</div>

<?php if ($msg == 'deleted'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> دسته بندی حذف شد.</div>
<?php endif; ?>
<?php if ($msg == 'saved'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> ذخیره شد.</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2><?= $editCat ? 'ویرایش دسته بندی' : 'افزودن دسته بندی جدید' ?></h2>
    </div>
    <div class="card-body">
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="<?= $editCat ? 'edit' : 'add' ?>">
            <?php if ($editCat): ?>
            <input type="hidden" name="id" value="<?= $editCat['id'] ?>">
            <?php endif; ?>
            <div class="form-row">
                <div class="form-group">
                    <label>نام *</label>
                    <input type="text" name="name" value="<?= sanitize($editCat['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>اسلاگ</label>
                    <input type="text" name="slug" value="<?= sanitize($editCat['slug'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>ترتیب</label>
                <input type="number" name="sort_order" value="<?= $editCat['sort_order'] ?? 0 ?>">
            </div>
            <button type="submit" class="btn-admin btn-green"><i class="fas fa-save"></i> <?= $editCat ? 'بروزرسانی' : 'افزودن' ?></button>
            <?php if ($editCat): ?>
            <a href="<?= ADMIN_URL ?>/categories.php" class="btn-admin btn-gray">انصراف</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (count($categories) > 0): ?>
        <div class="table-responsive">
            <table>
                <tr><th>نام</th><th>اسلاگ</th><th>ترتیب</th><th>عملیات</th></tr>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><strong><?= sanitize($cat['name']) ?></strong></td>
                    <td><?= sanitize($cat['slug']) ?></td>
                    <td><?= $cat['sort_order'] ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="?edit=<?= $cat['id'] ?>" class="btn-admin btn-blue"><i class="fas fa-edit"></i></a>
                            <a href="?delete=<?= $cat['id'] ?>" class="btn-admin btn-red" onclick="return confirm('آیا مطمئن هستید؟')"><i class="fas fa-trash"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><i class="fas fa-folder"></i><p>دسته بندی وجود ندارد.</p></div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
