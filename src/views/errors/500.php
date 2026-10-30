<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lỗi hệ thống</title>
    <link rel="stylesheet" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/css/style.css">
</head>
<body>
<main class="container page-content">
    <div class="not-found">
        <h1>500</h1>
        <p><?= htmlspecialchars(ErrorHandler::MESSAGE) ?></p>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/">Về trang chủ</a>
    </div>
</main>
</body>
</html>
