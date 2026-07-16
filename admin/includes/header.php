<?php
if (session_status() === PHP_SESSION_NONE) session_start();
ob_start();
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';
sendSecurityHeaders();
requireLogin();

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$userRole = $_SESSION['admin_role'] ?? 'subscriber';
$roleNames = [
    'admin' => 'مدیر کل',
    'editor' => 'ویرایشگر',
    'author' => 'نویسنده',
    'contributor' => 'مشارکت‌کننده',
    'subscriber' => 'مشترک',
];
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'پنل مدیریت' ?> - پنل مدیریت</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
</head>
<body>
<div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="<?= ADMIN_URL ?>/index.php" class="sidebar-logo">
                <i class="fas fa-shield-alt"></i>
                <span>پنل مدیریت</span>
            </a>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <!-- Dashboard: All roles -->
                <li class="<?= $currentPage == 'index' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>/index.php"><i class="fas fa-tachometer-alt"></i> <span>داشبورد</span></a>
                </li>

                <?php if (hasPermission('editor')): ?>
                <!-- Slider: Editor+ -->
                <li class="<?= $currentPage == 'sliders' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>/sliders.php"><i class="fas fa-images"></i> <span>اسلایدر</span></a>
                </li>
                <!-- Services: Editor+ -->
                <li class="<?= $currentPage == 'services' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>/services.php"><i class="fas fa-cogs"></i> <span>خدمات</span></a>
                </li>
                <!-- Menus: Editor+ -->
                <li class="<?= $currentPage == 'menus' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>/menus.php"><i class="fas fa-bars"></i> <span>منو</span></a>
                </li>
                <?php endif; ?>

                <?php if (hasPermission('author')): ?>
                <!-- Posts: Author+ -->
                <li class="<?= $currentPage == 'posts' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>/posts.php"><i class="fas fa-newspaper"></i> <span>مقالات</span></a>
                </li>
                <!-- Categories: Author+ -->
                <li class="<?= $currentPage == 'categories' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>/categories.php"><i class="fas fa-folder"></i> <span>دسته بندی ها</span></a>
                </li>
                <?php endif; ?>

                <?php if (hasPermission('editor')): ?>
                <!-- Testimonials: Editor+ -->
                <li class="<?= $currentPage == 'testimonials' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>/testimonials.php"><i class="fas fa-comments"></i> <span>نظرات مشتریان</span></a>
                </li>
                <!-- Counters: Editor+ -->
                <li class="<?= $currentPage == 'counters' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>/counters.php"><i class="fas fa-sort-numeric-up"></i> <span>شمارنده‌ها</span></a>
                </li>
                <!-- Messages: Editor+ -->
                <li class="<?= $currentPage == 'messages' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>/messages.php"><i class="fas fa-envelope"></i> <span>پیام ها</span></a>
                </li>
                <?php endif; ?>

                <?php if (hasPermission('admin')): ?>
                <!-- Users: Admin only -->
                <li class="<?= $currentPage == 'users' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>/users.php"><i class="fas fa-users"></i> <span>کاربران</span></a>
                </li>
                <!-- Languages: Admin only -->
                <li class="<?= $currentPage == 'languages' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>/languages.php"><i class="fas fa-language"></i> <span>زبان‌ها</span></a>
                </li>
                <!-- SEO: Admin only -->
                <li class="<?= $currentPage == 'seo' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>/seo.php"><i class="fas fa-search"></i> <span>سئو (SEO)</span></a>
                </li>
                <!-- Settings: Admin only -->
                <li class="<?= $currentPage == 'settings' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>/settings.php"><i class="fas fa-cog"></i> <span>تنظیمات</span></a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="sidebar-footer">
            <a href="<?= SITE_URL ?>" target="_blank"><i class="fas fa-external-link-alt"></i> مشاهده سایت</a>
            <a href="<?= ADMIN_URL ?>/logout.php"><i class="fas fa-sign-out-alt"></i> خروج</a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="admin-main">
        <header class="admin-header">
            <button class="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('active')">
                <i class="fas fa-bars"></i>
            </button>
            <div class="admin-header-left">
                <span class="admin-user">
                    <i class="fas fa-user"></i>
                    <?= sanitize($_SESSION['admin_name'] ?? 'مدیر') ?>
                    <span class="badge badge-info" style="font-size:0.7rem; margin-right:5px;"><?= $roleNames[$userRole] ?? $userRole ?></span>
                </span>
            </div>
        </header>
        <div class="admin-content">
