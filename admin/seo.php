<?php
$pageTitle = 'مدیریت سئو (SEO)';
require_once __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['seo_meta_title', 'seo_meta_description', 'seo_meta_keywords', 'seo_og_image', 'seo_robots', 'seo_google_analytics', 'seo_schema_org'];
    foreach ($fields as $f) {
        if (isset($_POST[$f])) {
            setSetting($f, trim($_POST[$f]));
        }
    }
    redirect(ADMIN_URL . '/seo.php?msg=saved');
}

$msg = $_GET['msg'] ?? '';
$pdo = $db->getConnection();

// SEO Stats
$totalPosts = countRows('posts', 'status = 1');
$postsWithMeta = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 1 AND meta_title != '' AND meta_title IS NOT NULL")->fetchColumn();
$totalServices = countRows('services', 'status = 1');
$servicesWithMeta = $pdo->query("SELECT COUNT(*) FROM services WHERE status = 1 AND meta_title != '' AND meta_title IS NOT NULL")->fetchColumn();
?>

<div class="page-title-bar">
    <h1><i class="fas fa-search"></i> مدیریت سئو (SEO)</h1>
</div>

<?php if ($msg == 'saved'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> تنظیمات سئو ذخیره شد.</div>
<?php endif; ?>

<!-- SEO Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-newspaper"></i></div>
        <div class="stat-info">
            <h3><?= $postsWithMeta ?>/<?= $totalPosts ?></h3>
            <p>مقالات با SEO</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-cogs"></i></div>
        <div class="stat-info">
            <h3><?= $servicesWithMeta ?>/<?= $totalServices ?></h3>
            <p>خدمات با SEO</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-sitemap"></i></div>
        <div class="stat-info">
            <h3><a href="<?= SITE_URL ?>/sitemap.xml" target="_blank" style="color:inherit;">فعال</a></h3>
            <p>Sitemap.xml</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-robot"></i></div>
        <div class="stat-info">
            <h3><a href="<?= SITE_URL ?>/robots.php" target="_blank" style="color:inherit;">فعال</a></h3>
            <p>Robots.txt</p>
        </div>
    </div>
</div>

<form method="POST">
    <!-- Site SEO -->
    <div class="card">
        <div class="card-header"><h2><i class="fas fa-globe"></i> تنظیمات سئوی سایت</h2></div>
        <div class="card-body">
            <div class="form-group">
                <label>عنوان سایت (Meta Title)</label>
                <input type="text" name="seo_meta_title" value="<?= sanitize(getSetting('seo_meta_title')) ?>" placeholder="عنوان پیش‌فرض سایت برای موتورهای جستجو">
                <div class="hint">حداکثر 60 کاراکتر. این عنوان در نتایج جستجو نمایش داده می‌شود.</div>
            </div>
            <div class="form-group">
                <label>توضیحات سایت (Meta Description)</label>
                <textarea name="seo_meta_description" rows="3" placeholder="توضیحات پیش‌فرض سایت برای موتورهای جستجو"><?= sanitize(getSetting('seo_meta_description')) ?></textarea>
                <div class="hint">حداکثر 160 کاراکتر. این متن در نتایج جستجو زیر عنوان نمایش داده می‌شود.</div>
            </div>
            <div class="form-group">
                <label>کلمات کلیدی (Meta Keywords)</label>
                <input type="text" name="seo_meta_keywords" value="<?= sanitize(getSetting('seo_meta_keywords')) ?>" placeholder="کلمات با کاما جدا شوند">
                <div class="hint">کلمات کلیدی سایت را با کاما جدا کنید.</div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>تصویر Open Graph پیش‌فرض</label>
                    <input type="text" name="seo_og_image" value="<?= sanitize(getSetting('seo_og_image')) ?>" placeholder="آدرس تصویر یا خالی برای استفاده از لوگو">
                    <div class="hint">تصویری که هنگام اشتراک‌گذاری سایت در شبکه‌های اجتماعی نمایش داده می‌شود.</div>
                </div>
                <div class="form-group">
                    <label>تنظیمات Robots</label>
                    <select name="seo_robots">
                        <option value="index, follow" <?= getSetting('seo_robots') == 'index, follow' ? 'selected' : '' ?>>index, follow (پیش‌فرض)</option>
                        <option value="noindex, follow" <?= getSetting('seo_robots') == 'noindex, follow' ? 'selected' : '' ?>>noindex, follow</option>
                        <option value="index, nofollow" <?= getSetting('seo_robots') == 'index, nofollow' ? 'selected' : '' ?>>index, nofollow</option>
                        <option value="noindex, nofollow" <?= getSetting('seo_robots') == 'noindex, nofollow' ? 'selected' : '' ?>>noindex, nofollow</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced SEO -->
    <div class="card">
        <div class="card-header"><h2><i class="fas fa-code"></i> تنظیمات پیشرفته</h2></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Schema.org (ساختار یافته)</label>
                    <select name="seo_schema_org">
                        <option value="1" <?= getSetting('seo_schema_org') == '1' ? 'selected' : '' ?>>فعال</option>
                        <option value="0" <?= getSetting('seo_schema_org') == '0' ? 'selected' : '' ?>>غیرفعال</option>
                    </select>
                    <div class="hint">تگ‌های JSON-LD Schema.org برای نمایش بهتر در نتایج جستجو.</div>
                </div>
                <div class="form-group">
                    <label>Google Analytics ID</label>
                    <input type="text" name="seo_google_analytics" value="<?= sanitize(getSetting('seo_google_analytics')) ?>" placeholder="G-XXXXXXXXXX">
                    <div class="hint">شناسه Google Analytics خود را وارد کنید.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- SEO Tips -->
    <div class="card">
        <div class="card-header"><h2><i class="fas fa-lightbulb"></i> نکات سئو</h2></div>
        <div class="card-body">
            <div class="seo-tips">
                <div class="seo-tip <?= $postsWithMeta == $totalPosts ? 'done' : '' ?>">
                    <i class="fas <?= $postsWithMeta == $totalPosts ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    <span>عنوان SEO برای <?= $totalPosts ?> مقاله تنظیم شده (<?= $postsWithMeta ?> از <?= $totalPosts ?>)</span>
                </div>
                <div class="seo-tip <?= $servicesWithMeta == $totalServices ? 'done' : '' ?>">
                    <i class="fas <?= $servicesWithMeta == $totalServices ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    <span>عنوان SEO برای <?= $totalServices ?> خدمت تنظیم شده (<?= $servicesWithMeta ?> از <?= $totalServices ?>)</span>
                </div>
                <div class="seo-tip done">
                    <i class="fas fa-check-circle"></i>
                    <span>Sitemap.xml خودکار فعال است</span>
                </div>
                <div class="seo-tip done">
                    <i class="fas fa-check-circle"></i>
                    <span>Robots.txt خودکار فعال است</span>
                </div>
                <div class="seo-tip <?= getSetting('seo_meta_description') ? 'done' : '' ?>">
                    <i class="fas <?= getSetting('seo_meta_description') ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    <span>توضیحات سایت تنظیم شده</span>
                </div>
                <div class="seo-tip <?= getSetting('seo_schema_org') == '1' ? 'done' : '' ?>">
                    <i class="fas <?= getSetting('seo_schema_org') == '1' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    <span>Schema.org فعال است</span>
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn-admin btn-green" style="margin-bottom:30px;"><i class="fas fa-save"></i> ذخیره تنظیمات سئو</button>
</form>

<style>
.seo-tips { display: flex; flex-direction: column; gap: 10px; }
.seo-tip {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 15px;
    background: #fff3cd; border-radius: 8px;
    font-size: 0.9rem; color: #856404;
}
.seo-tip.done {
    background: #d4edda; color: #155724;
}
.seo-tip i { font-size: 1.1rem; }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
