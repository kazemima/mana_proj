<?php
$pageTitle = 'ویرایش مقاله';
require_once __DIR__ . '/includes/header.php';

if (!hasPermission('author')) {
    redirect(ADMIN_URL . '/index.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$item = $id ? getById('posts', $id) : null;
if ($id && !$item) redirect(ADMIN_URL . '/posts.php');

$categories = getAll('categories', '1=1', 'sort_order ASC');
$activeLangs = getActiveLanguages();
$defaultLangCode = getDefaultLang();
$nonDefaultLangs = array_filter($activeLangs, fn($l) => $l['code'] !== $defaultLangCode);
$existingTranslations = $id ? getPostTranslationsAll($id) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken();
    $data = [
        'title' => trim($_POST['title']),
        'slug' => makeSlug(trim($_POST['slug'] ?: $_POST['title'])),
        'content' => $_POST['content'],
        'excerpt' => trim($_POST['excerpt']),
        'category_id' => (int)$_POST['category_id'],
        'author' => trim($_POST['author']) ?: 'مدیریت',
        'sort_order' => (int)$_POST['sort_order'],
        'status' => isset($_POST['status']) ? 1 : 0,
        'meta_title' => trim($_POST['meta_title']),
        'meta_description' => trim($_POST['meta_description']),
        'meta_keywords' => trim($_POST['meta_keywords']),
        'og_image' => trim($_POST['og_image']),
        'canonical_url' => trim($_POST['canonical_url']),
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    if (isset($_FILES['image']['tmp_name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
        $uploaded = uploadImage($_FILES['image'], 'post_');
        if ($uploaded) $data['image'] = $uploaded;
    }

    if ($id) {
        update('posts', $id, $data);
    } else {
        $data['created_at'] = date('Y-m-d H:i:s');
        $id = insert('posts', $data);
    }

    // Save translations for non-default languages
    $transLangCodes = array_map(fn($l) => $l['code'], $nonDefaultLangs);
    $transData = $_POST['translations'] ?? [];
    savePostTranslations($id, $transLangCodes, $transData);

    redirect(ADMIN_URL . '/posts.php?msg=saved');
}
?>

<div class="page-title-bar">
    <h1><i class="fas fa-newspaper"></i> <?= $id ? 'ویرایش مقاله' : 'مقاله جدید' ?></h1>
    <a href="<?= ADMIN_URL ?>/posts.php" class="btn-admin btn-gray"><i class="fas fa-arrow-right"></i> بازگشت</a>
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
                    <label>اسلاگ</label>
                    <input type="text" name="slug" value="<?= sanitize($item['slug'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>دسته بندی</label>
                    <select name="category_id">
                        <option value="0">بدون دسته بندی</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($item['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>><?= sanitize($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>نویسنده</label>
                    <input type="text" name="author" value="<?= sanitize($item['author'] ?? 'مدیریت') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>خلاصه</label>
                <textarea name="excerpt" rows="3"><?= sanitize($item['excerpt'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>محتوا</label>
                <textarea name="content" rows="15" style="min-height:300px;"><?= $item['content'] ?? '' ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>تصویر شاخص</label>
                    <div class="upload-area-wrapper">
                        <div class="upload-area" onclick="document.getElementById('post_image').click()">
                            <i class="fas fa-cloud-upload-alt" style="font-size:2rem;margin-bottom:5px;"></i><br>
                            <span>کلیک کنید یا فایل را بکشید</span>
                        </div>
                        <input type="file" name="image" id="post_image" accept="image/*" onchange="previewImage(this, 'imgPreview')" style="display:none;">
                        <div class="upload-preview" id="imgPreview">
                            <?php if (!empty($item['image'])): ?>
                            <img src="<?= getImageUrl($item['image']) ?>" alt="">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>ترتیب</label>
                    <input type="number" name="sort_order" value="<?= $item['sort_order'] ?? 0 ?>">
                    <label style="margin-top:15px;display:flex;align-items:center;gap:8px;">
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
                            <label>خلاصه</label>
                            <textarea name="translations[<?= $lang['code'] ?>][excerpt]" rows="2" placeholder="خلاصه به <?= $lang['native_name'] ?>"><?= sanitize($tr['excerpt'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>محتوا</label>
                            <textarea name="translations[<?= $lang['code'] ?>][content]" rows="10" style="min-height:200px;" placeholder="محتوا به <?= $lang['native_name'] ?>"><?= $tr['content'] ?? '' ?></textarea>
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
                <div class="card-header">
                    <h2><i class="fas fa-search"></i> تنظیمات SEO</h2>
                    <button type="button" class="btn-admin btn-blue" onclick="generateSEO()" title="خودکارسازی سئو بر اساس محتوا">
                        <i class="fas fa-magic"></i> خودکارسازی سئو
                    </button>
                </div>
                <div class="card-body">
                    <div id="seoPreview" style="display:none;background:#f0f8f0;border:1px solid #c3e6cb;border-radius:8px;padding:15px;margin-bottom:20px;">
                        <strong style="color:#155724;"><i class="fas fa-check-circle"></i> سئو خودکار اعمال شد:</strong>
                        <div id="seoPreviewDetails" style="margin-top:10px;font-size:0.85rem;color:#555;"></div>
                    </div>
                    <div class="form-group">
                        <label>عنوان SEO (Meta Title) <span class="seo-counter" id="titleCounter"></span></label>
                        <input type="text" name="meta_title" id="seoTitle" value="<?= sanitize($item['meta_title'] ?? '') ?>" placeholder="خالی بگذارید تا از عنوان مقاله استفاده شود">
                        <div class="hint">حداکثر 60 کاراکتر. اگر خالی باشد عنوان مقاله استفاده می‌شود.</div>
                    </div>
                    <div class="form-group">
                        <label>توضیحات SEO (Meta Description) <span class="seo-counter" id="descCounter"></span></label>
                        <textarea name="meta_description" id="seoDescription" rows="3" placeholder="خالی بگذارید تا از خلاصه مقاله استفاده شود"><?= sanitize($item['meta_description'] ?? '') ?></textarea>
                        <div class="hint">حداکثر 160 کاراکتر. اگر خالی باشد از خلاصه مقاله استفاده می‌شود.</div>
                    </div>
                    <div class="form-group">
                        <label>کلمات کلیدی (Meta Keywords)</label>
                        <input type="text" name="meta_keywords" id="seoKeywords" value="<?= sanitize($item['meta_keywords'] ?? '') ?>" placeholder="کلمات با کاما جدا شوند">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>تصویر Open Graph (OG Image)</label>
                            <input type="text" name="og_image" value="<?= sanitize($item['og_image'] ?? '') ?>" placeholder="خالی بگذارید تا از تصویر شاخص استفاده شود">
                        </div>
                        <div class="form-group">
                            <label>لینک کنونیکال</label>
                            <input type="text" name="canonical_url" value="<?= sanitize($item['canonical_url'] ?? '') ?>" placeholder="خالی بگذارید تا خودکار باشد">
                        </div>
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
    // Hide all tabs
    document.querySelectorAll('.lang-tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.lang-tab-btn').forEach(el => el.classList.remove('active'));

    // Show selected tab
    const tab = document.getElementById('langTab-' + code);
    const btn = document.getElementById('tabBtn-' + code);
    if (tab) tab.style.display = 'block';
    if (btn) btn.classList.add('active');
    currentLangTab = code;
}

// Initialize first tab
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

// Persian stopwords to exclude from keywords
const persianStopWords = ['و', 'در', 'به', 'از', 'که', 'این', 'را', 'با', 'است', 'برای', 'آن', 'یک', 'خود', 'تا', 'کند', 'بر', 'هم', 'نیز', 'گفت', 'شد', 'باید', 'شود', 'شده', 'بود', 'هست', 'می', 'یا', 'همه', 'پیش', 'دو', 'بی', 'هیچ', 'چه', 'اگر', 'بیشتر', 'شما', 'ما', 'من', 'او', 'آنها', 'ما', 'بین', 'حتی', 'کرد', 'دارد', 'داشت', 'مورد', 'شوند', 'بعد', 'اول', 'دوم', 'سوم', 'همین', 'همان', 'چنین', 'چون', 'زیرا', 'لیکن', 'بنابراین', 'پس', 'یعنی', 'مثلا', 'حدود', 'یکی', 'دیگر', 'همچنین', 'چند', 'چندین', 'بیش', 'کم', 'خیلی', 'کاملا', 'تقریبا', 'حدودا', 'فقط', 'باز', 'کنید', 'کنیم', 'کنند', 'بکنید', 'بکنیم', 'باشد', 'باشند', 'شده', 'شوند', 'می‌شود', 'می‌شوند', 'می‌توان', 'می‌تواند', 'می‌توانند', 'وجود', 'دارند', 'داشته', 'ندارد', 'نبود', 'نیست'];

function generateSEO() {
    const title = document.querySelector('input[name="title"]').value.trim();
    const content = document.querySelector('textarea[name="content"]').value;
    const excerpt = document.querySelector('textarea[name="excerpt"]').value.trim();

    // Strip HTML tags from content
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = content;
    const plainText = tempDiv.textContent || tempDiv.innerText || '';
    const cleanText = plainText.replace(/\s+/g, ' ').trim();

    // 1. Generate Meta Title (max 60 chars)
    let seoTitle = title;
    if (seoTitle.length > 60) {
        seoTitle = seoTitle.substring(0, 57) + '...';
    }
    document.getElementById('seoTitle').value = seoTitle;

    // 2. Generate Meta Description (max 160 chars)
    let seoDesc = excerpt || '';
    if (!seoDesc && cleanText.length > 0) {
        // Take first 2-3 sentences
        const sentences = cleanText.split(/[.؟!]+/).filter(s => s.trim().length > 10);
        seoDesc = sentences.slice(0, 3).join('. ');
    }
    if (seoDesc.length > 160) {
        seoDesc = seoDesc.substring(0, 157) + '...';
    }
    document.getElementById('seoDescription').value = seoDesc;

    // 3. Extract Keywords
    const allText = (title + ' ' + cleanText).toLowerCase();
    const words = allText.split(/[\s,.؟!،؛\(\)\[\]\{\}]+/).filter(w => w.length > 2);
    const wordFreq = {};
    words.forEach(word => {
        if (!persianStopWords.includes(word) && word.length > 2) {
            wordFreq[word] = (wordFreq[word] || 0) + 1;
        }
    });

    // Sort by frequency and take top keywords
    const sortedKeywords = Object.entries(wordFreq)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 8)
        .map(([word]) => word);

    // Remove duplicates and similar words
    const uniqueKeywords = [];
    sortedKeywords.forEach(kw => {
        const isDuplicate = uniqueKeywords.some(uk =>
            uk.includes(kw) || kw.includes(uk)
        );
        if (!isDuplicate && kw.length > 2) {
            uniqueKeywords.push(kw);
        }
    });

    document.getElementById('seoKeywords').value = uniqueKeywords.join(', ');

    // Show preview
    const preview = document.getElementById('seoPreview');
    const details = document.getElementById('seoPreviewDetails');
    preview.style.display = 'block';
    details.innerHTML = `
        <p><strong>عنوان:</strong> ${seoTitle} (${seoTitle.length} کاراکتر)</p>
        <p><strong>توضیحات:</strong> ${seoDesc} (${seoDesc.length} کاراکتر)</p>
        <p><strong>کلمات کلیدی:</strong> ${uniqueKeywords.join(', ')}</p>
    `;

    // Update counters
    updateCounters();
}

// Character counters
document.getElementById('seoTitle').addEventListener('input', updateCounters);
document.getElementById('seoDescription').addEventListener('input', updateCounters);

function updateCounters() {
    const titleLen = document.getElementById('seoTitle').value.length;
    const descLen = document.getElementById('seoDescription').value.length;

    document.getElementById('titleCounter').textContent = `(${titleLen}/60)`;
    document.getElementById('titleCounter').style.color = titleLen > 60 ? '#f44336' : titleLen > 50 ? '#ff9800' : '#4caf50';

    document.getElementById('descCounter').textContent = `(${descLen}/160)`;
    document.getElementById('descCounter').style.color = descLen > 160 ? '#f44336' : descLen > 140 ? '#ff9800' : '#4caf50';
}

// Initialize counters
updateCounters();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
