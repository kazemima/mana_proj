<!-- Footer -->
<footer class="footer">
    <div class="footer-main">
        <div class="container">
            <div class="footer-grid">
                <!-- About -->
                <div class="footer-col">
                    <div class="footer-logo">
                        <?php if (getSetting('site_logo')): ?>
                        <img src="<?= getImageUrl(getSetting('site_logo')) ?>" alt="<?= sanitize(getSetting('site_name')) ?>">
                        <?php else: ?>
                        <span class="logo-text"><?= sanitize(getSetting('site_name')) ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="footer-about"><?= nl2br(sanitize(getSetting('about_text'))) ?></p>
                    <div class="footer-social">
                        <?php if (getSetting('facebook')): ?>
                        <a href="<?= getSetting('facebook') ?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        <?php endif; ?>
                        <?php if (getSetting('twitter')): ?>
                        <a href="<?= getSetting('twitter') ?>" target="_blank"><i class="fab fa-twitter"></i></a>
                        <?php endif; ?>
                        <?php if (getSetting('linkedin')): ?>
                        <a href="<?= getSetting('linkedin') ?>" target="_blank"><i class="fab fa-linkedin-in"></i></a>
                        <?php endif; ?>
                        <?php if (getSetting('instagram')): ?>
                        <a href="<?= getSetting('instagram') ?>" target="_blank"><i class="fab fa-instagram"></i></a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Contact -->
                <div class="footer-col">
                    <h3 class="footer-title"><?= t('contact_info', 'اطلاعات تماس') ?></h3>
                    <ul class="footer-contact">
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?= sanitize(getSetting('site_address')) ?></span>
                        </li>
                        <li>
                            <i class="fas fa-envelope"></i>
                            <a href="mailto:<?= getSetting('site_email') ?>"><?= getSetting('site_email') ?></a>
                        </li>
                        <li>
                            <i class="fas fa-phone"></i>
                            <a href="tel:<?= getSetting('site_phone') ?>"><?= getSetting('site_phone') ?></a>
                        </li>
                    </ul>
                </div>

                <!-- Links -->
                <div class="footer-col">
                    <h3 class="footer-title"><?= t('useful_links', 'لینک های مفید') ?></h3>
                    <ul class="footer-links">
                        <?php foreach ($menuTree as $item):
                        $item = getLocalizedMenuItem($item);
                        ?>
                        <li><a href="<?= resolveUrl($item['url']) ?>" <?php if ($item['target'] == '_blank'): ?>target="_blank"<?php endif; ?>><?= sanitize($item['title']) ?></a></li>
                        <?php endforeach; ?>
                        <li><a href="#">مستندات</a></li>
                        <li><a href="#">حفظ حریم خصوصی</a></li>
                    </ul>
                </div>

                <!-- Blog Posts -->
                <div class="footer-col">
                    <h3 class="footer-title"><?= t('latest_posts', 'آخرین مقالات') ?></h3>
                    <ul class="footer-posts">
                        <?php $footerPosts = getAll('posts', 'status = 1', 'created_at DESC', 3); ?>
                        <?php foreach ($footerPosts as $fp): ?>
                        <li>
                            <a href="<?= SITE_URL ?>/post.php?slug=<?= $fp['slug'] ?>"><?= sanitize($fp['title']) ?></a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <p><?= t('copyright', sanitize(getSetting('copyright'))) ?></p>
        </div>
    </div>
</footer>

<!-- Back to Top -->
<a href="#top" class="back-to-top" id="backToTop" title="بازگشت به بالا">
    <i class="fas fa-chevron-up"></i>
</a>

<!-- Mobile Menu -->
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-header">
        <span>منو</span>
        <button class="mobile-menu-close" aria-label="بستن منو">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <nav class="mobile-nav">
        <ul>
            <?php foreach ($menuTree as $item): ?>
            <li class="<?= $item['children'] ? 'has-submenu' : '' ?>">
                <a href="<?= resolveUrl($item['url']) ?>" <?php if ($item['target'] == '_blank'): ?>target="_blank"<?php endif; ?>>
                    <?= sanitize($item['title']) ?>
                    <?php if ($item['children']): ?>
                    <i class="fas fa-chevron-down"></i>
                    <?php endif; ?>
                </a>
                <?php if ($item['children']): ?>
                <ul class="submenu">
                    <?php foreach ($item['children'] as $child): ?>
                    <li><a href="<?= resolveUrl($child['url']) ?>" <?php if ($child['target'] == '_blank'): ?>target="_blank"<?php endif; ?>><?= sanitize($child['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</div>
<div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
<script src="<?= SITE_URL ?>/assets/js/animations.js"></script>
</body>
</html>
