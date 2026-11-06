<?php
// Chạy qua run_tests.php: cùng HTTP client, CSDL và server kiểm thử thật.
if (!defined('TEST_DB') || TEST_DB !== 'movie_booking_test') {
    throw new RuntimeException('Chỉ chạy qua bộ kiểm thử movie_booking_test.');
}

if ($authGroup === null || $authGroup === 'register') {
    section('UC04 - Validation và quyền đăng ký');
    $guest = new Client($A);
    $token = $guest->csrf('/register');
    $base = ['csrf_token' => $token, 'full_name' => 'Khách Kiểm Thử', 'email' => 'registration.test@example.com',
        'phone' => '0977777777', 'password' => 'Phim2026', 'password_confirm' => 'Phim2026'];
    $before = (int) scalar('SELECT COUNT(*) FROM users');
    foreach (['abcdef', '12345678', 'abcdefgh', 'abc1234', str_repeat('a', 72) . '1', "Phim2026\0"] as $password) {
        $r = $guest->post('/register', array_replace($base, ['password' => $password, 'password_confirm' => $password]));
        check('UC04', 'Từ chối mật khẩu không đạt chính sách (' . strlen($password) . ' byte)',
            $r['status'] === 200 && str_contains($r['body'], 'id="password-error"')
            && (int) scalar('SELECT COUNT(*) FROM users') === $before);
    }
    foreach (['phone' => '', 'full_name' => str_repeat('a', 101), 'email' => 'khong-phai-email', 'password_confirm' => 'Khac2026'] as $field => $value) {
        $r = $guest->post('/register', array_replace($base, [$field => $value]));
        check('UC04', "Validation trường $field", $r['status'] === 200
            && str_contains($r['body'], 'id="' . $field . '-error"')
            && (int) scalar('SELECT COUNT(*) FROM users') === $before);
    }
    foreach (['email' => 'user@example.com', 'phone' => '0911111111'] as $field => $value) {
        $r = $guest->post('/register', array_replace($base, [$field => $value]));
        check('UC04', "Từ chối $field trùng tại trường", $r['status'] === 200
            && str_contains($r['body'], 'id="' . $field . '-error"')
            && (int) scalar('SELECT COUNT(*) FROM users') === $before);
    }
    $r = $guest->post('/register', array_replace($base, ['phone' => 'sai', 'full_name' => '<script>alert(1)</script>']));
    check('UC04', 'Giữ tên/email/SĐT, encode HTML, không echo mật khẩu',
        str_contains($r['body'], '&lt;script&gt;alert(1)&lt;/script&gt;') && str_contains($r['body'], 'value="sai"')
        && str_contains($r['body'], 'value="registration.test@example.com"') && !str_contains($r['body'], 'value="Phim2026"'));
    foreach ([$userA, $admin] as $signedIn) {
        $r = $signedIn->post('/register', array_replace($base, ['csrf_token' => $signedIn->csrf('/account')]));
        check('UC04', 'User/Admin không POST tạo tài khoản mới', $r['status'] === 302
            && (int) scalar('SELECT COUNT(*) FROM users') === $before);
    }
    $r = $guest->post('/register', $base);
    $row = db()->query("SELECT * FROM users WHERE email='registration.test@example.com'")->fetch();
    check('UC04', 'Đăng ký hợp lệ tạo customer active với mật khẩu băm', $r['status'] === 302
        && str_ends_with((string) $r['location'], '/login') && $row && $row['role'] === 'customer'
        && $row['status'] === 'active' && password_verify('Phim2026', $row['password_hash'])
        && (password_get_info($row['password_hash'])['options']['cost'] ?? 0) >= 12);
    check('UC04', 'Có thông báo thành công tại trang đăng nhập', str_contains($guest->follow($r)['body'], 'Đăng ký thành công'));
}

if ($authGroup === null || $authGroup === 'profile') {
    section('UC06 - Validation và giữ dữ liệu hồ sơ');
    $profileUser = new Client($A);
    $profileUser->login('user@example.com', 'user123');
    $id = userId('user@example.com');
    $original = db()->query("SELECT full_name,phone,email FROM users WHERE id=$id")->fetch();
    $token = $profileUser->csrf('/account');
    foreach (['', '12345', '0900000000'] as $phone) {
        $r = $profileUser->post('/account', ['csrf_token' => $token, 'full_name' => 'Tên Vừa Nhập', 'phone' => $phone]);
        check('UC06', 'SĐT trống/sai/trùng: lỗi tại trường, giữ tên và SĐT', $r['status'] === 200
            && str_contains($r['body'], 'id="phone-error"') && str_contains($r['body'], 'value="Tên Vừa Nhập"')
            && str_contains($r['body'], 'value="' . $phone . '"')
            && db()->query("SELECT full_name,phone,email FROM users WHERE id=$id")->fetch() === $original);
    }
    foreach (['', str_repeat('a', 101)] as $name) {
        $r = $profileUser->post('/account', ['csrf_token' => $token, 'full_name' => $name, 'phone' => '0977777776']);
        check('UC06', 'Tên trống/quá dài: giữ dữ liệu, không ghi CSDL', $r['status'] === 200
            && str_contains($r['body'], 'id="full_name-error"') && str_contains($r['body'], 'value="0977777776"')
            && db()->query("SELECT full_name,phone,email FROM users WHERE id=$id")->fetch() === $original);
    }
    $r = $profileUser->post('/account', ['csrf_token' => $token, 'full_name' => '<script>alert(2)</script>', 'phone' => 'sai']);
    check('UC06', 'Encode dữ liệu hồ sơ nhập lại chống XSS', str_contains($r['body'], '&lt;script&gt;alert(2)&lt;/script&gt;')
        && !str_contains($r['body'], '<script>alert(2)</script>'));
    $r = $profileUser->post('/account', ['csrf_token' => $token, 'full_name' => 'Tên Hợp Lệ', 'phone' => $original['phone'], 'email' => 'changed@example.com']);
    check('UC06', 'Lưu hợp lệ, giữ SĐT của mình và không cho đổi email', $r['status'] === 302
        && scalar('SELECT full_name FROM users WHERE id=?', [$id]) === 'Tên Hợp Lệ'
        && scalar('SELECT email FROM users WHERE id=?', [$id]) === $original['email']);
}

if ($authGroup === null || $authGroup === 'password') {
    section('UC06 - Đổi mật khẩu an toàn');
    $guest = new Client($A);
    check('UC06-password', 'Guest không được đổi mật khẩu', $guest->post('/account/password', [])['status'] === 302);
    $pwUser = new Client($A);
    $pwUser->login('user@example.com', 'user123');
    $id = userId('user@example.com');
    $hashBefore = scalar('SELECT password_hash FROM users WHERE id=?', [$id]);
    $otherId = userId('ngoc.tran@example.com');
    $otherHash = scalar('SELECT password_hash FROM users WHERE id=?', [$otherId]);
    $page = $pwUser->get('/account');
    check('UC06-password', 'Trang hồ sơ có form đổi mật khẩu', str_contains($page['body'], 'action="/account/password"'));
    $token = $pwUser->csrf('/account');
    $data = ['csrf_token' => $token, 'current_password' => 'user123', 'new_password' => ' Rap2026 ', 'new_password_confirm' => ' Rap2026 '];
    $r = $pwUser->post('/account/password', array_diff_key($data, ['csrf_token' => true]));
    check('UC06-password', 'Không token CSRF bị chặn và hash không đổi', $r['status'] === 419
        && scalar('SELECT password_hash FROM users WHERE id=?', [$id]) === $hashBefore);
    $r = $pwUser->post('/account/password', array_replace($data, ['current_password' => 'sai']));
    check('UC06-password', 'Mật khẩu cũ sai bị từ chối', $r['status'] === 200 && str_contains($r['body'], 'id="current_password-error"')
        && scalar('SELECT password_hash FROM users WHERE id=?', [$id]) === $hashBefore);
    foreach (['', 'abc1234', 'abcdefgh', '12345678', str_repeat('a', 72) . '1', "Phim2026\0"] as $pw) {
        $r = $pwUser->post('/account/password', array_replace($data, ['new_password' => $pw, 'new_password_confirm' => $pw]));
        check('UC06-password', 'Mật khẩu mới yếu/quá dài/NUL không thay đổi hash', $r['status'] === 200
            && str_contains($r['body'], 'id="new_password-error"')
            && scalar('SELECT password_hash FROM users WHERE id=?', [$id]) === $hashBefore);
    }
    $r = $pwUser->post('/account/password', array_replace($data, ['new_password_confirm' => 'Khac2026']));
    check('UC06-password', 'Xác nhận không khớp, không echo lại mật khẩu', $r['status'] === 200
        && str_contains($r['body'], 'id="new_password_confirm-error"') && !str_contains($r['body'], 'value=" Rap2026 "')
        && !str_contains($r['body'], 'value="user123"') && scalar('SELECT password_hash FROM users WHERE id=?', [$id]) === $hashBefore);
    $r = $pwUser->post('/account/password', $data + ['user_id' => $otherId]);
    $hash = scalar('SELECT password_hash FROM users WHERE id=?', [$id]);
    check('UC06-password', 'Đổi hợp lệ, bcrypt cost 12, chỉ tài khoản session, không trim mật khẩu', $r['status'] === 302
        && $hash !== $hashBefore && password_verify(' Rap2026 ', $hash) && !password_verify('Rap2026', $hash)
        && (password_get_info($hash)['options']['cost'] ?? 0) >= 12
        && scalar('SELECT password_hash FROM users WHERE id=?', [$otherId]) === $otherHash);
    check('UC06-password', 'Thành công có thông báo', str_contains($pwUser->follow($r)['body'], 'Đổi mật khẩu thành công'));
    check('UC06-password', 'Token CSRF trước đổi mật khẩu bị vô hiệu', $pwUser->post('/account/password', $data)['status'] === 419);
    $oldLogin = (new Client($A))->login('user@example.com', 'user123');
    $newLogin = (new Client($A))->login('user@example.com', ' Rap2026 ');
    check('UC06-password', 'Mật khẩu cũ không đăng nhập được; mật khẩu mới đăng nhập được', $oldLogin['status'] === 200
        && str_contains($oldLogin['body'], 'Tài khoản hoặc mật khẩu chưa đúng') && $newLogin['status'] === 302);
    $audit = db()->query("SELECT * FROM audit_logs WHERE action='password_change' AND user_id=$id")->fetchAll();
    check('UC06-password', 'Có audit đổi mật khẩu, không lưu mật khẩu/hash', count($audit) === 1
        && !str_contains(json_encode($audit), 'Rap2026') && !str_contains(json_encode($audit), $hash));
    // Khôi phục fixture chỉ trong movie_booking_test để các ca khác không phụ thuộc thứ tự.
    db()->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([$hashBefore, $id]);
}
