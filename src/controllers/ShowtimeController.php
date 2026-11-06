<?php

require_once __DIR__ . '/../models/Showtime.php';
require_once __DIR__ . '/../models/Movie.php';
require_once __DIR__ . '/../models/Cinema.php';
require_once __DIR__ . '/../models/SeatType.php';
require_once __DIR__ . '/../models/Room.php';

class ShowtimeController extends Controller
{
    public function search(): void
    {
        $movieId = (int) $this->input('movie_id', 0) ?: null;
        $cinemaId = (int) $this->input('cinema_id', 0) ?: null;
        $roomId = (int) $this->input('room_id', 0) ?: null;
        $date = (string) $this->input('date', '') ?: null;

        // Ngày (nếu chọn) phải nằm trong 7 ngày tới, tính cả hôm nay
        $minDate = date('Y-m-d');
        $maxDate = date('Y-m-d', strtotime('+' . (Showtime::SEARCH_DAYS - 1) . ' days'));
        $error = null;
        if ($date !== null && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date < $minDate || $date > $maxDate)) {
            $error = 'Chỉ tra cứu được lịch chiếu trong ' . Showtime::SEARCH_DAYS . ' ngày tới.';
        }

        $this->view('showtimes/search', [
            'pageTitle' => 'Lịch chiếu',
            'movies'    => Movie::allByStatus('now_showing'),
            'cinemas'   => Cinema::activeList(),
            'rooms'     => Room::activeWithCinema(),
            'showtimes' => $error ? [] : Showtime::search($movieId, $cinemaId, $roomId, $date),
            'error'     => $error,
            'filters'   => ['movie_id' => $movieId, 'cinema_id' => $cinemaId, 'room_id' => $roomId, 'date' => $date],
            'minDate'   => $minDate,
            'maxDate'   => $maxDate,
        ]);
    }

    /**
     * Sơ đồ ghế (UC03). Khách chưa đăng nhập cũng chọn được ghế (lưu ở trình duyệt);
     * suất đã hủy / đóng bán thì chỉ xem.
     */
    public function seats(string $id): void
    {
        $showtime = Showtime::find((int) $id);
        if (!$showtime) {
            $this->notFound();
            return;
        }

        $this->view('showtimes/seats', [
            'pageTitle'   => 'Sơ đồ ghế - ' . $showtime['movie_title'],
            'showtime'    => $showtime,
            'seatTypes'   => SeatType::all(),
            'saleClosed'  => Showtime::isSaleClosed($showtime),
            'pageScripts' => ['seat-map'],
        ]);
    }
}
