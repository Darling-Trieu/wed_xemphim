<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Booking.php';
require_once __DIR__ . '/../models/BookingSeat.php';

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
        $phone = $this->input('phone');

        if ($fullName === '' || mb_strlen($fullName) > 100) {
            $this->flash('error', 'Họ tên không được để trống và tối đa 100 ký tự.');
            $this->redirect('/account');
            return;
        }
        $owner = $phone ? User::findByPhone($phone) : null;
        if ($owner && (int) $owner['id'] !== Auth::id()) {
            $this->flash('error', 'Số điện thoại đã được sử dụng.');
            $this->redirect('/account');
            return;
        }

        User::updateProfile(Auth::id(), $fullName, $phone ?: null);

        $_SESSION['user']['full_name'] = $fullName;
        $this->flash('success', 'Cập nhật thông tin thành công.');
        $this->redirect('/account');
    }

    public function bookings(): void
    {
        Auth::requireLogin();
        $bookings = Booking::allByUser(Auth::id());

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
