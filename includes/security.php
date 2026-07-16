<?php
// ========== CSRF Protection ==========
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

function verifyCSRFToken() {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('دسترسی غیرمجاز.');
    }
}

// ========== Security Headers ==========
function sendSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: https:; connect-src 'self'");
}

// ========== Table Whitelist (SQL Injection Prevention) ==========
$ALLOWED_TABLES = [
    'users', 'settings', 'sliders', 'services', 'categories', 'posts',
    'testimonials', 'pages', 'counter_items', 'contact_messages', 'faqs',
    'menu_items', 'menu_translations', 'languages', 'translations',
    'post_translations', 'service_translations', 'password_resets',
];

function sanitizeTableName($table) {
    global $ALLOWED_TABLES;
    if (!in_array($table, $ALLOWED_TABLES, true)) {
        http_response_code(400);
        exit('نام جدول نامعتبر.');
    }
    return $table;
}

// ========== Sanitize HTML Attributes ==========
function attr($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
