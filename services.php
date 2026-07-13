<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'خدمات';
require_once __DIR__ . '/includes/header.php';
$services = getAll('services', 'status = 1', 'sort_order ASC');
foreach ($services as &$service) { $service = getLocalizedService($service); }
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1>خدمات ما</h1>
        <nav class="breadcrumb">
            <a href="<?= SITE_URL ?>/">صفحه اصلی</a>
            <span>/</span>
            <span>خدمات</span>
        </nav>
    </div>
</section>

<!-- Services -->
<section class="section services-page-section">
    <div class="container">
        <div class="services-grid full-services">
            <?php foreach ($services as $index => $service): ?>
            <a href="<?= SITE_URL ?>/service-detail.php?slug=<?= $service['slug'] ?>" class="service-card full stagger-item" style="--i: <?= $index ?>;" id="service-<?= $service['id'] ?>">
                <div class="service-icon">
                    <i class="<?= $service['icon'] ?>"></i>
                </div>
                <h3 class="service-title"><?= sanitize($service['title']) ?></h3>
                <p class="service-desc"><?= nl2br(sanitize($service['description'])) ?></p>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
