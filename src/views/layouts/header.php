<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?><?= APP_NAME ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/asset/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IM+Fell+English+SC&family=Playfair+Display:wght@400;500&family=Inter:wght@400;500;600;700&subset=vietnamese&display=swap">
    <?php $cssDir = dirname(__DIR__, 3) . '/public/css'; // ?v= = thời điểm sửa file, tránh trình duyệt giữ CSS cũ ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css?v=<?= @filemtime($cssDir . '/style.css') ?>">
</head>
<body>
<?php
require_once dirname(__DIR__, 2) . '/models/Booking.php';

// Mục menu đang mở được tô đỏ (giống thiết kế CineQ)
$navPath = substr((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), strlen(BASE_URL)) ?: '/';
$navStatus = $_GET['status'] ?? null;
$navCurrent = static function (bool $active): string {
    return $active ? ' aria-current="page"' : '';
};
?>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= BASE_URL ?>/" aria-label="<?= APP_NAME ?> - Trang chủ"><?php include __DIR__ . '/../partials/logo.php'; ?></a>
        <button type="button" class="nav-toggle" id="nav-toggle" aria-label="Mở menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <div class="nav-panel" id="nav-panel">
            <nav class="main-nav" aria-label="Điều hướng chính">
                <a href="<?= BASE_URL ?>/"<?= $navCurrent($navPath === '/') ?>>Trang chủ</a>
                <a href="<?= BASE_URL ?>/movies?status=now_showing"<?= $navCurrent($navPath === '/movies' && $navStatus === 'now_showing') ?>>Đang chiếu</a>
                <a href="<?= BASE_URL ?>/movies?status=coming_soon"<?= $navCurrent($navPath === '/movies' && $navStatus === 'coming_soon') ?>>Sắp chiếu</a>
                <a href="<?= BASE_URL ?>/showtimes"<?= $navCurrent(str_starts_with($navPath, '/showtimes')) ?>>Lịch chiếu</a>
                <?php if (Auth::isAdmin()): ?>
                    <a href="<?= BASE_URL ?>/admin">Quản trị</a>
                <?php endif; ?>
            </nav>
            <div class="auth-nav">
                <?php if (Auth::check()): ?>
                    <?php
                    $cancelledBookingCount = !Auth::isAdmin()
                        ? Booking::countCancelledByUser(Auth::id())
                        : 0;
                    ?>
                    <a class="tickets-nav" href="<?= BASE_URL ?>/account/bookings"<?= $navCurrent(str_starts_with($navPath, '/account/bookings')) ?>>
                        <span>Vé của tôi</span>
                        <?php if ($cancelledBookingCount > 0): ?>
                            <span class="notification-badge" aria-label="<?= $cancelledBookingCount ?> vé đã bị hủy"><?= $cancelledBookingCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= BASE_URL ?>/account"<?= $navCurrent($navPath === '/account') ?>>Xin chào, <?= htmlspecialchars(Auth::user()['full_name']) ?></a>
                    <form class="logout-form" method="post" action="<?= BASE_URL ?>/logout">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn-light btn-light--outline">Đăng xuất</button>
                    </form>
                <?php else: ?>
                    <a class="btn-light btn-light--outline" href="<?= BASE_URL ?>/login">Đăng nhập</a>
                    <a class="btn-light" href="<?= BASE_URL ?>/register">Đăng ký</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
<main class="container page-content">
    <?php if ($flashSuccess = $this->flash('success')): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError = $this->flash('error')): ?>
        <div class="alert alert-error"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>
