<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'صفحه اصلی';
require_once __DIR__ . '/includes/header.php';

$sliders = getAll('sliders', 'status = 1', 'sort_order ASC');
$services = getAll('services', 'status = 1', 'sort_order ASC');
foreach ($services as &$service) { $service = getLocalizedService($service); }
$counters = getAll('counter_items', 'status = 1', 'sort_order ASC');
$testimonials = getAll('testimonials', 'status = 1', 'created_at DESC', 5);
$posts = getAll('posts', 'status = 1', 'created_at DESC', 3);
foreach ($posts as &$post) { $post = getLocalizedPost($post); }
?>

<!-- Hero Slider -->
<section class="hero-slider" id="heroSlider">
    <div class="slider-wrapper">
        <?php if (count($sliders) > 0): ?>
        <?php foreach ($sliders as $index => $slide): ?>
        <div class="slide <?= $index == 0 ? 'active' : '' ?>" data-index="<?= $index ?>">
            <?php if ($index == 0): ?>
            <!-- First slide uses <img> for LCP -->
            <div class="slide-bg">
                <?= picture($slide['image'], $slide['title'], 'slide-img', 'high') ?>
            </div>
            <?php else: ?>
            <div class="slide-bg" style="background-image: url('<?= getImageUrl($slide['image']) ?>')"></div>
            <?php endif; ?>
            <div class="slide-content">
                <div class="container">
                    <div class="slide-inner">
                        <h2 class="slide-subtitle anim-fade-up"><?= sanitize($slide['subtitle']) ?></h2>
                        <h1 class="slide-title anim-fade-up delay-1"><?= sanitize($slide['title']) ?></h1>
                        <?php if ($slide['btn_text']): ?>
                        <a href="<?= $slide['link'] ? SITE_URL . '/' . ltrim($slide['link'], '/') : '#' ?>" class="btn btn-primary anim-fade-up delay-2">
                            <?= sanitize($slide['btn_text']) ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php if (count($sliders) > 1): ?>
    <div class="slider-nav">
        <button class="slider-prev" aria-label="قبلی"><i class="fas fa-chevron-right"></i></button>
        <button class="slider-next" aria-label="بعدی"><i class="fas fa-chevron-left"></i></button>
    </div>
    <div class="slider-dots">
        <?php foreach ($sliders as $index => $slide): ?>
        <button class="dot <?= $index == 0 ? 'active' : '' ?>" data-index="<?= $index ?>" aria-label="اسلاید <?= $index + 1 ?>"></button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<!-- Services Section -->
<section class="section services-section" id="services">
    <div class="container">
        <div class="section-header text-center reveal">
            <h2 class="section-title"><?= t('our_services', 'خدمات ما') ?></h2>
            <p class="section-subtitle"><?= t('explore_subsystems', 'زیر سیستم های ما را بررسی کنید') ?></p>
        </div>
        <div class="services-grid">
            <?php foreach ($services as $index => $service): ?>
            <a href="<?= SITE_URL ?>/service-detail.php?slug=<?= $service['slug'] ?>" class="service-card stagger-item" style="--i: <?= $index ?>;" id="service-<?= $service['id'] ?>">
                <div class="service-icon">
                    <i class="<?= $service['icon'] ?>"></i>
                </div>
                <h3 class="service-title"><?= sanitize($service['title']) ?></h3>
                <p class="service-desc"><?= sanitize($service['short_description'] ?: $service['description']) ?></p>
                <div class="service-overlay">
                    <p><?= sanitize($service['description']) ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- About / Strategy Section -->
<section class="section strategy-section">
    <div class="container">
        <div class="strategy-grid">
            <div class="strategy-content reveal-left">
                <h2 class="section-title"><?= t('what_we_do', 'ما چه کاری میکنیم') ?></h2>
                <h3 class="strategy-subtitle"><?= t('about_strategic_management', 'درباره مدیریت استراتژیک چه میدانید؟') ?></h3>
                <p><?= nl2br(sanitize(getSetting('strategy_text'))) ?></p>
                <a href="<?= SITE_URL ?>/about.php" class="btn btn-primary"><?= t('about_us', 'درباره ما') ?></a>
            </div>
            <div class="strategy-image reveal-right">
                <?php if (getSetting('strategy_image')): ?>
                <?= picture(getSetting('strategy_image'), 'درباره ما') ?>
                <?php else: ?>
                <div class="strategy-placeholder">
                    <i class="fas fa-chart-line"></i>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Counter Section -->
<section class="section counter-section">
    <div class="container">
        <div class="counter-grid">
            <?php foreach ($counters as $counter): ?>
            <div class="counter-item reveal-scale">
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

<!-- Testimonials Section -->
<section class="section testimonials-section">
    <div class="container">
        <div class="section-header text-center reveal">
            <h2 class="section-title">نظرات مشتریان</h2>
        </div>
        <div class="testimonials-slider">
            <div class="testimonials-track" id="testimonialsTrack">
                <?php foreach ($testimonials as $testimonial): ?>
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <i class="fas fa-quote-right quote-icon"></i>
                        <p><?= nl2br(sanitize($testimonial['content'])) ?></p>
                    </div>
                    <div class="testimonial-author">
                        <div class="author-info">
                            <strong><?= sanitize($testimonial['name']) ?></strong>
                            <span><?= sanitize($testimonial['role']) ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="testimonial-nav">
                <button class="testimonial-prev" aria-label="قبلی"><i class="fas fa-chevron-right"></i></button>
                <button class="testimonial-next" aria-label="بعدی"><i class="fas fa-chevron-left"></i></button>
            </div>
        </div>
    </div>
</section>

<!-- Blog Section -->
<section class="section blog-section">
    <div class="container">
        <div class="section-header text-center reveal">
            <h2 class="section-title"><?= t('our_blog', ' وبلاگ ما') ?></h2>
            <p class="section-subtitle"><?= t('view_articles', 'مقالات ما را ببینید') ?></p>
        </div>
        <div class="blog-grid">
            <?php foreach ($posts as $index => $post): ?>
            <div class="post-card stagger-item" style="--i: <?= $index ?>;">
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
                    <p class="post-excerpt"><?= sanitize(mb_substr($post['excerpt'] ?: strip_tags($post['content']), 0, 120)) ?>...</p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($posts) > 0): ?>
        <div class="text-center" style="margin-top: 2rem;">
            <a href="<?= SITE_URL ?>/blog.php" class="btn btn-outline">مشاهده همه مقالات</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
