<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'وبلاگ';

$search = isset($_GET['s']) ? trim($_GET['s']) : '';
$catId = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;

$where = 'status = 1';
if ($search) {
    $where .= " AND (title LIKE '%$search%' OR content LIKE '%$search%' OR excerpt LIKE '%$search%')";
}
if ($catId) {
    $where .= " AND category_id = $catId";
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 9;
$offset = ($page - 1) * $perPage;
$total = countRows('posts', $where);
$totalPages = ceil($total / $perPage);

$posts = getAll('posts', $where, 'created_at DESC', "$perPage OFFSET $offset");
foreach ($posts as &$post) { $post = getLocalizedPost($post); }
$categories = getAll('categories', '1=1', 'sort_order ASC');

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1>وبلاگ</h1>
        <nav class="breadcrumb">
            <a href="<?= SITE_URL ?>/">صفحه اصلی</a>
            <span>/</span>
            <span>وبلاگ</span>
        </nav>
    </div>
</section>

<!-- Blog Content -->
<section class="section blog-page-section">
    <div class="container">
        <div class="blog-layout">
            <div class="blog-main">
                <?php if ($search): ?>
                <div class="search-result-info">
                    <p>نتایج جستجو برای: <strong>"<?= sanitize($search) ?>"</strong></p>
                </div>
                <?php endif; ?>

                <?php if (count($posts) > 0): ?>
                <div class="blog-grid">
                    <?php foreach ($posts as $post): ?>
                    <div class="post-card">
                        <div class="post-image">
                            <a href="<?= SITE_URL ?>/post.php?slug=<?= $post['slug'] ?>">
                                <?= picture($post['image'], $post['title']) ?>
                            </a>
                        </div>
                        <div class="post-details">
                            <div class="post-meta">
                                <span class="post-date"><i class="far fa-calendar"></i> <?= timeAgo($post['created_at']) ?></span>
                                <span class="post-comments"><i class="far fa-eye"></i> <?= $post['views'] ?></span>
                            </div>
                            <h3 class="post-title">
                                <a href="<?= SITE_URL ?>/post.php?slug=<?= $post['slug'] ?>"><?= sanitize($post['title']) ?></a>
                            </h3>
                            <p class="post-excerpt"><?= sanitize(mb_substr($post['excerpt'] ?: strip_tags($post['content']), 0, 150)) ?>...</p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?><?= $search ? '&s=' . urlencode($search) : '' ?>" class="page-link" aria-label="صفحه قبل"><i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?><?= $search ? '&s=' . urlencode($search) : '' ?>" class="page-link <?= $i == $page ? 'active' : '' ?>" aria-label="صفحه <?= $i ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?><?= $search ? '&s=' . urlencode($search) : '' ?>" class="page-link" aria-label="صفحه بعد"><i class="fas fa-chevron-left"></i></a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div class="no-posts">
                    <i class="fas fa-newspaper"></i>
                    <p>مقاله ای یافت نشد.</p>
                </div>
                <?php endif; ?>
            </div>

            <aside class="blog-sidebar">
                <!-- Search -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">جستجو</h3>
                    <form action="<?= SITE_URL ?>/blog.php" method="GET" class="sidebar-search">
                        <input type="text" name="s" placeholder="جستجو در مقالات..." value="<?= sanitize($search) ?>">
                        <button type="submit" aria-label="جستجو"><i class="fas fa-search"></i></button>
                    </form>
                </div>

                <!-- Categories -->
                <?php if (count($categories) > 0): ?>
                <div class="sidebar-widget">
                    <h3 class="widget-title">دسته بندی ها</h3>
                    <ul class="sidebar-categories">
                        <li class="<?= !$catId ? 'active' : '' ?>">
                            <a href="<?= SITE_URL ?>/blog.php">همه</a>
                        </li>
                        <?php foreach ($categories as $cat): ?>
                        <li class="<?= $catId == $cat['id'] ? 'active' : '' ?>">
                            <a href="<?= SITE_URL ?>/blog.php?cat=<?= $cat['id'] ?>"><?= sanitize($cat['name']) ?></a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Recent Posts -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">آخرین مقالات</h3>
                    <ul class="sidebar-posts">
                        <?php $recentPosts = getAll('posts', 'status = 1', 'created_at DESC', 4); ?>
                        <?php foreach ($recentPosts as $rp): ?>
                        <li>
                            <a href="<?= SITE_URL ?>/post.php?slug=<?= $rp['slug'] ?>">
                                <?= picture($rp['image'], $rp['title'], 'sidebar-post-img') ?>
                                <div>
                                    <span class="post-date"><?= timeAgo($rp['created_at']) ?></span>
                                    <span class="post-title-mini"><?= sanitize($rp['title']) ?></span>
                                </div>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
