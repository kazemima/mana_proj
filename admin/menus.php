<?php
$pageTitle = 'مدیریت منو';
require_once __DIR__ . '/includes/header.php';

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo = $db->getConnection();
    $stmt = $pdo->prepare("SELECT id FROM menu_items WHERE parent_id = ?");
    $stmt->execute([$id]);
    $children = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($children as $childId) {
        remove('menu_items', $childId);
    }
    remove('menu_items', $id);
    redirect(ADMIN_URL . '/menus.php?msg=deleted');
}

if (isset($_GET['toggle'])) {
    $item = getById('menu_items', (int)$_GET['toggle']);
    if ($item) {
        update('menu_items', $item['id'], ['status' => $item['status'] ? 0 : 1]);
    }
    redirect(ADMIN_URL . '/menus.php?msg=toggled');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'add' || $action == 'edit') {
        $data = [
            'title' => trim($_POST['title']),
            'url' => trim($_POST['url']),
            'target' => trim($_POST['target']),
            'icon' => trim($_POST['icon']),
            'parent_id' => (int)$_POST['parent_id'],
            'sort_order' => (int)$_POST['sort_order'],
            'status' => isset($_POST['status']) ? 1 : 0,
        ];

        if ($action == 'edit') {
            update('menu_items', (int)$_POST['id'], $data);
            $menuId = (int)$_POST['id'];
        } else {
            $menuId = insert('menu_items', $data);
        }

        $transLangCodes = [];
        $activeLangsArr = getActiveLanguages();
        $defaultLangCodeArr = getDefaultLang();
        foreach ($activeLangsArr as $al) {
            if ($al['code'] !== $defaultLangCodeArr) $transLangCodes[] = $al['code'];
        }
        $transData = $_POST['trans'] ?? [];
        if (!empty($transLangCodes) && !empty($transData)) {
            saveMenuTranslations($menuId, $transLangCodes, $transData);
        }

        redirect(ADMIN_URL . '/menus.php?msg=saved');
    }
}

$msg = $_GET['msg'] ?? '';

$pdo = $db->getConnection();
$allItems = $pdo->query("SELECT * FROM menu_items ORDER BY parent_id ASC, sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

function buildMenuTree($items, $parentId = 0) {
    $tree = [];
    foreach ($items as $item) {
        if ($item['parent_id'] == $parentId) {
            $item['children'] = buildMenuTree($items, $item['id']);
            $tree[] = $item;
        }
    }
    return $tree;
}
$menuTree = buildMenuTree($allItems);

// Get parent-only items for dropdown
$parentItems = array_filter($allItems, fn($i) => $i['parent_id'] == 0);

// Language support
$activeLangs = getActiveLanguages();
$defaultLangCode = getDefaultLang();
$nonDefaultLangs = array_filter($activeLangs, fn($l) => $l['code'] !== $defaultLangCode);
?>

<div class="page-title-bar">
    <h1><i class="fas fa-bars"></i> مدیریت منو</h1>
    <button class="btn-admin btn-green" onclick="openModal()">
        <i class="fas fa-plus"></i> افزودن منو
    </button>
</div>

<?php if ($msg == 'deleted'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> آیتم منو حذف شد.</div>
<?php endif; ?>
<?php if ($msg == 'saved'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> ذخیره شد.</div>
<?php endif; ?>
<?php if ($msg == 'reordered'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> ترتیب منو بروزرسانی شد.</div>
<?php endif; ?>
<?php if ($msg == 'toggled'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> وضعیت نمایش تغییر کرد.</div>
<?php endif; ?>

<!-- Menu Tree -->
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-sitemap"></i> ساختار فعلی منو</h2>
        <span style="font-size:0.8rem;color:#888;"><i class="fas fa-arrows-alt"></i> آیتم‌ها را بکشید و رها کنید</span>
    </div>
    <div class="card-body">
        <?php if (count($menuTree) > 0): ?>
        <form method="POST" id="reorderForm">
            <input type="hidden" name="action" value="reorder">
            <input type="hidden" name="order_data" id="orderData" value="">
            <div class="menu-tree" id="menuTree">
                <?php foreach ($menuTree as $mainItem): ?>
                <div class="menu-tree-item <?= !$mainItem['status'] ? 'inactive' : '' ?>" draggable="true" data-id="<?= $mainItem['id'] ?>" data-parent="0">
                    <div class="menu-tree-row">
                        <span class="menu-drag" title="کشیدن برای تغییر ترتیب"><i class="fas fa-grip-vertical"></i></span>
                        <?php if ($mainItem['icon']): ?>
                        <i class="<?= $mainItem['icon'] ?>" style="margin-left:5px;color:var(--primary);"></i>
                        <?php endif; ?>
                        <strong><?= sanitize($mainItem['title']) ?></strong>
                        <span class="badge badge-info" style="margin:0 5px;"><?= $mainItem['url'] ?></span>
                        <?php if ($mainItem['target'] == '_blank'): ?>
                        <span class="badge badge-warning" style="font-size:0.7rem;">تب جدید</span>
                        <?php endif; ?>
                        <?php if (!$mainItem['status']): ?>
                        <span class="badge badge-danger">غیرفعال</span>
                        <?php endif; ?>
                        <div class="btn-group" style="margin-right:auto;">
                            <?php
                            $mainTrans = getMenuTranslationsAll($mainItem['id']);
                            $mainItemWithTrans = $mainItem;
                            $mainItemWithTrans['translations'] = $mainTrans;
                            ?>
                            <button type="button" class="btn-admin btn-blue" title="ویرایش" onclick='editMenu(<?= json_encode($mainItemWithTrans, JSON_UNESCAPED_UNICODE) ?>)'><i class="fas fa-edit"></i></button>
                            <a href="?toggle=<?= $mainItem['id'] ?>" class="btn-admin btn-gray" title="تغییر وضعیت"><i class="fas fa-toggle-on"></i></a>
                            <a href="?delete=<?= $mainItem['id'] ?>" class="btn-admin btn-red" title="حذف" onclick="return confirm('آیا مطمئن هستید؟ این آیتم و تمام زیرمجموعه‌هایش حذف می‌شوند.')"><i class="fas fa-trash"></i></a>
                        </div>
                    </div>
                    <?php if (!empty($mainItem['children'])): ?>
                    <div class="menu-tree-children" data-parent-id="<?= $mainItem['id'] ?>">
                        <?php foreach ($mainItem['children'] as $child): ?>
                        <div class="menu-tree-item child <?= !$child['status'] ? 'inactive' : '' ?>" draggable="true" data-id="<?= $child['id'] ?>" data-parent="<?= $mainItem['id'] ?>">
                            <div class="menu-tree-row">
                                <span class="menu-drag"><i class="fas fa-grip-vertical"></i></span>
                                <span style="margin-left:5px;color:#aaa;">└</span>
                                <span><?= sanitize($child['title']) ?></span>
                                <span class="badge badge-info" style="margin:0 5px;"><?= $child['url'] ?></span>
                                <?php if ($child['target'] == '_blank'): ?>
                                <span class="badge badge-warning" style="font-size:0.7rem;">تب جدید</span>
                                <?php endif; ?>
                                <?php if (!$child['status']): ?>
                                <span class="badge badge-danger">غیرفعال</span>
                                <?php endif; ?>
                                <div class="btn-group" style="margin-right:auto;">
                                    <?php
                                    $childTrans = getMenuTranslationsAll($child['id']);
                                    $childWithTrans = $child;
                                    $childWithTrans['translations'] = $childTrans;
                                    ?>
                                    <button type="button" class="btn-admin btn-blue" title="ویرایش" onclick='editMenu(<?= json_encode($childWithTrans, JSON_UNESCAPED_UNICODE) ?>)'><i class="fas fa-edit"></i></button>
                                    <a href="?toggle=<?= $child['id'] ?>" class="btn-admin btn-gray" title="تغییر وضعیت"><i class="fas fa-toggle-on"></i></a>
                                    <a href="?delete=<?= $child['id'] ?>" class="btn-admin btn-red" title="حذف" onclick="return confirm('آیا مطمئن هستید؟')"><i class="fas fa-trash"></i></a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:20px;">
                <button type="button" class="btn-admin btn-blue" onclick="saveOrder()"><i class="fas fa-sort"></i> ذخیره ترتیب</button>
            </div>
        </form>
        <?php else: ?>
        <div class="empty-state"><i class="fas fa-bars"></i><p>آیتم منویی وجود ندارد.</p><button class="btn-admin btn-green" onclick="openModal()"><i class="fas fa-plus"></i> افزودن اولین آیتم</button></div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal -->
<div class="menu-modal-overlay" id="menuModal">
    <div class="menu-modal">
        <div class="menu-modal-header">
            <h3 id="modalTitle">افزودن آیتم منو</h3>
            <button class="menu-modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" id="menuForm">
            <input type="hidden" name="action" value="add" id="formAction">
            <input type="hidden" name="id" value="" id="formId">
            <div class="menu-modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>عنوان منو *</label>
                        <input type="text" name="title" id="formTitle" required placeholder="مثلا: صفحه اصلی">
                    </div>
                    <div class="form-group">
                        <label>لینک</label>
                        <input type="text" name="url" id="formUrl" placeholder="مثلا: /about.php یا https://example.com">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>منوی والد</label>
                        <select name="parent_id" id="formParent">
                            <option value="0">— منوی اصلی —</option>
                            <?php foreach ($parentItems as $pItem): ?>
                            <option value="<?= $pItem['id'] ?>"><?= sanitize($pItem['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>آیکون (Font Awesome)</label>
                        <input type="text" name="icon" id="formIcon" placeholder="مثلا: fas fa-home">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>باز شدن لینک</label>
                        <select name="target" id="formTarget">
                            <option value="_self">همین صفحه</option>
                            <option value="_blank">تب جدید</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>ترتیب</label>
                        <input type="number" name="sort_order" id="formSort" value="0">
                        <label style="margin-top:15px;display:flex;align-items:center;gap:8px;">
                            <input type="checkbox" name="status" id="formStatus" checked> فعال
                        </label>
                    </div>
                </div>
                <!-- Translation Fields -->
                <?php if (count($nonDefaultLangs) > 0): ?>
                <div style="border-top:1px solid #eee;margin-top:15px;padding-top:15px;">
                    <strong style="color:var(--primary);font-size:0.9rem;"><i class="fas fa-language"></i> ترجمه عنوان</strong>
                    <?php foreach ($nonDefaultLangs as $lang): ?>
                    <div class="form-group" style="margin-top:10px;margin-bottom:5px;">
                        <label style="font-size:0.8rem;"><?= $lang['flag'] ?> <?= $lang['native_name'] ?></label>
                        <input type="text" name="trans[<?= $lang['code'] ?>][title]" id="formTrans-<?= $lang['code'] ?>" placeholder="ترجمه عنوان">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="menu-modal-footer">
                <button type="submit" class="btn-admin btn-green"><i class="fas fa-save"></i> ذخیره</button>
                <button type="button" class="btn-admin btn-gray" onclick="closeModal()">انصراف</button>
            </div>
        </form>
    </div>
</div>

<script>
// ========== Drag and Drop ==========
const menuTree = document.getElementById('menuTree');
let draggedItem = null;
let draggedPlaceholder = null;

menuTree.addEventListener('dragstart', function(e) {
    draggedItem = e.target.closest('.menu-tree-item');
    if (!draggedItem) return;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', draggedItem.dataset.id);
    setTimeout(() => draggedItem.classList.add('dragging'), 0);
});

menuTree.addEventListener('dragend', function(e) {
    if (draggedItem) draggedItem.classList.remove('dragging');
    document.querySelectorAll('.drag-over, .drag-over-child').forEach(el => {
        el.classList.remove('drag-over', 'drag-over-child');
    });
    draggedItem = null;
    saveOrder();
});

menuTree.addEventListener('dragover', function(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';

    const target = e.target.closest('.menu-tree-item');
    if (!target || target === draggedItem) return;

    const draggedIsChild = draggedItem.classList.contains('child');
    const targetIsChild = target.classList.contains('child');
    const targetParent = target.closest('.menu-tree-children');
    const draggedParent = draggedItem.closest('.menu-tree-children');

    // Remove previous highlights
    document.querySelectorAll('.drag-over, .drag-over-child').forEach(el => {
        el.classList.remove('drag-over', 'drag-over-child');
    });

    // Get mouse position relative to target
    const rect = target.getBoundingClientRect();
    const midY = rect.top + rect.height / 2;
    const isAbove = e.clientY < midY;

    if (draggedIsChild && targetIsChild && draggedParent === targetParent) {
        // Same parent - reorder within
        if (isAbove) {
            targetParent.insertBefore(draggedItem, target);
        } else {
            targetParent.insertBefore(draggedItem, target.nextSibling);
        }
    } else if (!draggedIsChild && !targetIsChild) {
        // Both main items - reorder
        if (isAbove) {
            menuTree.insertBefore(draggedItem, target);
        } else {
            menuTree.insertBefore(draggedItem, target.nextSibling);
        }
    } else if (!draggedIsChild && targetIsChild) {
        // Main item -> child container
        target.classList.add('drag-over-child');
    }
});

menuTree.addEventListener('drop', function(e) {
    e.preventDefault();
    document.querySelectorAll('.drag-over, .drag-over-child').forEach(el => {
        el.classList.remove('drag-over', 'drag-over-child');
    });
});

function saveOrder() {
    const items = menuTree.querySelectorAll('.menu-tree-item');
    const orderData = [];
    items.forEach(item => {
        const parentEl = item.closest('.menu-tree-children');
        const parentId = parentEl ? parentEl.dataset.parentId : 0;
        orderData.push({
            id: parseInt(item.dataset.id),
            parent_id: parseInt(parentId || item.dataset.parent || 0)
        });
    });
    document.getElementById('orderData').value = JSON.stringify(orderData);

    // Auto-save via AJAX
    fetch('menu-reorder.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=reorder_json&order_data=' + encodeURIComponent(JSON.stringify(orderData))
    }).then(r => r.json()).then(data => {
        if (data.success) {
            showToast('ترتیب ذخیره شد');
        }
    }).catch(() => {
        showToast('خطا در ذخیره ترتیب');
    });
}

function showToast(msg) {
    const toast = document.createElement('div');
    toast.className = 'drag-toast';
    toast.innerHTML = '<i class="fas fa-check-circle"></i> ' + msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 2000);
}

// ========== Modal ==========
function openModal() {
    document.getElementById('formAction').value = 'add';
    document.getElementById('formId').value = '';
    document.getElementById('formTitle').value = '';
    document.getElementById('formUrl').value = '';
    document.getElementById('formParent').value = '0';
    document.getElementById('formIcon').value = '';
    document.getElementById('formTarget').value = '_self';
    document.getElementById('formSort').value = '0';
    document.getElementById('formStatus').checked = true;
    document.getElementById('modalTitle').textContent = 'افزودن آیتم منو';
    document.getElementById('menuModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function editMenu(item) {
    document.getElementById('formAction').value = 'edit';
    document.getElementById('formId').value = item.id;
    document.getElementById('formTitle').value = item.title;
    document.getElementById('formUrl').value = item.url;
    document.getElementById('formParent').value = item.parent_id;
    document.getElementById('formIcon').value = item.icon;
    document.getElementById('formTarget').value = item.target;
    document.getElementById('formSort').value = item.sort_order;
    document.getElementById('formStatus').checked = item.status == 1;

    // Load translations
    const trans = item.translations || {};
    document.querySelectorAll('[id^="formTrans-"]').forEach(input => {
        const langCode = input.id.replace('formTrans-', '');
        input.value = (trans[langCode] && trans[langCode].title) ? trans[langCode].title : '';
    });

    document.getElementById('modalTitle').textContent = 'ویرایش آیتم منو';
    document.getElementById('menuModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('menuModal').classList.remove('active');
    document.body.style.overflow = '';
}

document.getElementById('menuModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
