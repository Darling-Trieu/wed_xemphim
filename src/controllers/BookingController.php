<?php

require_once __DIR__ . '/../models/Showtime.php';
require_once __DIR__ . '/../models/Seat.php';
require_once __DIR__ . '/../models/Booking.php';
require_once __DIR__ . '/../models/BookingSeat.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../core/Mailer.php';
require_once __DIR__ . '/../models/SeatHold.php';

class BookingController extends Controller
{
    /**
     * Khách bấm "Đăng nhập để đặt vé" được đưa tới đây: requireLogin nhớ URL này, đăng nhập
     * xong quay lại sơ đồ ghế của đúng suất, ghế đã chọn được khôi phục từ sessionStorage.
     */
    public function create(string $showtimeId): void
    {
        Auth::requireLogin();
        $this->redirect('/showtimes/' . (int) $showtimeId . '/seats');
    }

    /** Màn hình xác nhận: chưa tạo đơn, chỉ kiểm tra và hiển thị giá do server tính. */
    public function confirm(string $showtimeId): void
    {
        Auth::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Csrf::verifyRequest();
        }

        $showtime = Showtime::find((int) $showtimeId);
        if (!$showtime) {
            $this->notFound();
            return;
        }

        $seatIds = Booking::normalizeSeatIds($this->input('seat_ids', ''));

        try {
            $tickets = Booking::prepareTickets($showtime, $seatIds, null);
            $taken = Booking::soldLabels((int) $showtime['id'], $seatIds);
            if ($taken) {
                throw new BookingSeatConflictException('Ghế ' . implode(', ', $taken) . ' đã có người đặt, vui lòng chọn ghế khác.');
            }
        } catch (BookingException $e) {
            $this->backToSeatMap($showtime, $e->getMessage());
            return;
        }

        $this->renderConfirm($showtime, $tickets, null);
    }

    public function store(string $showtimeId): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $showtime = Showtime::find((int) $showtimeId);
        if (!$showtime) {
            $this->notFound();
            return;
        }

        $seatIds = Booking::normalizeSeatIds($this->input('seat_ids', ''));
        if (empty($seatIds)) {
            $this->backToSeatMap($showtime, 'Vui lòng chọn ít nhất 1 ghế.');
            return;
        }
        $ageConfirmed = $this->input('age_confirmed') === '1';

        try {
            SeatHold::assertOwned((int) $showtime['id'], $seatIds, session_id());
            $code = Booking::create(Auth::id(), (int) $showtime['id'], $seatIds, $ageConfirmed);
        } catch (BookingAgeException $e) {
            // Thiếu xác nhận độ tuổi: ở lại màn hình xác nhận với đúng các ghế đã chọn
            try {
                $tickets = Booking::prepareTickets($showtime, $seatIds, null);
            } catch (BookingException $inner) {
                $this->backToSeatMap($showtime, $inner->getMessage());
                return;
            }
            $this->renderConfirm($showtime, $tickets, $e->getMessage());
            return;
        } catch (BookingException $e) {
            // Ghế bị người khác lấy / suất đóng bán...: quay lại sơ đồ với trạng thái mới
            $this->backToSeatMap($showtime, $e->getMessage());
            return;
        }
         catch (Throwable $e) {
            error_log('Booking failed: ' . $e->getMessage());
            $this->backToSeatMap($showtime, 'Có lỗi hệ thống, vui lòng thử lại.');
            return;
        }
        try {
            SeatHold::releaseSession((int) $showtime['id'], session_id());
        } catch (Throwable $e) {
            error_log('Release hold failed: ' . $e->getMessage());
        }

        $emailSent = false;
        $email = '';
        try {
            $user = User::find(Auth::id());
            $booking = Booking::findByCode($code);
            if ($user && $booking) {
                $email = (string) $user['email'];
                $seats = BookingSeat::allByBooking((int) $booking['id']);
                $emailSent = Mailer::sendBookingConfirmation($user, $booking, $seats);
            }
        } catch (Throwable $e) {
            error_log('Mail failed: ' . $e->getMessage());
        }
        
        $this->flash(
            'success',
            $emailSent
                ? 'Vé xác nhận đã được gửi tới email ' . $email . '.'
                : 'Đặt vé thành công. Bạn có thể xem vé ở mục Vé của tôi.'
        );
        
        $this->redirect('/booking/success/' . $code, 303);
    }

    public function success(string $code): void
    {
        Auth::requireLogin();

        $booking = Booking::findByCode($code);
        if (!$booking || ((int) $booking['user_id'] !== Auth::id() && !Auth::isAdmin())) {
            $this->notFound();
            return;
        }

        $seats = BookingSeat::allByBooking((int) $booking['id']);

        $this->view('booking/success', [
            'pageTitle' => 'Đặt vé thành công',
            'booking'   => $booking,
            'seats'     => $seats,
        ]);
    }

    private function renderConfirm(array $showtime, array $tickets, ?string $error): void
    {
        $this->view('booking/confirm', [
            'pageTitle'   => 'Xác nhận đặt vé - ' . $showtime['movie_title'],
            'showtime'    => $showtime,
            'tickets'     => $tickets,
            'error'       => $error,
            'pageScripts' => ['seat-map'],
        ]);
    }

    private function backToSeatMap(array $showtime, string $message): void
    {
        $this->flash('error', $message);
        $this->redirect('/showtimes/' . (int) $showtime['id'] . '/seats');
    }
}
