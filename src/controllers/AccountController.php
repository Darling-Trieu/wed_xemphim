<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Booking.php';
require_once __DIR__ . '/../models/BookingSeat.php';
require_once __DIR__ . '/../models/AuditLog.php';

class AccountController extends Controller
{
    public function profile(): void
    {
        Auth::requireLogin();
        $user = User::find(Auth::id());

        $this->view('account/profile', [
            'pageTitle' => 'Thông tin tài khoản',
            'user'      => $user,
        ]);
    }

    public function update(): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $fullName = (string) $this->input('full_name');
        $phone = (string) $this->input('phone', '');

        $errors = [];
        if (!$this->validLength($fullName, 100)) {
            $errors['full_name'] = 'Họ tên không được để trống và tối đa 100 ký tự.';
        }
        if (!$this->validPhone($phone)) {
            $errors['phone'] = 'Số điện thoại phải gồm 10 chữ số, bắt đầu bằng 0.';
        }
        $owner = !isset($errors['phone']) ? User::findByPhone($phone) : null;
        if ($owner && (int) $owner['id'] !== Auth::id()) {
            $errors['phone'] = 'Số điện thoại đã được sử dụng.';
        }
        if ($errors) {
            $this->view('account/profile', [
                'pageTitle' => 'Thông tin tài khoản',
                'user' => User::find(Auth::id()),
                'old' => ['full_name' => $fullName, 'phone' => $phone],
                'errors' => $errors,
            ]);
            return;
        }

        User::updateProfile(Auth::id(), $fullName, $phone ?: null);

        $_SESSION['user']['full_name'] = $fullName;
        $this->flash('success', 'Cập nhật thông tin thành công.');
        $this->redirect('/account');
    }

    public function changePassword(): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $user = User::find(Auth::id());
        $current = $this->passwordInput('current_password');
        $new = $this->passwordInput('new_password');
        $confirm = $this->passwordInput('new_password_confirm');
        $errors = [];
        if ($current === '' || !password_verify($current, $user['password_hash'])) {
            $errors['current_password'] = 'Mật khẩu cũ không đúng.';
        }
        if (!$this->validPassword($new)) {
            $errors['new_password'] = 'Mật khẩu mới phải có ít nhất 8 ký tự, có chữ và số, tối đa 72 byte và không chứa ký tự NUL.';
        }
        if ($new !== $confirm) {
            $errors['new_password_confirm'] = 'Xác nhận mật khẩu không khớp.';
        }
        if (!$errors && !User::changePassword(Auth::id(), $user['password_hash'], $new)) {
            $errors['current_password'] = 'Mật khẩu đã thay đổi, vui lòng nhập lại mật khẩu hiện tại.';
        }
        if ($errors) {
            $this->view('account/profile', [
                'pageTitle' => 'Thông tin tài khoản', 'user' => $user, 'passwordErrors' => $errors,
            ]);
            return;
        }

        AuditLog::write('password_change', 'user', Auth::id());
        Auth::regenerateSession();
        unset($_SESSION['csrf_token']);
        $this->flash('success', 'Đổi mật khẩu thành công.');
        $this->redirect('/account');
    }

    public function bookings(): void
    {
        Auth::requireLogin();
        $userId = Auth::id();
        Booking::markCancelledAsSeen($userId);
        $bookings = Booking::allByUser($userId);

        $this->view('account/bookings', [
            'pageTitle' => 'Lịch sử đặt vé',
            'bookings'  => $bookings,
        ]);
    }

    public function bookingDetail(string $code): void
    {
        Auth::requireLogin();
        $booking = Booking::findByCode($code);

        if (!$booking || (int) $booking['user_id'] !== Auth::id()) {
            $this->notFound();
            return;
        }

        $seats = BookingSeat::allByBooking((int) $booking['id']);

        $this->view('account/booking_detail', [
            'pageTitle' => 'Chi tiết vé ' . $code,
            'booking'   => $booking,
            'seats'     => $seats,
        ]);
    }
}
