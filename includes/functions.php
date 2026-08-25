<?php
require_once __DIR__ . '/database.php';

function getSetting($key, $default = '') {
    global $db;
    $pdo = $db->getConnection();
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['setting_value'] : $default;
}

function setSetting($key, $value) {
    global $db;
    $pdo = $db->getConnection();
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value");
    $stmt->execute([$key, $value]);
}

function getAll($table, $where = "1=1", $order = "sort_order ASC, id DESC", $limit = null) {
    global $db;
    $pdo = $db->getConnection();
    $sql = "SELECT * FROM $table WHERE $where ORDER BY $order";
    if ($limit) $sql .= " LIMIT $limit";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getById($table, $id) {
    global $db;
    $pdo = $db->getConnection();
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getBySlug($table, $slug) {
    global $db;
    $pdo = $db->getConnection();
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE slug = ?");
    $stmt->execute([$slug]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function insert($table, $data) {
    global $db;
    $pdo = $db->getConnection();
    $keys = array_keys($data);
    $cols = implode(', ', $keys);
    $placeholders = implode(', ', array_fill(0, count($keys), '?'));
    $stmt = $pdo->prepare("INSERT INTO $table ($cols) VALUES ($placeholders)");
    $stmt->execute(array_values($data));
    return $pdo->lastInsertId();
}

function update($table, $id, $data) {
    global $db;
    $pdo = $db->getConnection();
    $sets = [];
    $values = [];
    foreach ($data as $k => $v) {
        $sets[] = "$k = ?";
        $values[] = $v;
    }
    $values[] = $id;
    $stmt = $pdo->prepare("UPDATE $table SET " . implode(', ', $sets) . " WHERE id = ?");
    return $stmt->execute($values);
}

function remove($table, $id) {
    global $db;
    $pdo = $db->getConnection();
    $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
    return $stmt->execute([$id]);
}

function countRows($table, $where = "1=1") {
    global $db;
    $pdo = $db->getConnection();
    return $pdo->query("SELECT COUNT(*) FROM $table WHERE $where")->fetchColumn();
}

function makeSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text);
}

function uploadImage($file, $prefix = '') {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return false;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    if (!in_array($ext, $allowed)) return false;
    $name = $prefix . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
    move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $name);
    return $name;
}

function getImageUrl($filename) {
    if (!$filename) return SITE_URL . '/assets/images/placeholder.png';
    if (strpos($filename, 'http') === 0) return $filename;
    return SITE_URL . '/assets/uploads/' . $filename;
}

function redirect($url) {
    if (ob_get_level()) ob_end_clean();
    header("Location: $url");
    exit;
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']) && $_SESSION['admin_id'] > 0;
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect(ADMIN_URL . '/login.php');
    }
}

function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->y > 0) return $diff->y . ' سال پیش';
    if ($diff->m > 0) return $diff->m . ' ماه پیش';
    if ($diff->d > 0) return $diff->d . ' روز پیش';
    if ($diff->h > 0) return $diff->h . ' ساعت پیش';
    if ($diff->i > 0) return $diff->i . ' دقیقه پیش';
    return 'همین الان';
}

// ========== Translation / Language Functions ==========

function getCurrentLang() {
    if (isset($_SESSION['lang'])) return $_SESSION['lang'];
    if (isset($_GET['lang'])) {
        $_SESSION['lang'] = $_GET['lang'];
        return $_SESSION['lang'];
    }
    return 'fa';
}

function setCurrentLang($code) {
    $_SESSION['lang'] = $code;
}

function getDefaultLang() {
    global $db;
    $pdo = $db->getConnection();
    $row = $pdo->query("SELECT code FROM languages WHERE is_default = 1 AND status = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['code'] : 'fa';
}

function getActiveLanguages() {
    global $db;
    $pdo = $db->getConnection();
    return $pdo->query("SELECT * FROM languages WHERE status = 1 ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
}

function getAllLanguages() {
    global $db;
    $pdo = $db->getConnection();
    return $pdo->query("SELECT * FROM languages ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
}

function t($key, $fallback = null) {
    $lang = getCurrentLang();
    if ($lang === getDefaultLang()) return $fallback ?? $key;

    global $db;
    $pdo = $db->getConnection();
    $stmt = $pdo->prepare("SELECT trans_value FROM translations WHERE lang_code = ? AND trans_key = ?");
    $stmt->execute([$lang, $key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && $row['trans_value']) return $row['trans_value'];
    return $fallback ?? $key;
}

function getPostTranslation($postId, $lang) {
    global $db;
    $pdo = $db->getConnection();
    $stmt = $pdo->prepare("SELECT * FROM post_translations WHERE post_id = ? AND lang_code = ?");
    $stmt->execute([$postId, $lang]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getServiceTranslation($serviceId, $lang) {
    global $db;
    $pdo = $db->getConnection();
    $stmt = $pdo->prepare("SELECT * FROM service_translations WHERE service_id = ? AND lang_code = ?");
    $stmt->execute([$serviceId, $lang]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getLocalizedPost($post) {
    $lang = getCurrentLang();
    $defaultLang = getDefaultLang();
    if ($lang === $defaultLang) return $post;

    $trans = getPostTranslation($post['id'], $lang);
    if ($trans) {
        if ($trans['title']) $post['title'] = $trans['title'];
        if ($trans['excerpt']) $post['excerpt'] = $trans['excerpt'];
        if ($trans['content']) $post['content'] = $trans['content'];
        if ($trans['meta_title']) $post['meta_title'] = $trans['meta_title'];
        if ($trans['meta_description']) $post['meta_description'] = $trans['meta_description'];
    }
    return $post;
}

function getLocalizedService($service) {
    $lang = getCurrentLang();
    $defaultLang = getDefaultLang();
    if ($lang === $defaultLang) return $service;

    $trans = getServiceTranslation($service['id'], $lang);
    if ($trans) {
        if ($trans['title']) $service['title'] = $trans['title'];
        if ($trans['description']) $service['description'] = $trans['description'];
        if ($trans['short_description']) $service['short_description'] = $trans['short_description'];
        if ($trans['detail_content']) $service['detail_content'] = $trans['detail_content'];
        if ($trans['meta_title']) $service['meta_title'] = $trans['meta_title'];
        if ($trans['meta_description']) $service['meta_description'] = $trans['meta_description'];
    }
    return $service;
}

function getLangUrl($lang) {
    $url = $_SERVER['REQUEST_URI'];
    $url = preg_replace('/[?&]lang=[a-z]+/', '', $url);
    $separator = strpos($url, '?') !== false ? '&' : '?';
    return $url . $separator . 'lang=' . $lang;
}

// ========== Save Translation Functions ==========

function savePostTranslations($postId, $langCodes, $formData) {
    global $db;
    $pdo = $db->getConnection();
    $stmt = $pdo->prepare("INSERT INTO post_translations (post_id, lang_code, title, excerpt, content, meta_title, meta_description) VALUES (?, ?, ?, ?, ?, ?, ?) ON CONFLICT(post_id, lang_code) DO UPDATE SET title = excluded.title, excerpt = excluded.excerpt, content = excluded.content, meta_title = excluded.meta_title, meta_description = excluded.meta_description");

    foreach ($langCodes as $langCode) {
        $title = trim($formData[$langCode]['title'] ?? '');
        $excerpt = trim($formData[$langCode]['excerpt'] ?? '');
        $content = $formData[$langCode]['content'] ?? '';
        $metaTitle = trim($formData[$langCode]['meta_title'] ?? '');
        $metaDesc = trim($formData[$langCode]['meta_description'] ?? '');

        if ($title || $excerpt || $content) {
            $stmt->execute([$postId, $langCode, $title, $excerpt, $content, $metaTitle, $metaDesc]);
        }
    }
}

function saveServiceTranslations($serviceId, $langCodes, $formData) {
    global $db;
    $pdo = $db->getConnection();
    $stmt = $pdo->prepare("INSERT INTO service_translations (service_id, lang_code, title, description, short_description, detail_content, meta_title, meta_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON CONFLICT(service_id, lang_code) DO UPDATE SET title = excluded.title, description = excluded.description, short_description = excluded.short_description, detail_content = excluded.detail_content, meta_title = excluded.meta_title, meta_description = excluded.meta_description");

    foreach ($langCodes as $langCode) {
        $title = trim($formData[$langCode]['title'] ?? '');
        $description = trim($formData[$langCode]['description'] ?? '');
        $shortDesc = trim($formData[$langCode]['short_description'] ?? '');
        $detailContent = $formData[$langCode]['detail_content'] ?? '';
        $metaTitle = trim($formData[$langCode]['meta_title'] ?? '');
        $metaDesc = trim($formData[$langCode]['meta_description'] ?? '');

        if ($title || $description || $detailContent) {
            $stmt->execute([$serviceId, $langCode, $title, $description, $shortDesc, $detailContent, $metaTitle, $metaDesc]);
        }
    }
}

function getPostTranslationsAll($postId) {
    global $db;
    $pdo = $db->getConnection();
    $stmt = $pdo->prepare("SELECT * FROM post_translations WHERE post_id = ?");
    $stmt->execute([$postId]);
    $result = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $result[$row['lang_code']] = $row;
    }
    return $result;
}

function getServiceTranslationsAll($serviceId) {
    global $db;
    $pdo = $db->getConnection();
    $stmt = $pdo->prepare("SELECT * FROM service_translations WHERE service_id = ?");
    $stmt->execute([$serviceId]);
    $result = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $result[$row['lang_code']] = $row;
    }
    return $result;
}

// ========== Menu Translation Functions ==========

function getMenuTranslation($menuId, $lang) {
    global $db;
    $pdo = $db->getConnection();
    $stmt = $pdo->prepare("SELECT * FROM menu_translations WHERE menu_id = ? AND lang_code = ?");
    $stmt->execute([$menuId, $lang]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getLocalizedMenuItem($item) {
    $lang = getCurrentLang();
    $defaultLang = getDefaultLang();
    if ($lang === $defaultLang) return $item;

    $trans = getMenuTranslation($item['id'], $lang);
    if ($trans && $trans['title']) {
        $item['title'] = $trans['title'];
    }
    return $item;
}

function saveMenuTranslations($menuId, $langCodes, $formData) {
    global $db;
    $pdo = $db->getConnection();
    $stmt = $pdo->prepare("INSERT INTO menu_translations (menu_id, lang_code, title) VALUES (?, ?, ?) ON CONFLICT(menu_id, lang_code) DO UPDATE SET title = excluded.title");

    foreach ($langCodes as $langCode) {
        $title = trim($formData[$langCode]['title'] ?? '');
        if ($title) {
            $stmt->execute([$menuId, $langCode, $title]);
        }
    }
}

function getMenuTranslationsAll($menuId) {
    global $db;
    $pdo = $db->getConnection();
    $stmt = $pdo->prepare("SELECT * FROM menu_translations WHERE menu_id = ?");
    $stmt->execute([$menuId]);
    $result = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $result[$row['lang_code']] = $row;
    }
    return $result;
}

// ========== SEO Functions ==========

function getSeoTitle($item = null) {
    if ($item && !empty($item['meta_title'])) return $item['meta_title'];
    if ($item && !empty($item['title'])) return $item['title'] . ' - ' . getSetting('site_name');
    return getSetting('seo_meta_title', getSetting('site_name'));
}

function getSeoDescription($item = null) {
    if ($item && !empty($item['meta_description'])) return $item['meta_description'];
    if ($item && !empty($item['excerpt'])) return mb_substr(strip_tags($item['excerpt']), 0, 160);
    if ($item && !empty($item['description'])) return mb_substr(strip_tags($item['description']), 0, 160);
    return getSetting('seo_meta_description', getSetting('site_description'));
}

function getSeoKeywords($item = null) {
    if ($item && !empty($item['meta_keywords'])) return $item['meta_keywords'];
    return getSetting('seo_meta_keywords', '');
}

function getSeoOgImage($item = null) {
    if ($item && !empty($item['og_image'])) return getImageUrl($item['og_image']);
    if ($item && !empty($item['image'])) return getImageUrl($item['image']);
    return getSetting('seo_og_image', getImageUrl(getSetting('site_logo')));
}

function getCanonicalUrl($url = null) {
    if ($url) return $url;
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

function renderSeoTags($item = null, $pageUrl = null) {
    $title = getSeoTitle($item);
    $description = getSeoDescription($item);
    $keywords = getSeoKeywords($item);
    $ogImage = getSeoOgImage($item);
    $canonical = getCanonicalUrl($pageUrl);

    $html = '';
    $html .= '<meta name="description" content="' . sanitize($description) . '">' . "\n";
    if ($keywords) {
        $html .= '<meta name="keywords" content="' . sanitize($keywords) . '">' . "\n";
    }
    $html .= '<meta name="robots" content="' . sanitize(getSetting('seo_robots', 'index, follow')) . '">' . "\n";
    $html .= '<link rel="canonical" href="' . $canonical . '">' . "\n";

    // Open Graph
    $html .= '<meta property="og:title" content="' . sanitize($title) . '">' . "\n";
    $html .= '<meta property="og:description" content="' . sanitize($description) . '">' . "\n";
    $html .= '<meta property="og:image" content="' . $ogImage . '">' . "\n";
    $html .= '<meta property="og:url" content="' . $canonical . '">' . "\n";
    $html .= '<meta property="og:type" content="website">' . "\n";
    $html .= '<meta property="og:locale" content="fa_IR">' . "\n";
    $html .= '<meta property="og:site_name" content="' . sanitize(getSetting('site_name')) . '">' . "\n";

    // Twitter Card
    $html .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
    $html .= '<meta name="twitter:title" content="' . sanitize($title) . '">' . "\n";
    $html .= '<meta name="twitter:description" content="' . sanitize($description) . '">' . "\n";
    $html .= '<meta name="twitter:image" content="' . $ogImage . '">' . "\n";

    return $html;
}

function renderSchemaOrg($item = null, $type = 'WebSite') {
    if (!getSetting('seo_schema_org', '1')) return '';

    $schema = [];
    if ($type === 'WebSite') {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => getSetting('site_name'),
            'url' => SITE_URL,
            'logo' => getImageUrl(getSetting('site_logo')),
            'description' => getSetting('site_description'),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => getSetting('site_address'),
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'telephone' => getSetting('site_phone'),
                'email' => getSetting('site_email'),
            ],
        ];
    } elseif ($type === 'Article' && $item) {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $item['title'] ?? '',
            'description' => getSeoDescription($item),
            'image' => getSeoOgImage($item),
            'author' => [
                '@type' => 'Person',
                'name' => $item['author'] ?? 'مدیریت',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => getSetting('site_name'),
                'logo' => ['@type' => 'ImageObject', 'url' => getImageUrl(getSetting('site_logo'))],
            ],
            'datePublished' => $item['created_at'] ?? '',
            'dateModified' => $item['updated_at'] ?? $item['created_at'] ?? '',
            'mainEntityOfPage' => getCanonicalUrl(),
        ];
    } elseif ($type === 'Service' && $item) {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $item['title'] ?? '',
            'description' => getSeoDescription($item),
            'provider' => [
                '@type' => 'Organization',
                'name' => getSetting('site_name'),
            ],
        ];
    } elseif ($type === 'BreadcrumbList') {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $item ?? [],
        ];
    }

    if (empty($schema)) return '';
    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
}

function generateSitemap() {
    global $db;
    $pdo = $db->getConnection();
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    $xml .= '<url>' . "\n";
    $xml .= '  <loc>' . SITE_URL . '/</loc>' . "\n";
    $xml .= '  <changefreq>daily</changefreq>' . "\n";
    $xml .= '  <priority>1.0</priority>' . "\n";
    $xml .= '</url>' . "\n";

    $staticPages = ['about.php' => '0.8', 'services.php' => '0.9', 'blog.php' => '0.8', 'contact.php' => '0.7'];
    foreach ($staticPages as $page => $priority) {
        $xml .= '<url>' . "\n";
        $xml .= '  <loc>' . SITE_URL . '/' . $page . '</loc>' . "\n";
        $xml .= '  <changefreq>weekly</changefreq>' . "\n";
        $xml .= '  <priority>' . $priority . '</priority>' . "\n";
        $xml .= '</url>' . "\n";
    }

    $posts = $pdo->query("SELECT slug, updated_at FROM posts WHERE status = 1")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($posts as $post) {
        $xml .= '<url>' . "\n";
        $xml .= '  <loc>' . SITE_URL . '/post.php?slug=' . urlencode($post['slug']) . '</loc>' . "\n";
        $xml .= '  <lastmod>' . date('Y-m-d', strtotime($post['updated_at'])) . '</lastmod>' . "\n";
        $xml .= '  <changefreq>monthly</changefreq>' . "\n";
        $xml .= '  <priority>0.7</priority>' . "\n";
        $xml .= '</url>' . "\n";
    }

    $services = $pdo->query("SELECT slug FROM services WHERE status = 1")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($services as $service) {
        $xml .= '<url>' . "\n";
        $xml .= '  <loc>' . SITE_URL . '/service-detail.php?slug=' . urlencode($service['slug']) . '</loc>' . "\n";
        $xml .= '  <changefreq>monthly</changefreq>' . "\n";
        $xml .= '  <priority>0.8</priority>' . "\n";
        $xml .= '</url>' . "\n";
    }

    $xml .= '</urlset>';
    return $xml;
}

function generateRobotsTxt() {
    $content = "User-agent: *\n";
    $content .= "Allow: /\n";
    $content .= "Disallow: /admin/\n";
    $content .= "Disallow: /includes/\n";
    $content .= "Disallow: /assets/uploads/\n";
    $content .= "Disallow: /db/\n";
    $content .= "\n";
    $content .= "Sitemap: " . SITE_URL . "/sitemap.xml\n";
    return $content;
}
