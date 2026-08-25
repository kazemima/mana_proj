<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'درباره ما';
require_once __DIR__ . '/includes/header.php';
$counters = getAll('counter_items', 'status = 1', 'sort_order ASC');
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1>درباره ما</h1>
        <nav class="breadcrumb">
            <a href="<?= SITE_URL ?>/">صفحه اصلی</a>
            <span>/</span>
            <span>درباره ما</span>
        </nav>
    </div>
</section>

<!-- About Content -->
<section class="section about-section">
    <div class="container">
        <div class="about-grid">
            <div class="about-content">
                <h2>شرکت مدرن اندیشان نوین ابتکار</h2>
                <p><?= nl2br(sanitize(getSetting('about_text'))) ?></p>
            </div>
            <div class="about-image">
                <?php if (getSetting('site_logo')): ?>
                <img src="<?= getImageUrl(getSetting('site_logo')) ?>" alt="درباره ما">
                <?php else: ?>
                <div class="about-placeholder">
                    <i class="fas fa-building"></i>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Strategy -->
<section class="section strategy-section">
    <div class="container">
        <div class="strategy-grid reverse">
            <div class="strategy-content">
                <h2>مدیریت استراتژیک</h2>
                <p><?= nl2br(sanitize(getSetting('strategy_text'))) ?></p>
            </div>
            <div class="strategy-image">
                <div class="strategy-placeholder">
                    <i class="fas fa-bullseye"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Counter -->
<section class="section counter-section">
    <div class="container">
        <div class="counter-grid">
            <?php foreach ($counters as $counter): ?>
            <div class="counter-item">
                <div class="counter-icon">
                    <i class="<?= $counter['icon'] ?>"></i>
                </div>
                <div class="counter-value" data-target="<?= $counter['value'] ?>"><?= $counter['value'] ?></div>
                <div class="counter-suffix"><?= $counter['suffix'] ?></div>
                <div class="counter-title"><?= sanitize($counter['title']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
