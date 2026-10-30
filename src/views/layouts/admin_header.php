<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?>Quản trị - <?= APP_NAME ?></title>
    <?php $cssDir = dirname(__DIR__, 3) . '/public/css'; // ?v= = thời điểm sửa file, tránh trình duyệt giữ CSS cũ ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css?v=<?= @filemtime($cssDir . '/style.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin.css?v=<?= @filemtime($cssDir . '/admin.css') ?>">
</head>
<body class="admin-body">
<div class="admin-layout">
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <aside class="admin-sidebar" id="admin-sidebar">
        <a class="brand" href="<?= BASE_URL ?>/admin">🎬 Cinema<span>Booking</span></a>
        <nav>
            <a href="<?= BASE_URL ?>/admin">Dashboard</a>
            <a href="<?= BASE_URL ?>/admin/movies">Phim</a>
            <a href="<?= BASE_URL ?>/admin/genres">Thể loại</a>
            <a href="<?= BASE_URL ?>/admin/cinemas">Rạp chiếu</a>
            <a href="<?= BASE_URL ?>/admin/rooms">Phòng chiếu</a>
            <a href="<?= BASE_URL ?>/admin/showtimes">Suất chiếu</a>
            <a href="<?= BASE_URL ?>/admin/bookings">Đơn đặt vé</a>
            <a href="<?= BASE_URL ?>/admin/customers">Tài khoản</a>
            <a href="<?= BASE_URL ?>/admin/logs">Nhật ký hệ thống</a>
            <hr>
            <a href="<?= BASE_URL ?>/">&larr; Về trang chủ</a>
            <form class="logout-form" method="post" action="<?= BASE_URL ?>/logout">
                <?= Csrf::field() ?>
                <button type="submit" class="link-button">Đăng xuất</button>
            </form>
        </nav>
    </aside>
    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar-left">
                <button type="button" class="sidebar-toggle" id="sidebar-toggle" aria-label="Mở menu quản trị">
                    <span></span><span></span><span></span>
                </button>
                <span><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard' ?></span>
            </div>
            <span class="admin-user">Xin chào, <?= htmlspecialchars(Auth::user()['full_name'] ?? '') ?></span>
        </header>
        <div class="admin-content">
            <?php if ($flashSuccess = $this->flash('success')): ?>
                <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
            <?php endif; ?>
            <?php if ($flashError = $this->flash('error')): ?>
                <div class="alert alert-error"><?= htmlspecialchars($flashError) ?></div>
            <?php endif; ?>
