<?php
/**
 * MySQL connection settings.
 *
 * Every value can be overridden with an environment variable of the same name
 * (DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS) so credentials can live in the
 * hosting panel instead of in this file.
 */
function dbEnv($key, $default) {
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

define('DB_HOST', dbEnv('DB_HOST', '127.0.0.1'));
define('DB_PORT', dbEnv('DB_PORT', '3306'));
define('DB_NAME', dbEnv('DB_NAME', 'mana_site'));
define('DB_USER', dbEnv('DB_USER', 'root'));
define('DB_PASS', dbEnv('DB_PASS', ''));
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATION', 'utf8mb4_unicode_ci');

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$dir = dirname($_SERVER['SCRIPT_NAME']);
$baseUrl = $protocol . '://' . $host . '';
define('SITE_URL', $baseUrl);
define('SITE_NAME', 'شرکت مدرن اندیشان نوین ابتکار');
define('ADMIN_URL', SITE_URL . '/admin');
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
