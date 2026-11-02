<?php

require_once __DIR__ . '/../../models/Showtime.php';
require_once __DIR__ . '/../../models/Movie.php';
require_once __DIR__ . '/../../models/Room.php';
require_once __DIR__ . '/../../models/AuditLog.php';

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
        $this->renderForm(null, [], null);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();

        [$data, $error] = $this->validate(null);
        if ($error) {
            $this->renderForm(null, $_POST, $error);
            return;
        }

        [$id, $conflict] = Showtime::saveWithoutOverlap($data);
        if ($conflict) {
            $this->renderForm(null, $_POST, $this->overlapMessage($conflict));
            return;
        }

        AuditLog::write('create', 'showtime', $id, null, $this->logData($data));
        $this->flash('success', 'Đã thêm suất chiếu.');
        $this->redirect('/admin/showtimes');
    }

    public function editForm(string $id): void
    {
        Auth::requireAdmin();
        $showtime = $this->findEditable((int) $id);
        $this->renderForm($showtime, [], null);
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();
        $showtime = $this->findEditable((int) $id);

        [$data, $error] = $this->validate($showtime);
        if (!$error && Showtime::soldCount((int) $id) > 0 && $this->scheduleChanged($showtime, $data)) {
            $error = 'Suất chiếu đã bán vé: chỉ được sửa giá cơ bản, không đổi phim / phòng / giờ chiếu.';
        }
        if ($error) {
            $this->renderForm($showtime, $_POST, $error);
            return;
        }

        [, $conflict] = Showtime::saveWithoutOverlap($data, (int) $id);
        if ($conflict) {
            $this->renderForm($showtime, $_POST, $this->overlapMessage($conflict));
            return;
        }

        AuditLog::write('update', 'showtime', $id, $this->logData($showtime), $this->logData($data));
        $this->flash('success', 'Đã cập nhật suất chiếu.');
        $this->redirect('/admin/showtimes');
    }

    /** Hủy suất (không xóa cứng - BR09). Chỉ hủy được suất chưa có vé (UC12). */
    public function cancel(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();

        $showtime = Showtime::find((int) $id);
        if (!$showtime) {
            $this->notFound();
            return;
        }

        if (Showtime::cancelIfNoTickets((int) $id)) {
            AuditLog::write('cancel', 'showtime', $id, ['status' => 'active'], ['status' => 'cancelled']);
            $this->flash('success', 'Đã hủy suất chiếu.');
        } else {
            $this->flash('error', $showtime['status'] === 'cancelled'
                ? 'Suất chiếu đã được hủy trước đó.'
                : 'Chỉ hủy được suất chiếu chưa có vé.');
        }
        $this->redirect('/admin/showtimes');
    }

    private function findEditable(int $id): array
    {
        $showtime = Showtime::find($id);
        if (!$showtime) {
            $this->notFound();
        }
        if ($showtime['status'] === 'cancelled') {
            $this->flash('error', 'Suất chiếu đã hủy, không sửa được.');
            $this->redirect('/admin/showtimes');
        }
        return $showtime;
    }

    private function renderForm(?array $showtime, array $old, ?string $error): void
    {
        // Danh sách chọn: phim Đang chiếu / Sắp chiếu, phòng đang hoạt động (+ giá trị hiện tại khi sửa)
        $movies = Movie::schedulable();
        $rooms = Room::activeWithCinema();
        if ($showtime) {
            if (!in_array((int) $showtime['movie_id'], array_map('intval', array_column($movies, 'id')), true)) {
                $movies[] = Movie::find((int) $showtime['movie_id']);
            }
            if (!in_array((int) $showtime['room_id'], array_map('intval', array_column($rooms, 'id')), true)) {
                $rooms[] = Room::find((int) $showtime['room_id']);
            }
        }

        $this->adminView('admin/showtimes/form', [
            'pageTitle' => $showtime ? 'Sửa suất chiếu' : 'Thêm suất chiếu',
            'showtime'  => $showtime,
            'old'       => $old,
            'error'     => $error,
            'soldCount' => $showtime ? Showtime::soldCount((int) $showtime['id']) : 0,
            'movies'    => $movies,
            'rooms'     => $rooms,
        ]);
    }

    /**
     * Kiểm tra dữ liệu suất chiếu ở server (UC12). Giờ kết thúc chiếm phòng
     * = giờ bắt đầu + thời lượng phim + 15 phút dọn phòng (BR02).
     *
     * @return array{0: array, 1: ?string}
     */
    private function validate(?array $existing): array
    {
        $movieId = (int) $this->input('movie_id');
        $roomId = (int) $this->input('room_id');
        $showDate = (string) $this->input('show_date');
        $startTime = substr((string) $this->input('start_time'), 0, 5);
        $basePriceRaw = (string) $this->input('base_price');

        $movie = $movieId ? Movie::find($movieId) : null;
        $movieUnchanged = $existing && $movieId === (int) $existing['movie_id'];
        if (!$movie || (!$movieUnchanged && !in_array($movie['status'], ['now_showing', 'coming_soon'], true))) {
            return [[], 'Vui lòng chọn phim Đang chiếu hoặc Sắp chiếu.'];
        }

        $roomUnchanged = $existing && $roomId === (int) $existing['room_id'];
        $activeRoomIds = array_map('intval', array_column(Room::activeWithCinema(), 'id'));
        if (!$roomId || (!$roomUnchanged && !in_array($roomId, $activeRoomIds, true)) || !Room::find($roomId)) {
            return [[], 'Vui lòng chọn phòng chiếu đang hoạt động.'];
        }

        $start = DateTime::createFromFormat('!Y-m-d H:i', "$showDate $startTime");
        if (!$start || $start->format('Y-m-d H:i') !== "$showDate $startTime") {
            return [[], 'Ngày hoặc giờ chiếu không hợp lệ.'];
        }
        $startTimestamp = $start->getTimestamp();
        $startUnchanged = $existing && $startTimestamp === Showtime::startTimestamp($existing);
        if (!$startUnchanged && $startTimestamp <= time()) {
            return [[], 'Giờ bắt đầu phải ở tương lai.'];
        }

        if (!ctype_digit($basePriceRaw) || (int) $basePriceRaw <= 0 || (int) $basePriceRaw > 10000000) {
            return [[], 'Giá cơ bản phải là số nguyên lớn hơn 0.'];
        }

        $endTimestamp = $startTimestamp + ((int) $movie['duration_minutes'] + CLEANUP_MINUTES) * 60;

        return [[
            'movie_id'   => $movieId,
            'room_id'    => $roomId,
            'show_date'  => $showDate,
            'start_time' => date('H:i:s', $startTimestamp),
            'end_time'   => date('H:i:s', $endTimestamp),
            'base_price' => (int) $basePriceRaw,
            'starts_at'  => date('Y-m-d H:i:s', $startTimestamp),
            'ends_at'    => date('Y-m-d H:i:s', $endTimestamp),
        ], null];
    }

    private function scheduleChanged(array $existing, array $data): bool
    {
        return (int) $existing['movie_id'] !== $data['movie_id']
            || (int) $existing['room_id'] !== $data['room_id']
            || Showtime::startTimestamp($existing) !== strtotime($data['starts_at']);
    }

    private function overlapMessage(array $conflict): string
    {
        return sprintf(
            'Phòng đã có suất trùng thời gian: "%s" %s – %s (đã gồm %d phút dọn phòng).',
            $conflict['movie_title'],
            date('d/m/Y H:i', strtotime($conflict['starts_at'])),
            date('H:i', strtotime($conflict['ends_at'])),
            CLEANUP_MINUTES
        );
    }

    private function logData(array $showtime): array
    {
        return [
            'movie_id'   => (int) $showtime['movie_id'],
            'room_id'    => (int) $showtime['room_id'],
            'show_date'  => $showtime['show_date'],
            'start_time' => $showtime['start_time'],
            'end_time'   => $showtime['end_time'],
            'base_price' => (int) $showtime['base_price'],
        ];
    }
}
