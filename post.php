<?php
require_once __DIR__ . '/includes/functions.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if (!$slug) redirect(SITE_URL . '/blog.php');

$post = getBySlug('posts', $slug);
if (!$post || $post['status'] != 1) {
    redirect(SITE_URL . '/blog.php');
}

// Increment views
global $db;
$pdo = $db->getConnection();
$pdo->prepare("UPDATE posts SET views = views + 1 WHERE id = ?")->execute([$post['id']]);
$post['views']++;
$post = getLocalizedPost($post);

$pageTitle = $post['title'];
$seoItem = $post;
require_once __DIR__ . '/includes/header.php';

$relatedPosts = getAll('posts', "status = 1 AND id != {$post['id']}", 'created_at DESC', 3);
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1><?= sanitize($post['title']) ?></h1>
        <nav class="breadcrumb">
            <a href="<?= SITE_URL ?>/">صفحه اصلی</a>
            <span>/</span>
            <a href="<?= SITE_URL ?>/blog.php">وبلاگ</a>
            <span>/</span>
            <span><?= sanitize($post['title']) ?></span>
        </nav>
    </div>
</section>

<!-- Post Content -->
<section class="section single-post-section">
    <div class="container">
        <div class="post-layout">
            <article class="post-content">
                <div class="post-header">
                    <div class="post-meta">
                        <span><i class="far fa-calendar"></i> <?= timeAgo($post['created_at']) ?></span>
                        <span><i class="far fa-user"></i> <?= sanitize($post['author']) ?></span>
                        <span><i class="far fa-eye"></i> <?= $post['views'] ?> بازدید</span>
                    </div>
                    <?php if ($post['image']): ?>
                    <div class="post-featured-image">
                        <img src="<?= getImageUrl($post['image']) ?>" alt="<?= sanitize($post['title']) ?>">
                    </div>
                    <?php endif; ?>
                </div>
                <div class="post-body">
                    <?= $post['content'] ?>
                </div>
            </article>
        </div>

        <?php if (count($relatedPosts) > 0): ?>
        <div class="related-posts">
            <h3 class="related-title">مقالات مرتبط</h3>
            <div class="blog-grid">
                <?php foreach ($relatedPosts as $rp): ?>
                <div class="post-card">
                    <div class="post-image">
                        <a href="<?= SITE_URL ?>/post.php?slug=<?= $rp['slug'] ?>">
                            <img src="<?= getImageUrl($rp['image']) ?>" alt="<?= sanitize($rp['title']) ?>">
                        </a>
                    </div>
                    <div class="post-details">
                        <div class="post-meta">
                            <span class="post-date"><i class="far fa-calendar"></i> <?= timeAgo($rp['created_at']) ?></span>
                        </div>
                        <h3 class="post-title">
                            <a href="<?= SITE_URL ?>/post.php?slug=<?= $rp['slug'] ?>"><?= sanitize($rp['title']) ?></a>
                        </h3>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
