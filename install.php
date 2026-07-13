<?php
require_once __DIR__ . '/includes/config.php';

if (file_exists(DB_PATH)) {
    echo "دیتابیس قبلاً ساخته شده است. <a href='index.php'>بازگشت به صفحه اصلی</a>";
    exit;
}

try {
    require_once __DIR__ . '/includes/database.php';
    $db->initDatabase();
    echo "<!DOCTYPE html><html dir='rtl' lang='fa'><head><meta charset='UTF-8'><title>نصب سایت</title><style>body{font-family:Tahoma;direction:rtl;text-align:center;padding:50px;background:#f5f5f5;}.success{background:#d4edda;color:#155724;padding:20px;border-radius:8px;border:1px solid #c3e6cb;margin:20px auto;max-width:500px;}.info{color:#666;margin-top:10px;}</style></head><body><h1>نصب سایت مانا</h1><div class='success'><h2>✅ نصب با موفقیت انجام شد!</h2><p>دیتابیس SQLite ساخته شد و داده‌های اولیه درج شدند.</p></div><div class='info'><p><strong>اطلاعات ورود به پنل ادمین:</strong></p><p>نام کاربری: <code>admin</code></p><p>رمز عبور: <code>admin123</code></p><p><a href='index.php'>صفحه اصلی</a> | <a href='admin/login.php'>پنل مدیریت</a></p></div></body></html>";
} catch (Exception $e) {
    echo "خطا: " . $e->getMessage();
}
