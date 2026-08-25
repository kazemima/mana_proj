<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'تماس با ما';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($message)) {
        $error = 'لطفا نام و پیام را وارد کنید.';
    } else {
        insert('contact_messages', [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'subject' => $subject,
            'message' => $message,
        ]);
        $success = true;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1>تماس با ما</h1>
        <nav class="breadcrumb">
            <a href="<?= SITE_URL ?>/">صفحه اصلی</a>
            <span>/</span>
            <span>تماس با ما</span>
        </nav>
    </div>
</section>

<!-- Contact Section -->
<section class="section contact-section">
    <div class="container">
        <div class="contact-grid">
            <div class="contact-info">
                <h2>اطلاعات تماس</h2>
                <div class="contact-items">
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contact-text">
                            <h4>آدرس</h4>
                            <p><?= sanitize(getSetting('site_address')) ?></p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="contact-text">
                            <h4>تلفن</h4>
                            <p><a href="tel:<?= getSetting('site_phone') ?>"><?= getSetting('site_phone') ?></a></p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-text">
                            <h4>ایمیل</h4>
                            <p><a href="mailto:<?= getSetting('site_email') ?>"><?= getSetting('site_email') ?></a></p>
                        </div>
                    </div>
                </div>
                <div class="contact-social">
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

            <div class="contact-form-wrapper">
                <h2>فرم تماس</h2>
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    پیام شما با موفقیت ارسال شد. به زودی با شما تماس خواهیم گرفت.
                </div>
                <?php endif; ?>
                <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= sanitize($error) ?>
                </div>
                <?php endif; ?>
                <form method="POST" class="contact-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">نام و نام خانوادگی *</label>
                            <input type="text" id="name" name="name" required value="<?= sanitize($_POST['name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">ایمیل</label>
                            <input type="email" id="email" name="email" value="<?= sanitize($_POST['email'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">تلفن</label>
                            <input type="tel" id="phone" name="phone" value="<?= sanitize($_POST['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="subject">موضوع</label>
                            <input type="text" id="subject" name="subject" value="<?= sanitize($_POST['subject'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group full">
                        <label for="message">پیام *</label>
                        <textarea id="message" name="message" rows="6" required><?= sanitize($_POST['message'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">ارسال پیام</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
