<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?><?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= BASE_URL ?>/">🎬 <?= APP_NAME ?></a>
        <nav class="main-nav">
            <a href="<?= BASE_URL ?>/movies?status=now_showing">Đang chiếu</a>
            <a href="<?= BASE_URL ?>/movies?status=coming_soon">Sắp chiếu</a>
            <a href="<?= BASE_URL ?>/showtimes">Lịch chiếu</a>
            <?php if (Auth::isAdmin()): ?>
                <a href="<?= BASE_URL ?>/admin">Quản trị</a>
            <?php endif; ?>
        </nav>
        <div class="auth-nav">
            <?php if (Auth::check()): ?>
                <a href="<?= BASE_URL ?>/account/bookings">Vé của tôi</a>
                <a href="<?= BASE_URL ?>/account">Xin chào, <?= htmlspecialchars(Auth::user()['full_name']) ?></a>
                <a href="<?= BASE_URL ?>/logout">Đăng xuất</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/login">Đăng nhập</a>
                <a href="<?= BASE_URL ?>/register" class="btn-outline">Đăng ký</a>
            <?php endif; ?>
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
