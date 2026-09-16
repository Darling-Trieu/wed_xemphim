<?php

require_once __DIR__ . '/../../models/Showtime.php';
require_once __DIR__ . '/../../models/Movie.php';
require_once __DIR__ . '/../../models/Room.php';

class AdminShowtimeController extends Controller
{
    public function index(): void
    {
        Auth::requireAdmin();
        $this->adminView('admin/showtimes/index', [
            'pageTitle' => 'Quản lý suất chiếu',
            'showtimes' => Showtime::allWithDetails(),
        ]);
    }

    public function createForm(): void
    {
        Auth::requireAdmin();
        $this->adminView('admin/showtimes/form', [
            'pageTitle' => 'Thêm suất chiếu',
            'showtime'  => null,
            'movies'    => Movie::allByStatus(null),
            'rooms'     => Room::allWithCinema(),
        ]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();

        [$data, $error] = $this->collectInput();
        if ($error) {
            $this->flash('error', $error);
            $this->redirect('/admin/showtimes/create');
            return;
        }

        if (Showtime::overlapExists($data['room_id'], $data['show_date'], $data['start_time'], $data['end_time'])) {
            $this->flash('error', 'Phòng chiếu đã có suất chiếu khác trùng khung giờ này.');
            $this->redirect('/admin/showtimes/create');
            return;
        }

        Showtime::create($data);
        $this->flash('success', 'Đã thêm suất chiếu.');
        $this->redirect('/admin/showtimes');
    }

    public function editForm(string $id): void
    {
        Auth::requireAdmin();
        $showtime = Showtime::find((int) $id);
        if (!$showtime) {
            $this->notFound();
            return;
        }
        $this->adminView('admin/showtimes/form', [
            'pageTitle' => 'Sửa suất chiếu',
            'showtime'  => $showtime,
            'movies'    => Movie::allByStatus(null),
            'rooms'     => Room::allWithCinema(),
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();

        [$data, $error] = $this->collectInput();
        if ($error) {
            $this->flash('error', $error);
            $this->redirect('/admin/showtimes/' . $id . '/edit');
            return;
        }

        if (Showtime::overlapExists($data['room_id'], $data['show_date'], $data['start_time'], $data['end_time'], (int) $id)) {
            $this->flash('error', 'Phòng chiếu đã có suất chiếu khác trùng khung giờ này.');
            $this->redirect('/admin/showtimes/' . $id . '/edit');
            return;
        }

        Showtime::update((int) $id, $data);
        $this->flash('success', 'Đã cập nhật suất chiếu.');
        $this->redirect('/admin/showtimes');
    }

    public function delete(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();
        Showtime::delete((int) $id);
        $this->flash('success', 'Đã xóa suất chiếu.');
        $this->redirect('/admin/showtimes');
    }

    /** @return array{0: array, 1: ?string} */
    private function collectInput(): array
    {
        $movieId = (int) $this->input('movie_id');
        $roomId = (int) $this->input('room_id');
        $showDate = (string) $this->input('show_date');
        $startTime = (string) $this->input('start_time');
        $basePrice = (float) $this->input('base_price');

        $movie = $movieId ? Movie::find($movieId) : null;
        if (!$movie || !$roomId || !$showDate || !$startTime || $basePrice <= 0) {
            return [[], 'Vui lòng nhập đầy đủ và hợp lệ thông tin suất chiếu.'];
        }

        $startTimestamp = strtotime("$showDate $startTime");
        $endTimestamp = $startTimestamp + ((int) $movie['duration_minutes'] + 15) * 60;

        $data = [
            'movie_id'   => $movieId,
            'room_id'    => $roomId,
            'show_date'  => $showDate,
            'start_time' => date('H:i:s', $startTimestamp),
            'end_time'   => date('H:i:s', $endTimestamp),
            'base_price' => $basePrice,
        ];

        return [$data, null];
    }
}
