<?php
$pageTitle = isset($pageTitle) ? $pageTitle . ' - ' . getSetting('site_name') : getSetting('site_name');

// Language support
$currentLang = getCurrentLang();
$activeLangs = getActiveLanguages();

// Load menu items from database
$pdo = $db->getConnection();
$allMenuItems = $pdo->query("SELECT * FROM menu_items WHERE status = 1 ORDER BY parent_id ASC, sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

function buildFrontMenuTree($items, $parentId = 0) {
    $tree = [];
    foreach ($items as $item) {
        if ($item['parent_id'] == $parentId) {
            $children = buildFrontMenuTree($items, $item['id']);
            $item['children'] = $children;
            $tree[] = $item;
        }
    }
    return $tree;
}
$menuTree = buildFrontMenuTree($allMenuItems);

function resolveUrl($url) {
    if (!$url || $url === '#') return '#';
    if (strpos($url, 'http') === 0) return $url;
    if (strpos($url, '/') === 0) return SITE_URL . $url;
    return SITE_URL . '/' . $url;
}

$currentFile = basename($_SERVER['PHP_SELF']);
$langData = null;
foreach ($activeLangs as $al) {
    if ($al['code'] === $currentLang) { $langData = $al; break; }
}
$langDir = $langData ? $langData['direction'] : 'rtl';
$langCode = $langData ? $langData['code'] : 'fa';

// SEO variables (can be overridden by individual pages)
$seoItem = isset($seoItem) ? $seoItem : null;
$seoTitle = getSeoTitle($seoItem);
$seoDesc = getSeoDescription($seoItem);
?>
<!DOCTYPE html>
<html dir="<?= $langDir ?>" lang="<?= $langCode ?>-IR" prefix="og: http://ogp.me/ns#">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($seoTitle) ?></title>
    <?= renderSeoTags($seoItem) ?>
    <?= renderSchemaOrg($seoItem) ?>
    <link rel="icon" href="<?= getImageUrl(getSetting('site_favicon')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/animations.css">
</head>
<body>
<!-- Top Bar -->
<div class="top-bar">
    <div class="container">
        <div class="top-bar-inner">
            <div class="top-bar-right">
                <a href="mailto:<?= getSetting('site_email') ?>" class="top-bar-item">
                    <i class="fas fa-envelope"></i>
                    <span><?= getSetting('site_email') ?></span>
                </a>
                <span class="divider"></span>
                <a href="tel:<?= getSetting('site_phone') ?>" class="top-bar-item">
                    <i class="fas fa-phone"></i>
                    <span><?= getSetting('site_phone') ?></span>
                </a>
            </div>
            <div class="top-bar-left">
                <?php if (count($activeLangs) > 1): ?>
                <div class="lang-switcher">
                    <?php foreach ($activeLangs as $lang): ?>
                    <a href="<?= getLangUrl($lang['code']) ?>" class="lang-btn <?= $currentLang === $lang['code'] ? 'active' : '' ?>" title="<?= $lang['name'] ?>">
                        <span class="lang-flag"><?= $lang['flag'] ?></span>
                        <span class="lang-name"><?= $lang['native_name'] ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div class="social-icons">
                    <?php if (getSetting('facebook')): ?>
                    <a href="<?= getSetting('facebook') ?>" target="_blank" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                    <?php endif; ?>
                    <?php if (getSetting('twitter')): ?>
                    <a href="<?= getSetting('twitter') ?>" target="_blank" class="social-icon"><i class="fab fa-twitter"></i></a>
                    <?php endif; ?>
                    <?php if (getSetting('linkedin')): ?>
                    <a href="<?= getSetting('linkedin') ?>" target="_blank" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                    <?php endif; ?>
                    <?php if (getSetting('instagram')): ?>
                    <a href="<?= getSetting('instagram') ?>" target="_blank" class="social-icon"><i class="fab fa-instagram"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Header -->
<header class="header" id="header">
    <div class="container">
        <div class="header-inner">
            <button class="mobile-menu-toggle d-lg-none" aria-label="منو موبایل">
                <i class="fas fa-bars"></i>
            </button>
            <a href="<?= SITE_URL ?>" class="logo">
                <?php if (getSetting('site_logo')): ?>
                <img src="<?= getImageUrl(getSetting('site_logo')) ?>" alt="<?= sanitize(getSetting('site_name')) ?>">
                <?php else: ?>
                <span class="logo-text"><?= sanitize(getSetting('site_name')) ?></span>
                <?php endif; ?>
            </a>
            <nav class="main-menu d-none d-lg-block">
                <ul>
                    <?php foreach ($menuTree as $item):
                    $item = getLocalizedMenuItem($item);
                    ?>
                    <li class="<?= $item['children'] ? 'has-submenu' : '' ?> <?= ($currentFile === ltrim($item['url'], '/') && $item['url'] !== '#') ? 'active' : '' ?>">
                        <a href="<?= resolveUrl($item['url']) ?>" <?php if ($item['target'] == '_blank'): ?>target="_blank"<?php endif; ?>>
                            <?php if ($item['icon'] && !$item['children']): ?>
                            <i class="<?= $item['icon'] ?>" style="margin-left:4px;"></i>
                            <?php endif; ?>
                            <?= sanitize($item['title']) ?>
                        </a>
                        <?php if ($item['children']): ?>
                        <ul class="submenu">
                            <?php foreach ($item['children'] as $child):
                            $child = getLocalizedMenuItem($child);
                            ?>
                            <li>
                                <a href="<?= resolveUrl($child['url']) ?>" <?php if ($child['target'] == '_blank'): ?>target="_blank"<?php endif; ?>>
                                    <?php if ($child['icon']): ?>
                                    <i class="<?= $child['icon'] ?>" style="margin-left:5px;"></i>
                                    <?php endif; ?>
                                    <?= sanitize($child['title']) ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <div class="header-actions">
                <button class="search-toggle" aria-label="جستجو">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
    </div>
</header>

<!-- Search Overlay -->
<div class="search-overlay" id="searchOverlay">
    <div class="container">
        <form action="<?= SITE_URL ?>/blog.php" method="GET" class="search-form">
            <input type="text" name="s" placeholder="جستجو در ..." required autocomplete="off">
            <button type="submit" aria-label="جستجو"><i class="fas fa-search"></i></button>
            <button type="button" class="search-close" aria-label="بستن"><i class="fas fa-times"></i></button>
        </form>
    </div>
</div>
