<?php
$pageTitle = 'مدیریت زبان‌ها';
require_once __DIR__ . '/includes/header.php';

if (!hasPermission('admin')) {
    redirect(ADMIN_URL . '/index.php');
}

if (isset($_GET['delete'])) {
    remove('languages', (int)$_GET['delete']);
    redirect(ADMIN_URL . '/languages.php?msg=deleted');
}

if (isset($_GET['default'])) {
    $pdo = $db->getConnection();
    $pdo->exec("UPDATE languages SET is_default = 0");
    update('languages', (int)$_GET['default'], ['is_default' => 1]);
    redirect(ADMIN_URL . '/languages.php?msg=default');
}

if (isset($_GET['toggle'])) {
    $item = getById('languages', (int)$_GET['toggle']);
    if ($item) {
        update('languages', $item['id'], ['status' => $item['status'] ? 0 : 1]);
    }
    redirect(ADMIN_URL . '/languages.php?msg=toggled');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken();
    $action = $_POST['action'] ?? '';

    if ($action == 'add' || $action == 'edit') {
        $data = [
            'code' => strtolower(trim($_POST['code'])),
            'name' => trim($_POST['name']),
            'native_name' => trim($_POST['native_name']),
            'direction' => trim($_POST['direction']),
            'flag' => trim($_POST['flag']),
            'sort_order' => (int)$_POST['sort_order'],
            'is_default' => isset($_POST['is_default']) ? 1 : 0,
            'status' => isset($_POST['status']) ? 1 : 0,
        ];

        if ($action == 'edit') {
            update('languages', (int)$_POST['id'], $data);
        } else {
            insert('languages', $data);
        }
        redirect(ADMIN_URL . '/languages.php?msg=saved');
    }

    if ($action == 'translate') {
        $langCode = trim($_POST['lang_code']);
        $keys = $_POST['trans_key'] ?? [];
        $values = $_POST['trans_value'] ?? [];
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare("INSERT INTO translations (lang_code, trans_key, trans_value) VALUES (?, ?, ?) ON CONFLICT(lang_code, trans_key) DO UPDATE SET trans_value = excluded.trans_value");
        foreach ($keys as $index => $key) {
            if (!empty($key)) {
                $stmt->execute([$langCode, $key, $values[$index] ?? '']);
            }
        }
        redirect(ADMIN_URL . '/languages.php?msg=translated&lang=' . $langCode);
    }
}

$msg = $_GET['msg'] ?? '';
$editItem = isset($_GET['edit']) ? getById('languages', (int)$_GET['edit']) : null;
$translateLang = $_GET['lang'] ?? '';

$pdo = $db->getConnection();
$languages = getAll('languages', '1=1', 'sort_order ASC');

// Get translations for a specific language
$translations = [];
if ($translateLang) {
    $stmt = $pdo->prepare("SELECT * FROM translations WHERE lang_code = ? ORDER BY trans_key ASC");
    $stmt->execute([$translateLang]);
    $translations = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="page-title-bar">
    <h1><i class="fas fa-language"></i> مدیریت زبان‌ها</h1>
    <button class="btn-admin btn-green" onclick="openLangModal()">
        <i class="fas fa-plus"></i> زبان جدید
    </button>
</div>

<?php if ($msg == 'saved'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> زبان ذخیره شد.</div>
<?php endif; ?>
<?php if ($msg == 'deleted'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> زبان حذف شد.</div>
<?php endif; ?>
<?php if ($msg == 'default'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> زبان پیش‌فرض تغییر کرد.</div>
<?php endif; ?>
<?php if ($msg == 'toggled'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> وضعیت زبان تغییر کرد.</div>
<?php endif; ?>
<?php if ($msg == 'translated'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> ترجمه‌ها ذخیره شد.</div>
<?php endif; ?>

<!-- Languages List -->
<div class="card">
    <div class="card-header"><h2><i class="fas fa-globe"></i> زبان‌های فعال</h2></div>
    <div class="card-body">
        <?php if (count($languages) > 0): ?>
        <div class="table-responsive">
            <table>
                <tr>
                    <th>پرچم</th>
                    <th>کد</th>
                    <th>نام</th>
                    <th>نام بومی</th>
                    <th>جهت</th>
                    <th>ترتیب</th>
                    <th>پیش‌فرض</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
                <?php foreach ($languages as $lang): ?>
                <tr>
                    <td style="font-size:1.5rem;"><?= $lang['flag'] ?></td>
                    <td><strong><?= strtoupper($lang['code']) ?></strong></td>
                    <td><?= sanitize($lang['name']) ?></td>
                    <td><?= sanitize($lang['native_name']) ?></td>
                    <td><span class="badge badge-info"><?= strtoupper($lang['direction']) ?></span></td>
                    <td><?= $lang['sort_order'] ?></td>
                    <td>
                        <?php if ($lang['is_default']): ?>
                        <span class="badge badge-success">پیش‌فرض</span>
                        <?php else: ?>
                        <a href="?default=<?= $lang['id'] ?>" class="btn-admin btn-outline" style="font-size:0.75rem;">پیش‌فرض کردن</a>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= $lang['status'] ? 'badge-success' : 'badge-danger' ?>"><?= $lang['status'] ? 'فعال' : 'غیرفعال' ?></span></td>
                    <td>
                        <div class="btn-group">
                            <button class="btn-admin btn-blue" onclick='editLang(<?= json_encode($lang) ?>)'><i class="fas fa-edit"></i></button>
                            <a href="?toggle=<?= $lang['id'] ?>" class="btn-admin btn-gray" title="تغییر وضعیت"><i class="fas fa-toggle-on"></i></a>
                            <a href="?lang=<?= $lang['code'] ?>" class="btn-admin btn-green" title="ترجمه‌ها"><i class="fas fa-language"></i></a>
                            <?php if (!$lang['is_default']): ?>
                            <a href="?delete=<?= $lang['id'] ?>" class="btn-admin btn-red" onclick="return confirm('حذف شود؟')"><i class="fas fa-trash"></i></a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><i class="fas fa-language"></i><p>زبانی وجود ندارد.</p></div>
        <?php endif; ?>
    </div>
</div>

<!-- Translation Section -->
<?php if ($translateLang): ?>
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-language"></i> ترجمه‌های زبان <?= attr(strtoupper($translateLang)) ?></h2>
        <a href="<?= ADMIN_URL ?>/languages.php" class="btn-admin btn-gray"><i class="fas fa-arrow-right"></i> بازگشت</a>
    </div>
    <div class="card-body">
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="translate">
            <input type="hidden" name="lang_code" value="<?= attr($translateLang) ?>">
            <div class="translation-list" id="translationList">
                <?php
                $defaultLang = getDefaultLang();
                $stmt = $pdo->prepare("SELECT trans_key, trans_value FROM translations WHERE lang_code = ?");
                $stmt->execute([$defaultLang]);
                $defaultTranslations = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

                $transMap = [];
                foreach ($translations as $tr) {
                    $transMap[$tr['trans_key']] = $tr['trans_value'];
                }

                $allKeys = array_unique(array_merge(array_keys($defaultTranslations), array_keys($transMap)));
                sort($allKeys);
                ?>
                <?php foreach ($allKeys as $key): ?>
                <div class="translation-row">
                    <div class="trans-key">
                        <span class="trans-key-label"><?= $key ?></span>
                        <input type="hidden" name="trans_key[]" value="<?= $key ?>">
                        <div class="trans-default"><?= $defaultTranslations[$key] ?? $key ?></div>
                    </div>
                    <div class="trans-value">
                        <input type="text" name="trans_value[]" value="<?= sanitize($transMap[$key] ?? '') ?>" placeholder="ترجمه...">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:15px;display:flex;gap:10px;">
                <button type="submit" class="btn-admin btn-green"><i class="fas fa-save"></i> ذخیره ترجمه‌ها</button>
                <button type="button" class="btn-admin btn-blue" onclick="addTranslationRow()"><i class="fas fa-plus"></i> افزودن ردیف</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Language Modal -->
<div class="menu-modal-overlay" id="langModal">
    <div class="menu-modal">
        <div class="menu-modal-header">
            <h3 id="langModalTitle">زبان جدید</h3>
            <button class="menu-modal-close" onclick="closeLangModal()">&times;</button>
        </div>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add" id="langFormAction">
            <input type="hidden" name="id" value="" id="langFormId">
            <div class="menu-modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>کد زبان (ISO 639-1) *</label>
                        <input type="text" name="code" id="langFormCode" required placeholder="مثلا: fa, en, ar" maxlength="2">
                    </div>
                    <div class="form-group">
                        <label>نام زبان (انگلیسی) *</label>
                        <input type="text" name="name" id="langFormName" required placeholder="مثلا: Persian">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>نام بومی *</label>
                        <input type="text" name="native_name" id="langFormNative" required placeholder="مثلا: فارسی">
                    </div>
                    <div class="form-group">
                        <label>پرچم (ایموجی)</label>
                        <input type="text" name="flag" id="langFormFlag" placeholder="مثلا: 🇮🇷">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>جهت متن</label>
                        <select name="direction" id="langFormDir">
                            <option value="rtl">راست به چپ (RTL)</option>
                            <option value="ltr">چپ به راست (LTR)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>ترتیب</label>
                        <input type="number" name="sort_order" id="langFormSort" value="0">
                        <div style="margin-top:10px;">
                            <label style="display:flex;align-items:center;gap:8px;"><input type="checkbox" name="is_default" id="langFormDefault"> زبان پیش‌فرض</label>
                            <label style="display:flex;align-items:center;gap:8px;margin-top:5px;"><input type="checkbox" name="status" id="langFormStatus" checked> فعال</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="menu-modal-footer">
                <button type="submit" class="btn-admin btn-green"><i class="fas fa-save"></i> ذخیره</button>
                <button type="button" class="btn-admin btn-gray" onclick="closeLangModal()">انصراف</button>
            </div>
        </form>
    </div>
</div>

<style>
.translation-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
    align-items: start;
}
.trans-key-label {
    font-weight: 600; color: var(--primary); font-size: 0.85rem;
    display: block; margin-bottom: 4px;
}
.trans-default {
    font-size: 0.8rem; color: #888; line-height: 1.5;
}
.trans-value input {
    width: 100%; padding: 8px 12px;
    border: 1px solid var(--border); border-radius: 6px;
    font-family: inherit; font-size: 0.9rem;
}
.trans-value input:focus { outline: none; border-color: var(--primary); }
</style>

<script>
function openLangModal() {
    document.getElementById('langFormAction').value = 'add';
    document.getElementById('langFormId').value = '';
    document.getElementById('langFormCode').value = '';
    document.getElementById('langFormName').value = '';
    document.getElementById('langFormNative').value = '';
    document.getElementById('langFormFlag').value = '';
    document.getElementById('langFormDir').value = 'rtl';
    document.getElementById('langFormSort').value = '0';
    document.getElementById('langFormDefault').checked = false;
    document.getElementById('langFormStatus').checked = true;
    document.getElementById('langModalTitle').textContent = 'افزودن زبان جدید';
    document.getElementById('langModal').classList.add('active');
}

function editLang(lang) {
    document.getElementById('langFormAction').value = 'edit';
    document.getElementById('langFormId').value = lang.id;
    document.getElementById('langFormCode').value = lang.code;
    document.getElementById('langFormName').value = lang.name;
    document.getElementById('langFormNative').value = lang.native_name;
    document.getElementById('langFormFlag').value = lang.flag;
    document.getElementById('langFormDir').value = lang.direction;
    document.getElementById('langFormSort').value = lang.sort_order;
    document.getElementById('langFormDefault').checked = lang.is_default == 1;
    document.getElementById('langFormStatus').checked = lang.status == 1;
    document.getElementById('langModalTitle').textContent = 'ویرایش زبان';
    document.getElementById('langModal').classList.add('active');
}

function closeLangModal() {
    document.getElementById('langModal').classList.remove('active');
}

document.getElementById('langModal').addEventListener('click', function(e) {
    if (e.target === this) closeLangModal();
});

function addTranslationRow() {
    const list = document.getElementById('translationList');
    const row = document.createElement('div');
    row.className = 'translation-row';
    row.innerHTML = `
        <div class="trans-key">
            <input type="text" name="trans_key[]" placeholder="کلید ترجمه" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:0.85rem;">
        </div>
        <div class="trans-value">
            <input type="text" name="trans_value[]" placeholder="ترجمه..." style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:6px;">
        </div>
    `;
    list.appendChild(row);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
