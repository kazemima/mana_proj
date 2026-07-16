<?php
$pageTitle = 'مدیریت کاربران';
require_once __DIR__ . '/includes/header.php';

// Only admin can manage users
if (!hasPermission('admin')) {
    redirect(ADMIN_URL . '/index.php');
}

$roleHierarchy = [
    'admin' => 10,
    'editor' => 7,
    'author' => 5,
    'contributor' => 3,
    'subscriber' => 1,
];

if (isset($_GET['delete'])) {
    $targetUser = getById('users', (int)$_GET['delete']);
    if ($targetUser && $targetUser['id'] != $_SESSION['admin_id']) {
        remove('users', (int)$_GET['delete']);
        redirect(ADMIN_URL . '/users.php?msg=deleted');
    } else {
        redirect(ADMIN_URL . '/users.php?msg=no_permission');
    }
}

$users = getAll('users', '1=1', 'created_at DESC');
$msg = $_GET['msg'] ?? '';
$editItem = isset($_GET['edit']) ? getById('users', (int)$_GET['edit']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken();
    $targetId = (int)($_POST['id'] ?? 0);
    $targetUser = $targetId ? getById('users', $targetId) : null;

    // Prevent editing own role to lower
    if ($targetUser && $targetUser['id'] == $_SESSION['admin_id']) {
        $_POST['role'] = 'admin';
    }

    $data = [
        'username' => trim($_POST['username']),
        'name' => trim($_POST['name']),
        'email' => trim($_POST['email']),
        'role' => trim($_POST['role']),
    ];

    if (!empty($_POST['password'])) {
        if ($_POST['password'] !== ($_POST['password_confirm'] ?? '')) {
            $error_msg = 'رمز عبور و تکرار آن مطابقت ندارند.';
        } else {
            $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
    }

    if (empty($error_msg)) {
        if ($targetId) {
            update('users', $targetId, $data);
        } else {
            if (empty($_POST['password'])) {
                $error_msg = 'رمز عبور الزامی است.';
            } else {
                $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                insert('users', $data);
            }
        }
    }

    if (empty($error_msg)) {
        redirect(ADMIN_URL . '/users.php?msg=saved');
    }
}

$roles = [
    'admin' => 'مدیر کل',
    'editor' => 'ویرایشگر',
    'author' => 'نویسنده',
    'contributor' => 'مشارکت‌کننده',
    'subscriber' => 'مشترک',
];
$roleBadges = [
    'admin' => 'badge-success',
    'editor' => 'badge-info',
    'author' => 'badge-primary',
    'contributor' => 'badge-warning',
    'subscriber' => 'badge-secondary',
];
?>

<div class="page-title-bar">
    <h1><i class="fas fa-users"></i> مدیریت کاربران</h1>
    <button class="btn-admin btn-green" onclick="openUserModal()"><i class="fas fa-plus"></i> افزودن کاربر جدید</button>
</div>

<?php if ($msg == 'deleted'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> کاربر حذف شد.</div>
<?php endif; ?>
<?php if ($msg == 'saved'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> ذخیره شد.</div>
<?php endif; ?>
<?php if ($msg == 'no_permission'): ?>
<div class="alert-admin alert-error"><i class="fas fa-exclamation-circle"></i> شما دسترسی به این عملیات ندارید.</div>
<?php endif; ?>
<?php if (!empty($error_msg)): ?>
<div class="alert-admin alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error_msg ?></div>
<?php endif; ?>

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
                    <td><span class="badge <?= $roleBadges[$u['role']] ?? 'badge-info' ?>"><?= $roles[$u['role']] ?? $u['role'] ?></span></td>
                    <td><?= $u['last_login'] ? timeAgo($u['last_login']) : 'هرگز' ?></td>
                    <td>
                        <div class="btn-group">
                            <?php if ($u['id'] != $_SESSION['admin_id']): ?>
                            <button class="btn-admin btn-blue btn-edit-user" data-user="<?= base64_encode(json_encode($u)) ?>"><i class="fas fa-edit"></i></button>
                            <a href="?delete=<?= $u['id'] ?>" class="btn-admin btn-red" onclick="return confirm('آیا از حذف این کاربر مطمئن هستید؟')"><i class="fas fa-trash"></i></a>
                            <?php else: ?>
                            <span class="btn-admin btn-gray" style="opacity:0.5;cursor:default;" title="خودتان هستید"><i class="fas fa-user"></i></span>
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

<!-- User Modal -->
<div class="menu-modal-overlay" id="userModal">
    <div class="menu-modal">
        <div class="menu-modal-header">
            <h3 id="userModalTitle">افزودن کاربر جدید</h3>
            <button class="menu-modal-close" onclick="closeUserModal()">&times;</button>
        </div>
        <div class="menu-modal-body">
            <form method="POST" id="userForm">
                <?= csrfField() ?>
                <input type="hidden" name="id" id="user_id" value="">
                <div class="form-row">
                    <div class="form-group">
                        <label>نام کاربری *</label>
                        <input type="text" name="username" id="user_username" required>
                    </div>
                    <div class="form-group">
                        <label>نام کامل</label>
                        <input type="text" name="name" id="user_name">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>ایمیل</label>
                        <input type="email" name="email" id="user_email">
                    </div>
                    <div class="form-group">
                        <label>نقش</label>
                        <select name="role" id="user_role">
                            <option value="admin">مدیر کل</option>
                            <option value="editor">ویرایشگر</option>
                            <option value="author">نویسنده</option>
                            <option value="contributor">مشارکت‌کننده</option>
                            <option value="subscriber">مشترک</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>رمز عبور <span id="passwordHint">*</span></label>
                        <input type="password" name="password" id="user_password">
                    </div>
                    <div class="form-group">
                        <label>تکرار رمز عبور</label>
                        <input type="password" name="password_confirm" id="user_password_confirm">
                    </div>
                </div>
                <div id="passwordError" style="color: #f44336; font-size: 0.85rem; margin-bottom: 15px; display: none;">رمز عبور و تکرار آن مطابقت ندارند.</div>
            </form>
        </div>
        <div class="menu-modal-footer">
            <button class="btn-admin btn-green" onclick="submitUserForm()"><i class="fas fa-save"></i> <span id="userSubmitText">افزودن</span></button>
            <button class="btn-admin btn-gray" onclick="closeUserModal()">انصراف</button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
