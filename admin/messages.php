<?php
$pageTitle = 'مدیریت پیام‌ها';
require_once __DIR__ . '/includes/header.php';

if (!hasPermission('editor')) {
    redirect(ADMIN_URL . '/index.php');
}

if (isset($_GET['delete'])) {
    remove('contact_messages', (int)$_GET['delete']);
    redirect(ADMIN_URL . '/messages.php?msg=deleted');
}

if (isset($_GET['read'])) {
    update('contact_messages', (int)$_GET['read'], ['is_read' => 1]);
    redirect(ADMIN_URL . '/messages.php?msg=read');
}

$messages = getAll('contact_messages', '1=1', 'created_at DESC');
$msg = $_GET['msg'] ?? '';
$viewMsg = isset($_GET['view']) ? getById('contact_messages', (int)$_GET['view']) : null;

if ($viewMsg && !$viewMsg['is_read']) {
    update('contact_messages', $viewMsg['id'], ['is_read' => 1]);
    $viewMsg['is_read'] = 1;
}
?>

<div class="page-title-bar">
    <h1><i class="fas fa-envelope"></i> مدیریت پیام‌ها</h1>
</div>

<?php if ($msg == 'deleted'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> پیام حذف شد.</div>
<?php endif; ?>

<?php if ($viewMsg): ?>
<!-- View Message -->
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-envelope-open"></i> مشاهده پیام</h2>
        <a href="<?= ADMIN_URL ?>/messages.php" class="btn-admin btn-gray"><i class="fas fa-arrow-right"></i> بازگشت</a>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-group">
                <label>نام فرستنده</label>
                <p style="padding:10px;background:#f8f9fa;border-radius:6px;"><?= sanitize($viewMsg['name']) ?></p>
            </div>
            <div class="form-group">
                <label>ایمیل</label>
                <p style="padding:10px;background:#f8f9fa;border-radius:6px;"><?= sanitize($viewMsg['email'] ?: '—') ?></p>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>تلفن</label>
                <p style="padding:10px;background:#f8f9fa;border-radius:6px;"><?= sanitize($viewMsg['phone'] ?: '—') ?></p>
            </div>
            <div class="form-group">
                <label>تاریخ ارسال</label>
                <p style="padding:10px;background:#f8f9fa;border-radius:6px;"><?= $viewMsg['created_at'] ?></p>
            </div>
        </div>
        <div class="form-group">
            <label>موضوع</label>
            <p style="padding:10px;background:#f8f9fa;border-radius:6px;"><?= sanitize($viewMsg['subject'] ?: 'بدون موضوع') ?></p>
        </div>
        <div class="form-group">
            <label>متن پیام</label>
            <div style="padding:15px;background:#f8f9fa;border-radius:6px;min-height:100px;line-height:2;">
                <?= nl2br(sanitize($viewMsg['message'])) ?>
            </div>
        </div>
        <a href="?delete=<?= $viewMsg['id'] ?>" class="btn-admin btn-red" onclick="return confirm('آیا مطمئن هستید؟')"><i class="fas fa-trash"></i> حذف</a>
    </div>
</div>
<?php else: ?>
<!-- List -->
<div class="card">
    <div class="card-body">
        <?php if (count($messages) > 0): ?>
        <div class="table-responsive">
            <table>
                <tr><th>نام</th><th>ایمیل</th><th>موضوع</th><th>تاریخ</th><th>وضعیت</th><th>عملیات</th></tr>
                <?php foreach ($messages as $m): ?>
                <tr style="<?= !$m['is_read'] ? 'background:#f8fdf7;' : '' ?>">
                    <td><strong><?= sanitize($m['name']) ?></strong></td>
                    <td><?= sanitize($m['email']) ?></td>
                    <td><?= sanitize($m['subject'] ?: 'بدون موضوع') ?></td>
                    <td><?= timeAgo($m['created_at']) ?></td>
                    <td>
                        <?php if ($m['is_read']): ?>
                        <span class="badge badge-success">خوانده شده</span>
                        <?php else: ?>
                        <span class="badge badge-warning">جدید</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="?view=<?= $m['id'] ?>" class="btn-admin btn-blue"><i class="fas fa-eye"></i></a>
                            <a href="?delete=<?= $m['id'] ?>" class="btn-admin btn-red" onclick="return confirm('آیا مطمئن هستید؟')"><i class="fas fa-trash"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><i class="fas fa-envelope"></i><p>پیامی وجود ندارد.</p></div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
