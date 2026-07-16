<?php
$pageTitle = 'تنظیمات سایت';
require_once __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Password change
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $pdo = $db->getConnection();
        $user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $user->execute([$_SESSION['admin_id']]);
        $user = $user->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($currentPassword, $user['password'])) {
            redirect(ADMIN_URL . '/settings.php?msg=wrong_password');
        } elseif ($newPassword !== $confirmPassword) {
            redirect(ADMIN_URL . '/settings.php?msg=password_mismatch');
        } elseif (strlen($newPassword) < 6) {
            redirect(ADMIN_URL . '/settings.php?msg=password_short');
        } else {
            update('users', $_SESSION['admin_id'], [
                'password' => password_hash($newPassword, PASSWORD_DEFAULT)
            ]);
            redirect(ADMIN_URL . '/settings.php?msg=password_changed');
        }
    }

    // Site settings
    $fields = ['site_name', 'site_description', 'site_email', 'site_phone', 'site_address', 'facebook', 'twitter', 'linkedin', 'instagram', 'about_text', 'strategy_text', 'testimonial_text', 'testimonial_author', 'copyright'];
    foreach ($fields as $f) {
        if (isset($_POST[$f])) {
            setSetting($f, trim($_POST[$f]));
        }
    }

    foreach (['site_logo', 'site_favicon'] as $imgField) {
        if (isset($_FILES[$imgField]['tmp_name']) && is_uploaded_file($_FILES[$imgField]['tmp_name'])) {
            $uploaded = uploadImage($_FILES[$imgField], $imgField . '_');
            if ($uploaded) setSetting($imgField, $uploaded);
        }
    }

    redirect(ADMIN_URL . '/settings.php?msg=saved');
}

$msg = $_GET['msg'] ?? '';
?>

<div class="page-title-bar">
    <h1><i class="fas fa-cog"></i> تنظیمات سایت</h1>
</div>

<?php if ($msg == 'saved'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> تنظیمات ذخیره شد.</div>
<?php endif; ?>
<?php if ($msg == 'password_changed'): ?>
<div class="alert-admin alert-success"><i class="fas fa-check-circle"></i> رمز عبور با موفقیت تغییر کرد.</div>
<?php endif; ?>
<?php if ($msg == 'wrong_password'): ?>
<div class="alert-admin alert-error"><i class="fas fa-exclamation-circle"></i> رمز عبور فعلی اشتباه است.</div>
<?php endif; ?>
<?php if ($msg == 'password_mismatch'): ?>
<div class="alert-admin alert-error"><i class="fas fa-exclamation-circle"></i> رمز عبور جدید و تکرار آن مطابقت ندارند.</div>
<?php endif; ?>
<?php if ($msg == 'password_short'): ?>
<div class="alert-admin alert-error"><i class="fas fa-exclamation-circle"></i> رمز عبور باید حداقل ۶ کاراکتر باشد.</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <!-- General Settings -->
    <div class="card">
        <div class="card-header"><h2><i class="fas fa-globe"></i> تنظیمات عمومی</h2></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>نام سایت</label>
                    <input type="text" name="site_name" value="<?= sanitize(getSetting('site_name')) ?>">
                </div>
                <div class="form-group">
                    <label>توضیحات سایت</label>
                    <input type="text" name="site_description" value="<?= sanitize(getSetting('site_description')) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>لوگو</label>
                    <div class="upload-area-wrapper">
                        <div class="upload-area" onclick="document.getElementById('site_logo').click()">
                            <i class="fas fa-cloud-upload-alt" style="font-size:2rem;margin-bottom:5px;"></i><br>
                            <span>کلیک کنید یا فایل را بکشید</span>
                        </div>
                        <input type="file" name="site_logo" id="site_logo" accept="image/*" onchange="previewImage(this, 'logoPreview')" style="display:none;">
                        <div class="upload-preview" id="logoPreview">
                            <?php if (getSetting('site_logo')): ?>
                            <img src="<?= getImageUrl(getSetting('site_logo')) ?>" alt="">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>فاویکون</label>
                    <div class="upload-area-wrapper">
                        <div class="upload-area" onclick="document.getElementById('site_favicon').click()">
                            <i class="fas fa-cloud-upload-alt" style="font-size:2rem;margin-bottom:5px;"></i><br>
                            <span>کلیک کنید یا فایل را بکشید</span>
                        </div>
                        <input type="file" name="site_favicon" id="site_favicon" accept="image/*" onchange="previewImage(this, 'favPreview')" style="display:none;">
                        <div class="upload-preview" id="favPreview">
                            <?php if (getSetting('site_favicon')): ?>
                            <img src="<?= getImageUrl(getSetting('site_favicon')) ?>" alt="">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Settings -->
    <div class="card">
        <div class="card-header"><h2><i class="fas fa-phone"></i> اطلاعات تماس</h2></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>ایمیل</label>
                    <input type="email" name="site_email" value="<?= sanitize(getSetting('site_email')) ?>">
                </div>
                <div class="form-group">
                    <label>تلفن</label>
                    <input type="text" name="site_phone" value="<?= sanitize(getSetting('site_phone')) ?>">
                </div>
            </div>
            <div class="form-group">
                <label>آدرس</label>
                <textarea name="site_address" rows="3"><?= sanitize(getSetting('site_address')) ?></textarea>
            </div>
        </div>
    </div>

    <!-- Social Media -->
    <div class="card">
        <div class="card-header"><h2><i class="fas fa-share-alt"></i> شبکه‌های اجتماعی</h2></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>فیسبوک</label>
                    <input type="text" name="facebook" value="<?= sanitize(getSetting('facebook')) ?>" placeholder="https://facebook.com/...">
                </div>
                <div class="form-group">
                    <label>توییتر</label>
                    <input type="text" name="twitter" value="<?= sanitize(getSetting('twitter')) ?>" placeholder="https://twitter.com/...">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>لینکدین</label>
                    <input type="text" name="linkedin" value="<?= sanitize(getSetting('linkedin')) ?>" placeholder="https://linkedin.com/...">
                </div>
                <div class="form-group">
                    <label>اینستاگرام</label>
                    <input type="text" name="instagram" value="<?= sanitize(getSetting('instagram')) ?>" placeholder="https://instagram.com/...">
                </div>
            </div>
        </div>
    </div>

    <!-- Content Settings -->
    <div class="card">
        <div class="card-header"><h2><i class="fas fa-file-alt"></i> محتوا</h2></div>
        <div class="card-body">
            <div class="form-group">
                <label>متن درباره ما</label>
                <textarea name="about_text" rows="5"><?= sanitize(getSetting('about_text')) ?></textarea>
            </div>
            <div class="form-group">
                <label>متن مدیریت استراتژیک</label>
                <textarea name="strategy_text" rows="5"><?= sanitize(getSetting('strategy_text')) ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>متن نقل قول</label>
                    <textarea name="testimonial_text" rows="3"><?= sanitize(getSetting('testimonial_text')) ?></textarea>
                </div>
                <div class="form-group">
                    <label>نویسنده نقل قول</label>
                    <input type="text" name="testimonial_author" value="<?= sanitize(getSetting('testimonial_author')) ?>">
                </div>
            </div>
            <div class="form-group">
                <label>متن کپی‌رایت</label>
                <input type="text" name="copyright" value="<?= sanitize(getSetting('copyright')) ?>">
            </div>
        </div>
    </div>

    <button type="submit" class="btn-admin btn-green" style="margin-bottom:30px;"><i class="fas fa-save"></i> ذخیره تنظیمات</button>
</form>

<!-- Change Password -->
<div class="card">
    <div class="card-header"><h2><i class="fas fa-lock"></i> تغییر رمز عبور</h2></div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="change_password">
            <div class="form-row">
                <div class="form-group">
                    <label>رمز عبور فعلی *</label>
                    <input type="password" name="current_password" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>رمز عبور جدید *</label>
                    <input type="password" name="new_password" required minlength="6">
                </div>
                <div class="form-group">
                    <label>تکرار رمز عبور جدید *</label>
                    <input type="password" name="confirm_password" required minlength="6">
                </div>
            </div>
            <button type="submit" class="btn-admin btn-blue"><i class="fas fa-key"></i> تغییر رمز عبور</button>
        </form>
    </div>
</div>

<script>
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
