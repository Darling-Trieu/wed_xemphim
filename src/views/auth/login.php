<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
<main class="page-content">
    <form class="form-card" method="post" action="<?= BASE_URL ?>/login">
        <h1>Đăng nhập</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['flash']['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash']['success']) ?></div>
            <?php unset($_SESSION['flash']['success']); ?>
        <?php endif; ?>

        <?= Csrf::field() ?>
        <div class="form-group">
            <label>Email</label>
            <input class="form-control" type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Mật khẩu</label>
            <input class="form-control" type="password" name="password" required>
        </div>
        <button type="submit" class="btn" style="width:100%;">Đăng nhập</button>
        <p class="form-footer">Chưa có tài khoản? <a href="<?= BASE_URL ?>/register">Đăng ký ngay</a></p>
        <p class="form-footer"><a href="<?= BASE_URL ?>/">&larr; Về trang chủ</a></p>
    </form>
</main>
</body>
</html>
