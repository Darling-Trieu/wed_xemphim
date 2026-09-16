<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?>Quản trị - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin.css">
</head>
<body class="admin-body">
<div class="admin-layout">
    <aside class="admin-sidebar">
        <a class="brand" href="<?= BASE_URL ?>/admin">🎬 <?= APP_NAME ?></a>
        <nav>
            <a href="<?= BASE_URL ?>/admin">Dashboard</a>
            <a href="<?= BASE_URL ?>/admin/movies">Phim</a>
            <a href="<?= BASE_URL ?>/admin/genres">Thể loại</a>
            <a href="<?= BASE_URL ?>/admin/cinemas">Rạp chiếu</a>
            <a href="<?= BASE_URL ?>/admin/rooms">Phòng chiếu</a>
            <a href="<?= BASE_URL ?>/admin/showtimes">Suất chiếu</a>
            <a href="<?= BASE_URL ?>/admin/bookings">Đơn đặt vé</a>
            <a href="<?= BASE_URL ?>/admin/customers">Khách hàng</a>
            <hr>
            <a href="<?= BASE_URL ?>/">&larr; Về trang chủ</a>
            <a href="<?= BASE_URL ?>/logout">Đăng xuất</a>
        </nav>
    </aside>
    <div class="admin-main">
        <header class="admin-topbar">
            <span><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard' ?></span>
            <span class="admin-user">Xin chào, <?= htmlspecialchars(Auth::user()['full_name'] ?? '') ?></span>
        </header>
        <div class="admin-content">
            <?php if ($flashSuccess = $this->flash('success')): ?>
                <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
            <?php endif; ?>
            <?php if ($flashError = $this->flash('error')): ?>
                <div class="alert alert-error"><?= htmlspecialchars($flashError) ?></div>
            <?php endif; ?>
