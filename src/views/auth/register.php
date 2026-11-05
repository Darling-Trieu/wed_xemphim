<?php $cssDir = dirname(__DIR__, 3) . '/public/css'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - <?= APP_NAME ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/asset/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&subset=vietnamese&display=swap">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css?v=<?= @filemtime($cssDir . '/style.css') ?>">
</head>
<body class="signup-body">
<main class="signup-page">
    <form class="signup-card" method="post" action="<?= BASE_URL ?>/register">
        <h1>Đăng ký tài khoản</h1>

        <?= Csrf::field() ?>
        <div class="signup-field">
            <label for="full_name">Họ và tên</label>
            <input type="text" id="full_name" name="full_name" maxlength="100" autocomplete="name"
                   value="<?= htmlspecialchars($old['full_name'] ?? '') ?>" required>
            <?php if (isset($errors['full_name'])): ?><p class="alert alert-error" id="full_name-error"><?= htmlspecialchars($errors['full_name']) ?></p><?php endif; ?>
        </div>
        <div class="signup-field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" maxlength="150" autocomplete="email"
                   value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
            <?php if (isset($errors['email'])): ?><p class="alert alert-error" id="email-error"><?= htmlspecialchars($errors['email']) ?></p><?php endif; ?>
        </div>
        <div class="signup-field">
            <label for="phone">Số điện thoại</label>
            <input type="tel" id="phone" name="phone" inputmode="numeric" pattern="0[0-9]{9}" maxlength="10" autocomplete="tel"
                   title="10 chữ số, bắt đầu bằng 0" value="<?= htmlspecialchars($old['phone'] ?? '') ?>" required>
            <?php if (isset($errors['phone'])): ?><p class="alert alert-error" id="phone-error"><?= htmlspecialchars($errors['phone']) ?></p><?php endif; ?>
        </div>
        <div class="signup-field">
            <label for="password">Mật khẩu</label>
            <input type="password" id="password" name="password" autocomplete="new-password" required minlength="8" aria-describedby="password-policy">
            <small id="password-policy">Ít nhất 8 ký tự, có chữ và số.</small>
            <?php if (isset($errors['password'])): ?><p class="alert alert-error" id="password-error"><?= htmlspecialchars($errors['password']) ?></p><?php endif; ?>
        </div>
        <div class="signup-field">
            <label for="password_confirm">Xác nhận mật khẩu</label>
            <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" required minlength="8">
            <?php if (isset($errors['password_confirm'])): ?><p class="alert alert-error" id="password_confirm-error"><?= htmlspecialchars($errors['password_confirm']) ?></p><?php endif; ?>
        </div>
        <button type="submit" class="signup-submit">Đăng ký</button>
        <p class="signup-footer">Đã có tài khoản? <a href="<?= BASE_URL ?>/login">Đăng nhập</a></p>
        <p class="signup-footer"><a class="signup-home" href="<?= BASE_URL ?>/">Về trang chủ</a></p>
    </form>
</main>
</body>
</html>
