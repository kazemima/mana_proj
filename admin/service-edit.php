<?php
$pageTitle = 'ویرایش خدمت';
require_once __DIR__ . '/includes/header.php';

if (!hasPermission('editor')) {
    redirect(ADMIN_URL . '/index.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$item = $id ? getById('services', $id) : null;
if ($id && !$item) redirect(ADMIN_URL . '/services.php');

$activeLangs = getActiveLanguages();
$defaultLangCode = getDefaultLang();
$nonDefaultLangs = array_filter($activeLangs, fn($l) => $l['code'] !== $defaultLangCode);
$existingTranslations = $id ? getServiceTranslationsAll($id) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken();
    $data = [
        'title' => trim($_POST['title']),
        'description' => trim($_POST['description']),
        'short_description' => trim($_POST['short_description']),
        'detail_content' => $_POST['detail_content'] ?? '',
        'icon' => trim($_POST['icon']),
        'slug' => makeSlug(trim($_POST['slug'] ?: $_POST['title'])),
        'sort_order' => (int)$_POST['sort_order'],
        'status' => isset($_POST['status']) ? 1 : 0,
        'meta_title' => trim($_POST['meta_title']),
        'meta_description' => trim($_POST['meta_description']),
        'meta_keywords' => trim($_POST['meta_keywords']),
    ];

    if (isset($_FILES['image']['tmp_name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
        $uploaded = uploadImage($_FILES['image'], 'service_');
        if ($uploaded) $data['image'] = $uploaded;
    }

    if ($id) {
        update('services', $id, $data);
    } else {
        $data['created_at'] = date('Y-m-d H:i:s');
        $id = insert('services', $data);
    }

    // Save translations
    $transLangCodes = array_map(fn($l) => $l['code'], $nonDefaultLangs);
    $transData = $_POST['translations'] ?? [];
    saveServiceTranslations($id, $transLangCodes, $transData);

    redirect(ADMIN_URL . '/services.php?msg=saved');
}
?>

<div class="page-title-bar">
    <h1><i class="fas fa-cogs"></i> <?= $id ? 'ویرایش خدمت' : 'خدمت جدید' ?></h1>
    <a href="<?= ADMIN_URL ?>/services.php" class="btn-admin btn-gray"><i class="fas fa-arrow-right"></i> بازگشت</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            <div class="form-row">
                <div class="form-group">
                    <label>عنوان *</label>
                    <input type="text" name="title" value="<?= sanitize($item['title'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>آیکون (کلاس Font Awesome)</label>
                    <input type="text" name="icon" value="<?= sanitize($item['icon'] ?? '') ?>" placeholder="مثلا: fas fa-cog">
                    <div class="hint">مثلا: fas fa-cog, a-icon-user</div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>اسلاگ</label>
                    <input type="text" name="slug" value="<?= sanitize($item['slug'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>ترتیب</label>
                    <input type="number" name="sort_order" value="<?= $item['sort_order'] ?? 0 ?>">
                </div>
            </div>
            <div class="form-group">
                <label>توضیح کوتاه</label>
                <input type="text" name="short_description" value="<?= sanitize($item['short_description'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>توضیحات کامل</label>
                <textarea name="description" rows="5"><?= sanitize($item['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>محتوای صفحه توضیحات (HTML پشتیبانی می‌شود)</label>
                <textarea name="detail_content" rows="12" style="min-height:250px;font-family:monospace;direction:ltr;text-align:left;"><?= sanitize($item['detail_content'] ?? '') ?></textarea>
                <div class="hint">می‌توانید از HTML استفاده کنید. مثال: &lt;ul&gt;&lt;li&gt;&lt;strong&gt;عنوان&lt;/strong&gt;: توضیحات&lt;/li&gt;&lt;/ul&gt;</div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>تصویر</label>
                    <div class="upload-area-wrapper">
                        <div class="upload-area" onclick="document.getElementById('service_image').click()">
                            <i class="fas fa-cloud-upload-alt" style="font-size:2rem;margin-bottom:5px;"></i><br>
                            <span>کلیک کنید یا فایل را بکشید</span>
                        </div>
                        <input type="file" name="image" id="service_image" accept="image/*" onchange="previewImage(this, 'imgPreview')" style="display:none;">
                        <div class="upload-preview" id="imgPreview">
                            <?php if (!empty($item['image'])): ?>
                            <img src="<?= getImageUrl($item['image']) ?>" alt="">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label style="margin-top:20px;display:flex;align-items:center;gap:8px;">
                        <input type="checkbox" name="status" <?= ($item['status'] ?? 1) ? 'checked' : '' ?>> فعال
                    </label>
                </div>
            </div>

            <!-- Translation Tabs -->
            <?php if (count($nonDefaultLangs) > 0): ?>
            <div class="card" style="margin-top:20px;">
                <div class="card-header">
                    <h2><i class="fas fa-language"></i> ترجمه‌ها</h2>
                </div>
                <div class="card-body">
                    <div class="lang-tabs">
                        <?php foreach ($nonDefaultLangs as $lang): ?>
                        <button type="button" class="lang-tab-btn" onclick="switchLangTab('<?= $lang['code'] ?>')" id="tabBtn-<?= $lang['code'] ?>">
                            <?= $lang['flag'] ?> <?= $lang['native_name'] ?>
                            <?php if (!empty($existingTranslations[$lang['code']])): ?>
                            <span class="badge badge-success" style="font-size:0.6rem;margin-right:4px;">✓</span>
                            <?php endif; ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <?php foreach ($nonDefaultLangs as $lang): ?>
                    <div class="lang-tab-content" id="langTab-<?= $lang['code'] ?>" style="display:none;">
                        <div class="lang-tab-header">
                            <strong><?= $lang['flag'] ?> ترجمه به <?= $lang['native_name'] ?></strong>
                        </div>
                        <?php $tr = $existingTranslations[$lang['code']] ?? []; ?>
                        <div class="form-group">
                            <label>عنوان</label>
                            <input type="text" name="translations[<?= $lang['code'] ?>][title]" value="<?= sanitize($tr['title'] ?? '') ?>" placeholder="عنوان به <?= $lang['native_name'] ?>">
                        </div>
                        <div class="form-group">
                            <label>توضیحات کوتاه</label>
                            <input type="text" name="translations[<?= $lang['code'] ?>][short_description]" value="<?= sanitize($tr['short_description'] ?? '') ?>" placeholder="توضیح کوتاه">
                        </div>
                        <div class="form-group">
                            <label>توضیحات</label>
                            <textarea name="translations[<?= $lang['code'] ?>][description]" rows="4" placeholder="توضیحات"><?= $tr['description'] ?? '' ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>محتوای صفحه توضیحات</label>
                            <textarea name="translations[<?= $lang['code'] ?>][detail_content]" rows="10" style="min-height:200px;" placeholder="محتوا (HTML)"><?= $tr['detail_content'] ?? '' ?></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>عنوان SEO</label>
                                <input type="text" name="translations[<?= $lang['code'] ?>][meta_title]" value="<?= sanitize($tr['meta_title'] ?? '') ?>" placeholder="عنوان SEO">
                            </div>
                            <div class="form-group">
                                <label>توضیحات SEO</label>
                                <input type="text" name="translations[<?= $lang['code'] ?>][meta_description]" value="<?= sanitize($tr['meta_description'] ?? '') ?>" placeholder="توضیحات SEO">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- SEO Section -->
            <div class="card" style="margin-top:20px;">
                <div class="card-header"><h2><i class="fas fa-search"></i> تنظیمات SEO</h2></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>عنوان SEO (Meta Title)</label>
                        <input type="text" name="meta_title" value="<?= sanitize($item['meta_title'] ?? '') ?>" placeholder="خالی بگذارید تا از عنوان خدمت استفاده شود">
                    </div>
                    <div class="form-group">
                        <label>توضیحات SEO (Meta Description)</label>
                        <textarea name="meta_description" rows="3" placeholder="خالی بگذارید تا از توضیحات خدمت استفاده شود"><?= sanitize($item['meta_description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>کلمات کلیدی</label>
                        <input type="text" name="meta_keywords" value="<?= sanitize($item['meta_keywords'] ?? '') ?>" placeholder="کلمات با کاما جدا شوند">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-admin btn-green"><i class="fas fa-save"></i> ذخیره</button>
        </form>
    </div>
</div>

<script>
// ========== Language Tabs ==========
let currentLangTab = null;

function switchLangTab(code) {
    document.querySelectorAll('.lang-tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.lang-tab-btn').forEach(el => el.classList.remove('active'));
    const tab = document.getElementById('langTab-' + code);
    const btn = document.getElementById('tabBtn-' + code);
    if (tab) tab.style.display = 'block';
    if (btn) btn.classList.add('active');
    currentLangTab = code;
}

document.addEventListener('DOMContentLoaded', function() {
    const firstBtn = document.querySelector('.lang-tab-btn');
    if (firstBtn) {
        const code = firstBtn.id.replace('tabBtn-', '');
        switchLangTab(code);
    }
});

function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) { preview.innerHTML = '<img src="' + e.target.result + '" alt="">'; };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
