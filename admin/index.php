<?php
$pageTitle = 'داشبورد';
require_once __DIR__ . '/includes/header.php';

$totalServices = countRows('services');
$totalPosts = countRows('posts');
$totalMessages = countRows('contact_messages');
$unreadMessages = countRows('contact_messages', 'is_read = 0');
$totalTestimonials = countRows('testimonials');
$totalUsers = countRows('users');
$recentMessages = getAll('contact_messages', '1=1', 'created_at DESC', 5);
$recentPosts = getAll('posts', '1=1', 'created_at DESC', 5);
?>

<h1 class="page-title">داشبورد مدیریت</h1>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-cogs"></i></div>
        <div class="stat-info">
            <h3><?= $totalServices ?></h3>
            <p>خدمات</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-newspaper"></i></div>
        <div class="stat-info">
            <h3><?= $totalPosts ?></h3>
            <p>مقالات</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-envelope"></i></div>
        <div class="stat-info">
            <h3><?= $totalMessages ?></h3>
            <p>پیام ها (<?= $unreadMessages ?> خوانده نشده)</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-comments"></i></div>
        <div class="stat-info">
            <h3><?= $totalTestimonials ?></h3>
            <p>نظرات مشتریان</p>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
    <!-- Recent Messages -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-envelope"></i> آخرین پیام‌ها</h2>
            <a href="<?= ADMIN_URL ?>/messages.php" class="btn-admin btn-outline">مشاهده همه</a>
        </div>
        <div class="card-body">
            <?php if (count($recentMessages) > 0): ?>
            <div class="table-responsive">
                <table>
                    <tr><th>نام</th><th>موضوع</th><th>تاریخ</th><th>وضعیت</th></tr>
                    <?php foreach ($recentMessages as $msg): ?>
                    <tr>
                        <td><?= sanitize($msg['name']) ?></td>
                        <td><?= sanitize($msg['subject'] ?: 'بدون موضوع') ?></td>
                        <td><?= timeAgo($msg['created_at']) ?></td>
                        <td><span class="badge <?= $msg['is_read'] ? 'badge-success' : 'badge-warning' ?>"><?= $msg['is_read'] ? 'خوانده شده' : 'جدید' ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php else: ?>
            <p style="text-align:center;color:#aaa;padding:20px;">پیامی وجود ندارد.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Posts -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-newspaper"></i> آخرین مقالات</h2>
            <a href="<?= ADMIN_URL ?>/posts.php" class="btn-admin btn-outline">مشاهده همه</a>
        </div>
        <div class="card-body">
            <?php if (count($recentPosts) > 0): ?>
            <div class="table-responsive">
                <table>
                    <tr><th>عنوان</th><th>بازدید</th><th>تاریخ</th><th>وضعیت</th></tr>
                    <?php foreach ($recentPosts as $rp): ?>
                    <tr>
                        <td><?= sanitize($rp['title']) ?></td>
                        <td><?= $rp['views'] ?></td>
                        <td><?= timeAgo($rp['created_at']) ?></td>
                        <td><span class="badge <?= $rp['status'] ? 'badge-success' : 'badge-danger' ?>"><?= $rp['status'] ? 'فعال' : 'غیرفعال' ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php else: ?>
            <p style="text-align:center;color:#aaa;padding:20px;">مقاله‌ای وجود ندارد.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
