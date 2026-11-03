<?php
$cssDir = dirname(__DIR__, 3) . '/public/css';
$invalid = $invalid ?? [];
// Thông báo trên form: lỗi đăng nhập hoặc flash (vd. "Tài khoản đang bị khóa", "Đăng ký thành công")
$notices = [];
foreach ($errors ?? [] as $message) {
    $notices[] = ['error', $message];
}
foreach (['error', 'success'] as $key) {
    if (!empty($_SESSION['flash'][$key])) {
        $notices[] = [$key, $_SESSION['flash'][$key]];
        unset($_SESSION['flash'][$key]);
    }
}
// Khách đến từ sơ đồ ghế: nhắc rằng đăng nhập xong sẽ quay lại đúng suất
$fromSeatMap = preg_match('#/(booking|showtimes)/\d+#', (string) ($_SESSION['redirect_after_login'] ?? '')) === 1;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - <?= APP_NAME ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/asset/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&subset=vietnamese&display=swap">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css?v=<?= @filemtime($cssDir . '/style.css') ?>">
</head>
<body class="auth-body">
<header class="auth-header">
    <a class="brand" href="<?= BASE_URL ?>/" aria-label="<?= APP_NAME ?> - Trang chủ"><?php include __DIR__ . '/../partials/logo.php'; ?></a>
    <nav class="auth-header__nav" aria-label="Điều hướng chính">
        <a href="<?= BASE_URL ?>/">Trang chủ</a>
        <a href="<?= BASE_URL ?>/movies">Phim</a>
        <a href="<?= BASE_URL ?>/showtimes">Lịch chiếu</a>
    </nav>
    <div class="auth-header__actions">
        <a class="auth-btn auth-btn--primary" href="<?= BASE_URL ?>/login" aria-current="page">Đăng nhập</a>
        <a class="auth-btn auth-btn--ghost" href="<?= BASE_URL ?>/register">Đăng ký</a>
    </div>
</header>

<main class="auth-main" style="background-image:url('<?= BASE_URL ?>/asset/pic/login-cinema.png');">
    <section class="auth-intro">
        <p class="auth-eyebrow">Trải nghiệm điện ảnh của bạn</p>
        <h2 class="auth-slogan">Mỗi suất chiếu, một câu chuyện.</h2>
        <p class="auth-quote">Chọn phim, chọn ghế và giữ trọn khoảnh khắc trên màn ảnh lớn.</p>
        <?php if ($fromSeatMap): ?>
            <div class="auth-continuity">
                <strong>Tiếp tục từ ghế bạn đã chọn</strong>
                <span>Sau đăng nhập, quay lại đúng suất chiếu để xác nhận. Ghế chưa được giữ chỗ.</span>
            </div>
        <?php endif; ?>
    </section>

    <section class="auth-panel">
        <div class="auth-panel__intro">
            <p class="auth-panel__eyebrow">Chào mừng bạn trở lại</p>
            <h1>Đăng nhập CineQ</h1>
            <p>Bộ phim tiếp theo đang chờ bạn.</p>
        </div>

        <?php foreach ($notices as [$type, $message]): ?>
            <div class="alert alert-<?= $type ?>" role="alert"><?= htmlspecialchars($message) ?></div>
        <?php endforeach; ?>

        <form class="auth-form" method="post" action="<?= BASE_URL ?>/login" novalidate>
            <?= Csrf::field() ?>
            <div class="auth-field<?= in_array('login', $invalid, true) ? ' is-invalid' : '' ?>">
                <label for="login">Email / SĐT</label>
                <p class="auth-field__hint" id="login-hint">Email hoặc SĐT 10 chữ số đã đăng ký.</p>
                <input type="text" id="login" name="login" autocomplete="username" aria-describedby="login-hint"
                       value="<?= htmlspecialchars($old['login'] ?? '') ?>">
                <?php if (in_array('login', $invalid, true)): ?>
                    <p class="auth-field__error">Nhập email hoặc SĐT đúng định dạng.</p>
                <?php endif; ?>
            </div>
            <div class="auth-field<?= in_array('password', $invalid, true) ? ' is-invalid' : '' ?>">
                <label for="password">Mật khẩu</label>
                <p class="auth-field__hint" id="password-hint">Mật khẩu có ít nhất 6 ký tự.</p>
                <input type="password" id="password" name="password" autocomplete="current-password" aria-describedby="password-hint">
                <?php if (in_array('password', $invalid, true)): ?>
                    <p class="auth-field__error">Nhập mật khẩu tài khoản của bạn.</p>
                <?php endif; ?>
            </div>
            <button type="submit" class="auth-submit">Đăng nhập &rarr;</button>
            <a class="auth-link" href="<?= BASE_URL ?>/register">Chưa có tài khoản? Đăng ký ngay</a>
        </form>

        <p class="auth-note">Đăng nhập để quản lý vé và tiếp tục những cuộc hẹn điện ảnh.</p>
    </section>
</main>

<footer class="auth-footer">
    <span class="auth-footer__brand">CineQ</span>
    <span>Đặt vé xem phim trực tuyến &middot; &copy; <?= date('Y') ?> <?= APP_NAME ?></span>
</footer>
</body>
</html>
