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
        <?php foreach (['error' => 'alert-error', 'success' => 'alert-success'] as $key => $class): ?>
            <?php if (!empty($_SESSION['flash'][$key])): ?>
                <div class="alert <?= $class ?>"><?= htmlspecialchars($_SESSION['flash'][$key]) ?></div>
                <?php unset($_SESSION['flash'][$key]); ?>
            <?php endif; ?>
        <?php endforeach; ?>

        <?= Csrf::field() ?>
        <div class="form-group">
            <label for="login">Email hoặc số điện thoại</label>
            <input class="form-control" type="text" id="login" name="login" autocomplete="username"
                   value="<?= htmlspecialchars($old['login'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="password">Mật khẩu</label>
            <input class="form-control" type="password" id="password" name="password" autocomplete="current-password">
        </div>
        <button type="submit" class="btn" style="width:100%;">Đăng nhập</button>
        <p class="form-footer">Chưa có tài khoản? <a href="<?= BASE_URL ?>/register">Đăng ký ngay</a></p>
        <p class="form-footer"><a href="<?= BASE_URL ?>/">&larr; Về trang chủ</a></p>
    </form>
</main>
</body>
</html>
