<?php
$pageTitle = 'مدیریت کاربران';
require_once __DIR__ . '/includes/header.php';

if (isset($_GET['delete'])) {
    remove('users', (int)$_GET['delete']);
    redirect(ADMIN_URL . '/users.php?msg=deleted');
}

$users = getAll('users', '1=1', 'created_at DESC');
$msg = $_GET['msg'] ?? '';
$editItem = isset($_GET['edit']) ? getById('users', (int)$_GET['edit']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => trim($_POST['username']),
        'name' => trim($_POST['name']),
        'email' => trim($_POST['email']),
        'role' => trim($_POST['role']),
    ];

    if (!empty($_POST['password'])) {
        $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
    }

    if (isset($_POST['id']) && $_POST['id']) {
        update('users', (int)$_POST['id'], $data);
    } else {
        if (empty($_POST['password'])) {
            $error_msg = 'رمز عبور الزامی است.';
        } else {
            $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            insert('users', $data);
        }
    }
    redirect(ADMIN_URL . '/users.php?msg=saved');
}
?>

<div class="page-title-bar">
    <h1><i class="fas fa-users"></i> مدیریت کاربران</h1>
</div>

<?php if ($msg == 'deleted'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> کاربر حذف شد.</div>
<?php endif; ?>
<?php if ($msg == 'saved'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> ذخیره شد.</div>
<?php endif; ?>
<?php if (!empty($error_msg)): ?>
<div class="alert-admin alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error_msg ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2><?= $editItem ? 'ویرایش کاربر' : 'افزودن کاربر جدید' ?></h2>
    </div>
    <div class="card-body">
        <form method="POST">
            <?php if ($editItem): ?>
            <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
            <?php endif; ?>
            <div class="form-row">
                <div class="form-group">
                    <label>نام کاربری *</label>
                    <input type="text" name="username" value="<?= sanitize($editItem['username'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>نام کامل</label>
                    <input type="text" name="name" value="<?= sanitize($editItem['name'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>ایمیل</label>
                    <input type="email" name="email" value="<?= sanitize($editItem['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>رمز عبور <?= $editItem ? '(خالی بگذارید تا تغییر نکند)' : '*' ?></label>
                    <input type="password" name="password" <?= $editItem ? '' : 'required' ?>>
                </div>
            </div>
            <div class="form-group">
                <label>نقش</label>
                <select name="role">
                    <option value="admin" <?= ($editItem['role'] ?? '') == 'admin' ? 'selected' : '' ?>>مدیر</option>
                    <option value="editor" <?= ($editItem['role'] ?? '') == 'editor' ? 'selected' : '' ?>>ویرایشگر</option>
                </select>
            </div>
            <button type="submit" class="btn-admin btn-green"><i class="fas fa-save"></i> <?= $editItem ? 'بروزرسانی' : 'افزودن' ?></button>
            <?php if ($editItem): ?>
            <a href="<?= ADMIN_URL ?>/users.php" class="btn-admin btn-gray">انصراف</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (count($users) > 0): ?>
        <div class="table-responsive">
            <table>
                <tr><th>نام کاربری</th><th>نام</th><th>ایمیل</th><th>نقش</th><th>آخرین ورود</th><th>عملیات</th></tr>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><strong><?= sanitize($u['username']) ?></strong></td>
                    <td><?= sanitize($u['name']) ?></td>
                    <td><?= sanitize($u['email']) ?></td>
                    <td><span class="badge badge-info"><?= $u['role'] == 'admin' ? 'مدیر' : 'ویرایشگر' ?></span></td>
                    <td><?= $u['last_login'] ? timeAgo($u['last_login']) : 'هرگز' ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="?edit=<?= $u['id'] ?>" class="btn-admin btn-blue"><i class="fas fa-edit"></i></a>
                            <?php if ($u['id'] != $_SESSION['admin_id']): ?>
                            <a href="?delete=<?= $u['id'] ?>" class="btn-admin btn-red" onclick="return confirm('آیا مطمئن هستید؟')"><i class="fas fa-trash"></i></a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><i class="fas fa-users"></i><p>کاربری وجود ندارد.</p></div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
