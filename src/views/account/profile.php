<h1 class="section-title">Thông tin tài khoản</h1>

<form class="form-card" method="post" action="<?= BASE_URL ?>/account" style="margin-left:0;">
    <?= Csrf::field() ?>
    <div class="form-group">
        <label>Họ và tên</label>
        <input class="form-control" type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
    </div>
    <div class="form-group">
        <label>Email</label>
        <input class="form-control" type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
    </div>
    <div class="form-group">
        <label>Số điện thoại</label>
        <input class="form-control" type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
    </div>
    <button type="submit" class="btn">Lưu thay đổi</button>
</form>
