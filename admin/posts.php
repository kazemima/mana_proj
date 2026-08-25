<?php
$pageTitle = 'مدیریت مقالات';
require_once __DIR__ . '/includes/header.php';

if (isset($_GET['delete'])) {
    remove('posts', (int)$_GET['delete']);
    redirect(ADMIN_URL . '/posts.php?msg=deleted');
}

$posts = getAll('posts', '1=1', 'created_at DESC');
$msg = $_GET['msg'] ?? '';
?>

<div class="page-title-bar">
    <h1><i class="fas fa-newspaper"></i> مدیریت مقالات</h1>
    <a href="<?= ADMIN_URL ?>/post-edit.php" class="btn-admin btn-green"><i class="fas fa-plus"></i> مقاله جدید</a>
</div>

<?php if ($msg == 'deleted'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> مقاله حذف شد.</div>
<?php endif; ?>
<?php if ($msg == 'saved'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> مقاله ذخیره شد.</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <?php if (count($posts) > 0): ?>
        <div class="table-responsive">
            <table>
                <tr><th>تصویر</th><th>عنوان</th><th>نویسنده</th><th>بازدید</th><th>تاریخ</th><th>وضعیت</th><th>عملیات</th></tr>
                <?php foreach ($posts as $post): ?>
                <tr>
                    <td>
                        <?php if ($post['image']): ?>
                        <img src="<?= getImageUrl($post['image']) ?>" class="table-img" alt="">
                        <?php else: ?>
                        <span style="color:#aaa">—</span>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= sanitize($post['title']) ?></strong></td>
                    <td><?= sanitize($post['author']) ?></td>
                    <td><?= $post['views'] ?></td>
                    <td><?= timeAgo($post['created_at']) ?></td>
                    <td><span class="badge <?= $post['status'] ? 'badge-success' : 'badge-danger' ?>"><?= $post['status'] ? 'فعال' : 'غیرفعال' ?></span></td>
                    <td>
                        <div class="btn-group">
                            <a href="<?= ADMIN_URL ?>/post-edit.php?id=<?= $post['id'] ?>" class="btn-admin btn-blue"><i class="fas fa-edit"></i></a>
                            <a href="?delete=<?= $post['id'] ?>" class="btn-admin btn-red" onclick="return confirm('آیا مطمئن هستید؟')"><i class="fas fa-trash"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><i class="fas fa-newspaper"></i><p>مقاله‌ای وجود ندارد.</p></div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
