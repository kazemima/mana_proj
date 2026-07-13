<?php
define('DB_PATH', __DIR__ . '/../db/site.sqlite');
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$dir = dirname($_SERVER['SCRIPT_NAME']);
$baseUrl = $protocol . '://' . $host . '/mana_proj';
define('SITE_URL', $baseUrl);
define('SITE_NAME', 'شرکت مدرن اندیشان نوین ابتکار');
define('ADMIN_URL', SITE_URL . '/admin');
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
