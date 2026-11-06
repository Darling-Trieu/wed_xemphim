<?php $cssDir = dirname(__DIR__, 3) . '/public/css'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký tài khoản - <?= APP_NAME ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/asset/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css?v=<?= @filemtime($cssDir . '/style.css') ?>">
</head>
<body class="signup-body">
<main class="signup-page">
    <form class="signup-card" method="post" action="<?= BASE_URL ?>/register">
        <div class="signup-header">
            <div class="signup-logo-badge">
                <svg viewBox="0 0 24 24"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
            </div>
            <h1>Đăng ký tài khoản</h1>
            <p class="signup-subtitle">Khám phá thế giới điện ảnh đỉnh cao cùng <?= APP_NAME ?></p>
        </div>

        <?= Csrf::field() ?>

        <!-- Họ và tên -->
        <div class="signup-field">
            <label for="full_name">Họ và tên</label>
            <div class="signup-input-wrap">
                <input type="text" id="full_name" name="full_name" maxlength="100" autocomplete="name"
                       placeholder="Ví dụ: Nguyễn Văn A"
                       value="<?= htmlspecialchars($old['full_name'] ?? '') ?>" required>
                <svg class="signup-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <?php if (isset($errors['full_name'])): ?><p class="alert alert-error" id="full_name-error"><?= htmlspecialchars($errors['full_name']) ?></p><?php endif; ?>
        </div>

        <!-- Hàng đôi: Email & Số điện thoại -->
        <div class="signup-row-2">
            <div class="signup-field">
                <label for="email">Email</label>
                <div class="signup-input-wrap">
                    <input type="email" id="email" name="email" maxlength="150" autocomplete="email"
                           placeholder="email@domain.com"
                           value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
                    <svg class="signup-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </div>
                <?php if (isset($errors['email'])): ?><p class="alert alert-error" id="email-error"><?= htmlspecialchars($errors['email']) ?></p><?php endif; ?>
            </div>

            <div class="signup-field">
                <label for="phone">Số điện thoại</label>
                <div class="signup-input-wrap">
                    <input type="tel" id="phone" name="phone" inputmode="numeric" pattern="0[0-9]{9}" maxlength="10" autocomplete="tel"
                           placeholder="0912345678"
                           title="10 chữ số, bắt đầu bằng 0" value="<?= htmlspecialchars($old['phone'] ?? '') ?>" required>
                    <svg class="signup-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                </div>
                <?php if (isset($errors['phone'])): ?><p class="alert alert-error" id="phone-error"><?= htmlspecialchars($errors['phone']) ?></p><?php endif; ?>
            </div>
        </div>

        <!-- Hàng đôi: Mật khẩu & Xác nhận mật khẩu -->
        <div class="signup-row-2">
            <div class="signup-field">
                <label for="password">Mật khẩu</label>
                <div class="signup-input-wrap">
                    <input type="password" id="password" name="password" autocomplete="new-password" required minlength="8"
                           placeholder="Tối thiểu 8 ký tự"
                           aria-describedby="password-policy"
                           oninput="cineCheckStrength(this.value)">
                    <svg class="signup-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <button type="button" class="signup-toggle-pwd" onclick="cineTogglePwd('password', this)" aria-label="Hiện/ẩn mật khẩu">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                <?php if (isset($errors['password'])): ?><p class="alert alert-error" id="password-error"><?= htmlspecialchars($errors['password']) ?></p><?php endif; ?>
            </div>

            <div class="signup-field">
                <label for="password_confirm">Xác nhận mật khẩu</label>
                <div class="signup-input-wrap">
                    <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" required minlength="8"
                           placeholder="Nhập lại mật khẩu">
                    <svg class="signup-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <button type="button" class="signup-toggle-pwd" onclick="cineTogglePwd('password_confirm', this)" aria-label="Hiện/ẩn mật khẩu">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                <?php if (isset($errors['password_confirm'])): ?><p class="alert alert-error" id="password_confirm-error"><?= htmlspecialchars($errors['password_confirm']) ?></p><?php endif; ?>
            </div>
        </div>

        <small id="password-policy" class="signup-policy">Ít nhất 8 ký tự, có chữ và số.</small>
        <div class="signup-meter"><div id="pwd-meter-bar" class="signup-meter__bar"></div></div>
        <div class="signup-meter-hints">
            <span>Độ mạnh: <b id="pwd-strength-label">Chưa nhập</b></span>
        </div>

        <button type="submit" class="signup-submit">
            Đăng ký
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </button>

        <p class="signup-footer">Đã có tài khoản? <a href="<?= BASE_URL ?>/login">Đăng nhập</a></p>
        <p class="signup-footer"><a class="signup-home" href="<?= BASE_URL ?>/">Về trang chủ</a></p>
    </form>
</main>

<script>
function cineTogglePwd(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
    } else {
        input.type = 'password';
        btn.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    }
}

function cineCheckStrength(val) {
    const bar = document.getElementById('pwd-meter-bar');
    const label = document.getElementById('pwd-strength-label');
    if (!bar || !label) return;

    if (!val) {
        bar.style.width = '0%';
        label.textContent = 'Chưa nhập';
        label.style.color = '#8c8c9a';
        return;
    }

    let score = 0;
    if (val.length >= 8) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[a-zA-Z]/.test(val)) score++;
    if (/[^a-zA-Z0-9]/.test(val)) score++;

    if (score <= 1) {
        bar.style.width = '25%';
        bar.style.backgroundColor = '#e50914';
        label.textContent = 'Yếu';
        label.style.color = '#e50914';
    } else if (score === 2) {
        bar.style.width = '50%';
        bar.style.backgroundColor = '#ff9800';
        label.textContent = 'Trung bình';
        label.style.color = '#ff9800';
    } else if (score === 3) {
        bar.style.width = '75%';
        bar.style.backgroundColor = '#2196f3';
        label.textContent = 'Khá';
        label.style.color = '#2196f3';
    } else {
        bar.style.width = '100%';
        bar.style.backgroundColor = '#1eb980';
        label.textContent = 'Mạnh';
        label.style.color = '#1eb980';
    }
}
</script>
</body>
</html>
