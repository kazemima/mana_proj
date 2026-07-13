<?php
require_once __DIR__ . '/includes/functions.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if (!$slug) redirect(SITE_URL . '/services.php');

$pdo = $db->getConnection();
$stmt = $pdo->prepare("SELECT * FROM services WHERE slug = ?");
$stmt->execute([$slug]);
$service = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$service || $service['status'] != 1) {
    redirect(SITE_URL . '/services.php');
}

$service = getLocalizedService($service);
$pageTitle = $service['title'];
$seoItem = $service;
require_once __DIR__ . '/includes/header.php';

// Get related services
$related = getAll('services', "status = 1 AND id != {$service['id']}", 'sort_order ASC', 4);
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1><?= sanitize($service['title']) ?></h1>
        <nav class="breadcrumb">
            <a href="<?= SITE_URL ?>/">صفحه اصلی</a>
            <span>/</span>
            <a href="<?= SITE_URL ?>/services.php">خدمات</a>
            <span>/</span>
            <span><?= sanitize($service['title']) ?></span>
        </nav>
    </div>
</section>

<!-- Service Detail -->
<section class="section service-detail-section">
    <div class="container">
        <div class="service-detail-grid">
            <div class="service-detail-content">
                <div class="service-detail-header">
                    <div class="service-detail-icon">
                        <i class="<?= $service['icon'] ?>"></i>
                    </div>
                    <h2><?= sanitize($service['title']) ?></h2>
                </div>

                <?php if ($service['detail_content']): ?>
                <div class="service-detail-body">
                    <?= $service['detail_content'] ?>
                </div>
                <?php else: ?>
                <div class="service-detail-body">
                    <p><?= nl2br(sanitize($service['description'])) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <aside class="service-detail-sidebar">
                <div class="sidebar-widget">
                    <h3 class="widget-title">سایر خدمات</h3>
                    <ul class="sidebar-services">
                        <?php foreach ($related as $rel): ?>
                        <li>
                            <a href="<?= SITE_URL ?>/service-detail.php?slug=<?= $rel['slug'] ?>">
                                <i class="<?= $rel['icon'] ?>"></i>
                                <span><?= sanitize($rel['title']) ?></span>
                                <i class="fas fa-chevron-left" style="margin-right:auto;font-size:0.7rem;color:#aaa;"></i>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="sidebar-widget">
                    <h3 class="widget-title">نیاز به مشاوره دارید؟</h3>
                    <p style="font-size:0.9rem;color:#666;margin-bottom:15px;">برای کسب اطلاعات بیشتر با ما تماس بگیرید.</p>
                    <a href="<?= SITE_URL ?>/contact.php" class="btn btn-primary" style="width:100%;text-align:center;">تماس با ما</a>
                </div>
            </aside>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
