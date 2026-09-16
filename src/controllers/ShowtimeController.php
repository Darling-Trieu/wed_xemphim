<?php

require_once __DIR__ . '/../models/Showtime.php';
require_once __DIR__ . '/../models/Movie.php';
require_once __DIR__ . '/../models/Cinema.php';

class ShowtimeController extends Controller
{
    public function search(): void
    {
        $movieId = $this->input('movie_id') ? (int) $this->input('movie_id') : null;
        $cinemaId = $this->input('cinema_id') ? (int) $this->input('cinema_id') : null;
        $date = $this->input('date') ?: null;

        $showtimes = Showtime::search($movieId, $cinemaId, $date);

        $this->view('showtimes/search', [
            'pageTitle' => 'Lịch chiếu',
            'movies'    => Movie::allByStatus('now_showing'),
            'cinemas'   => Cinema::activeList(),
            'showtimes' => $showtimes,
            'movieId'   => $movieId,
            'cinemaId'  => $cinemaId,
            'date'      => $date,
        ]);
    }

    /** Xem sơ đồ ghế (read-only cho khách chưa đăng nhập). */
    public function seats(string $id): void
    {
        $showtime = Showtime::find((int) $id);
        if (!$showtime) {
            $this->notFound();
            return;
        }

        $this->view('showtimes/seats', [
            'pageTitle' => 'Sơ đồ ghế - ' . $showtime['movie_title'],
            'showtime'  => $showtime,
            'interactive' => false,
            'pageScripts' => ['seat-map'],
        ]);
    }
}
