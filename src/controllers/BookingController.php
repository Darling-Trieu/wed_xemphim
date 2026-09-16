<?php

require_once __DIR__ . '/../models/Showtime.php';
require_once __DIR__ . '/../models/Seat.php';
require_once __DIR__ . '/../models/Booking.php';
require_once __DIR__ . '/../models/BookingSeat.php';

class BookingController extends Controller
{
    public function create(string $showtimeId): void
    {
        Auth::requireLogin();

        $showtime = Showtime::find((int) $showtimeId);
        if (!$showtime) {
            $this->notFound();
            return;
        }

        $this->view('booking/create', [
            'pageTitle'   => 'Đặt vé - ' . $showtime['movie_title'],
            'showtime'    => $showtime,
            'interactive' => true,
            'pageScripts' => ['seat-map'],
        ]);
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

        $seatIdsRaw = $this->input('seat_ids', '');
        $seatIds = array_filter(array_map('intval', explode(',', (string) $seatIdsRaw)));

        if (empty($seatIds)) {
            $this->flash('error', 'Vui lòng chọn ít nhất 1 ghế.');
            $this->redirect('/booking/' . $showtimeId);
            return;
        }

        $roomSeats = Seat::allByRoom((int) $showtime['room_id']);
        $seatById = [];
        foreach ($roomSeats as $seat) {
            $seatById[(int) $seat['id']] = $seat;
        }

        $seatsToBook = [];
        foreach ($seatIds as $seatId) {
            if (!isset($seatById[$seatId]) || $seatById[$seatId]['status'] !== 'active') {
                $this->flash('error', 'Ghế không hợp lệ, vui lòng chọn lại.');
                $this->redirect('/booking/' . $showtimeId);
                return;
            }
            $price = round($showtime['base_price'] * $seatById[$seatId]['price_multiplier']);
            $seatsToBook[] = ['seat_id' => $seatId, 'price' => $price];
        }

        try {
            $code = Booking::create(Auth::id(), (int) $showtimeId, $seatsToBook);
        } catch (BookingSeatConflictException $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect('/booking/' . $showtimeId);
            return;
        }

        $this->redirect('/booking/success/' . $code);
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
}
