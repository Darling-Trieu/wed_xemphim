<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?>Quản trị - <?= APP_NAME ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/asset/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&subset=vietnamese&display=swap">
    <?php $cssDir = dirname(__DIR__, 3) . '/public/css'; // ?v= = thời điểm sửa file, tránh trình duyệt giữ CSS cũ ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css?v=<?= @filemtime($cssDir . '/style.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin.css?v=<?= @filemtime($cssDir . '/admin.css') ?>">
</head>
<body class="admin-body">
<div class="admin-layout">
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <aside class="admin-sidebar" id="admin-sidebar">
        <a class="brand" href="<?= BASE_URL ?>/admin" aria-label="<?= APP_NAME ?> - Quản trị"><?php include __DIR__ . '/../partials/logo.php'; ?></a>
        <?php
        // Mục đang mở được tô nền (giống thiết kế Figma Admin_page)
        $adminPath = substr((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), strlen(BASE_URL));
        $adminNav = [
            '/admin'           => 'Dashboard',
            '/admin/movies'    => 'Phim',
            '/admin/genres'    => 'Thể loại',
            '/admin/cinemas'   => 'Rạp chiếu',
            '/admin/rooms'     => 'Phòng chiếu',
            '/admin/showtimes' => 'Suất chiếu',
            '/admin/bookings'  => 'Đơn đặt vé',
            '/admin/customers' => 'Khách hàng',
            '/admin/logs'      => 'Nhật ký hệ thống',
        ];
        ?>
        <nav>
            <?php foreach ($adminNav as $href => $label): ?>
                <?php $isActive = $href === '/admin' ? rtrim($adminPath, '/') === '/admin' : str_starts_with($adminPath, $href); ?>
                <a href="<?= BASE_URL . $href ?>"<?= $isActive ? ' class="active" aria-current="page"' : '' ?>><?= $label ?></a>
            <?php endforeach; ?>
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
