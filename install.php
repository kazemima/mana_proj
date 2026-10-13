<?php
require_once __DIR__ . '/includes/config.php';

$lockFile = __DIR__ . '/.installed';

if (file_exists($lockFile)) {
    echo "سایت قبلاً نصب شده است. <a href='index.php'>بازگشت به صفحه اصلی</a>";
    exit;
}

try {
    require_once __DIR__ . '/includes/database.php';

    // Tables already present means a previous install or a completed migration.
    if ($db->tableExists('settings')) {
        echo "دیتابیس MySQL قبلاً راه‌اندازی شده است. <a href='index.php'>بازگشت به صفحه اصلی</a>";
        exit;
    }

    $db->initDatabase();
    file_put_contents($lockFile, date('Y-m-d H:i:s'));
    echo "<!DOCTYPE html><html dir='rtl' lang='fa'><head><meta charset='UTF-8'><title>نصب سایت</title><style>body{font-family:Tahoma;direction:rtl;text-align:center;padding:50px;background:#f5f5f5;}.success{background:#d4edda;color:#155724;padding:20px;border-radius:8px;border:1px solid #c3e6cb;margin:20px auto;max-width:600px;}.info{color:#666;margin-top:10px;}</style></head><body><h1>نصب سایت مانا</h1><div class='success'><h2>✅ نصب با موفقیت انجام شد!</h2><p>دیتابیس MySQL ("
    . htmlspecialchars(DB_NAME, ENT_QUOTES, 'UTF-8')
    . ") ساخته شد، جداول ایجاد و داده‌های اولیه درج شدند.</p></div><div class='info'><p><strong>اطلاعات ورود به پنل ادمین:</strong></p><p>نام کاربری: <code>admin</code></p><p>رمز عبور: <code>admin123</code></p><p>برای انتقال داده‌های قبلی سایت از SQLite فایل <code>migrate-sqlite-to-mysql.php</code> را از خط فرمان اجرا کنید.</p><p><a href='index.php'>صفحه اصلی</a> | <a href='admin/login.php'>پنل مدیریت</a></p></div></body></html>";
} catch (Throwable $e) {
    echo "خطا: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
