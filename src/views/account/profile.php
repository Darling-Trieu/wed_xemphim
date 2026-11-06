<h1 class="section-title">Thông tin tài khoản</h1>

<form class="form-card" method="post" action="<?= BASE_URL ?>/account" style="margin-left:0;">
    <?= Csrf::field() ?>
    <div class="form-group">
        <label for="full_name">Họ và tên</label>
        <input class="form-control" id="full_name" type="text" name="full_name" maxlength="100" value="<?= htmlspecialchars($old['full_name'] ?? $user['full_name']) ?>" required>
        <?php if (isset($errors['full_name'])): ?><p class="alert alert-error" id="full_name-error"><?= htmlspecialchars($errors['full_name']) ?></p><?php endif; ?>
    </div>
    <div class="form-group">
        <label>Email</label>
        <input class="form-control" type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
    </div>
    <div class="form-group">
        <label for="phone">Số điện thoại</label>
        <input class="form-control" id="phone" type="tel" name="phone" pattern="0[0-9]{9}" maxlength="10" inputmode="numeric" value="<?= htmlspecialchars($old['phone'] ?? $user['phone'] ?? '') ?>" required>
        <?php if (isset($errors['phone'])): ?><p class="alert alert-error" id="phone-error"><?= htmlspecialchars($errors['phone']) ?></p><?php endif; ?>
    </div>
    <button type="submit" class="btn">Lưu thay đổi</button>
</form>

<h2 class="section-title">Đổi mật khẩu</h2>
<form class="form-card" method="post" action="<?= BASE_URL ?>/account/password" style="margin-left:0;">
    <?= Csrf::field() ?>
    <?php foreach (['current_password' => 'Mật khẩu cũ', 'new_password' => 'Mật khẩu mới', 'new_password_confirm' => 'Xác nhận mật khẩu mới'] as $field => $label): ?>
        <div class="form-group">
            <label for="<?= $field ?>"><?= $label ?></label>
            <input class="form-control" type="password" id="<?= $field ?>" name="<?= $field ?>"
                   autocomplete="<?= $field === 'current_password' ? 'current-password' : 'new-password' ?>" required
                   <?= $field !== 'current_password' ? 'minlength="8" aria-describedby="new-password-policy"' : '' ?>>
            <?php if (isset($passwordErrors[$field])): ?>
                <p class="alert alert-error" id="<?= $field ?>-error"><?= htmlspecialchars($passwordErrors[$field]) ?></p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <p id="new-password-policy">Mật khẩu mới có ít nhất 8 ký tự, có chữ và số.</p>
    <button type="submit" class="btn">Đổi mật khẩu</button>
</form>
