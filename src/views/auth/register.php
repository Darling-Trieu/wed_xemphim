<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
<main class="page-content">
    <form class="form-card" method="post" action="<?= BASE_URL ?>/register">
        <h1>Đăng ký tài khoản</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
        <?php endif; ?>

        <?= Csrf::field() ?>
        <div class="form-group">
            <label>Họ và tên</label>
            <input class="form-control" type="text" name="full_name" value="<?= htmlspecialchars($old['full_name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input class="form-control" type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Số điện thoại</label>
            <input class="form-control" type="text" name="phone" value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Mật khẩu</label>
            <input class="form-control" type="password" name="password" required minlength="6">
        </div>
        <div class="form-group">
            <label>Xác nhận mật khẩu</label>
            <input class="form-control" type="password" name="password_confirm" required minlength="6">
        </div>
        <button type="submit" class="btn" style="width:100%;">Đăng ký</button>
        <p class="form-footer">Đã có tài khoản? <a href="<?= BASE_URL ?>/login">Đăng nhập</a></p>
        <p class="form-footer"><a href="<?= BASE_URL ?>/">&larr; Về trang chủ</a></p>
    </form>
</main>
</body>
</html>
