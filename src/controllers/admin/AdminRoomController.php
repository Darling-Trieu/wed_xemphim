<?php

require_once __DIR__ . '/../../models/Room.php';
require_once __DIR__ . '/../../models/Cinema.php';
require_once __DIR__ . '/../../models/Seat.php';
require_once __DIR__ . '/../../models/SeatType.php';
require_once __DIR__ . '/../../models/AuditLog.php';

/** Phòng chiếu và sơ đồ ghế (FR09, UC11). */
class AdminRoomController extends Controller
{
    private const MAX_GRID = 20;

    public function index(): void
    {
        Auth::requireAdmin();
        $this->adminView('admin/rooms/index', [
            'pageTitle' => 'Quản lý phòng chiếu',
            'rooms'     => Room::allWithCinema(),
        ]);
    }

    public function createForm(): void
    {
        Auth::requireAdmin();
        $this->renderForm(null);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();

        $cinemaId = (int) $this->input('cinema_id');
        $rows = (int) $this->input('rows', 5);
        $cols = (int) $this->input('cols', 8);
        $defaultSeatTypeId = (int) $this->input('seat_type_id');
        [$name, $roomType, , $error] = $this->validatedRoomInput($cinemaId, null);

        if (!$error) {
            if (!Cinema::find($cinemaId)) {
                $error = 'Vui lòng chọn rạp chiếu.';
            } elseif ($rows < 1 || $rows > self::MAX_GRID || $cols < 1 || $cols > self::MAX_GRID) {
                $error = 'Số hàng và số cột phải từ 1 đến ' . self::MAX_GRID . '.';
            } elseif (!SeatType::find($defaultSeatTypeId)) {
                $error = 'Vui lòng chọn loại ghế mặc định.';
            }
        }
        if ($error) {
            $this->flash('error', $error);
            $this->redirect('/admin/rooms/create');
            return;
        }

        // Phòng + toàn bộ ghế được tạo trong 1 transaction
        $roomId = $this->transaction(function () use ($cinemaId, $name, $roomType, $rows, $cols, $defaultSeatTypeId): int {
            $roomId = Room::create($cinemaId, $name, $roomType);
            Seat::generateGrid($roomId, $rows, $cols, $defaultSeatTypeId);
            AuditLog::write('create', 'room', $roomId, null, [
                'cinema_id' => $cinemaId, 'name' => $name, 'room_type' => $roomType,
                'rows' => $rows, 'cols' => $cols, 'seat_type_id' => $defaultSeatTypeId,
            ]);
            return $roomId;
        });

        $this->flash('success', 'Đã tạo phòng chiếu. Đánh dấu lối đi / loại ghế ở sơ đồ bên dưới rồi lưu lại.');
        $this->redirect('/admin/rooms/' . $roomId . '/seats');
    }

    public function editForm(string $id): void
    {
        Auth::requireAdmin();
        $this->renderForm($this->findRoom((int) $id));
    }

    /** Sửa tên / loại / trạng thái phòng. Sơ đồ ghế sửa ở trang "Sơ đồ ghế". */
    public function update(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();
        $room = $this->findRoom((int) $id);

        [$name, $roomType, $status, $error] = $this->validatedRoomInput((int) $room['cinema_id'], (int) $id);
        if ($error) {
            $this->flash('error', $error);
            $this->redirect('/admin/rooms/' . (int) $id . '/edit');
            return;
        }

        Room::update((int) $id, $name, $roomType, $status);
        AuditLog::write('update', 'room', $id,
            ['name' => $room['name'], 'room_type' => $room['room_type'], 'status' => $room['status']],
            ['name' => $name, 'room_type' => $roomType, 'status' => $status]);

        $this->flash('success', 'Đã cập nhật phòng chiếu.');
        $this->redirect('/admin/rooms');
    }

    public function seats(string $id): void
    {
        Auth::requireAdmin();
        $room = $this->findRoom((int) $id);

        $this->adminView('admin/rooms/seats', [
            'pageTitle'     => 'Sơ đồ ghế - ' . $room['name'],
            'room'          => $room,
            'seats'         => Seat::allByRoom((int) $id),
            'seatTypes'     => SeatType::all(),
            'ticketSeatIds' => Seat::idsWithTickets((int) $id),
        ]);
    }

    /**
     * Lưu sơ đồ ghế: mỗi ô nhận "t:<loại ghế>" (ghế hoạt động), "inactive" (Ngừng sử dụng)
     * hoặc "aisle" (lối đi). Ghế đã có vé không được biến thành lối đi (BR09).
     */
    public function updateSeats(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();
        $this->findRoom((int) $id);

        $current = array_column(Seat::allByRoom((int) $id), null, 'id');
        $validTypes = array_map('intval', array_column(SeatType::all(), 'id'));
        $ticketSeatIds = Seat::idsWithTickets((int) $id);

        $changes = [];
        $blocked = [];
        foreach ((array) ($_POST['seat'] ?? []) as $seatId => $value) {
            $seat = $current[(int) $seatId] ?? null;
            if (!$seat || !is_string($value)) {
                continue;
            }
            [$typeId, $status] = $this->parseSeatValue($value, (int) $seat['seat_type_id']);
            if (!in_array($typeId, $validTypes, true) || !in_array($status, Seat::STATUSES, true)) {
                continue;
            }
            if ($typeId === (int) $seat['seat_type_id'] && $status === $seat['status']) {
                continue;
            }
            if ($status === 'aisle' && in_array((int) $seatId, $ticketSeatIds, true)) {
                $blocked[] = $seat['seat_row'] . $seat['seat_number'];
                continue;
            }
            $changes[(int) $seatId] = [
                'before' => ['type' => (int) $seat['seat_type_id'], 'status' => $seat['status']],
                'after'  => ['type' => $typeId, 'status' => $status],
            ];
        }

        if ($changes) {
            $this->transaction(function () use ($changes, $id): void {
                foreach ($changes as $seatId => $change) {
                    Seat::update($seatId, $change['after']['type'], $change['after']['status']);
                }
                AuditLog::write('update_seats', 'room', $id,
                    array_map(fn ($c) => $c['before'], $changes),
                    array_map(fn ($c) => $c['after'], $changes));
            });
        }

        $this->flash('success', 'Đã cập nhật ' . count($changes) . ' ghế.');
        if ($blocked) {
            $this->flash('error', 'Ghế ' . implode(', ', $blocked) . ' đã có vé nên không đổi thành lối đi (có thể đặt Ngừng sử dụng).');
        }
        $this->redirect('/admin/rooms/' . $id . '/seats');
    }

    /**
     * BR09: phòng đã có suất chiếu (ghế có thể đã có vé) không xóa cứng, chỉ chuyển
     * Ngừng hoạt động. Phòng chưa từng có suất thì xóa được (ghế xóa theo).
     */
    public function delete(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();
        $room = $this->findRoom((int) $id);

        if (Room::hasShowtimes((int) $id)) {
            Room::setStatus((int) $id, 'inactive');
            AuditLog::write('deactivate', 'room', $id, ['status' => $room['status']], ['status' => 'inactive']);
            $this->flash('success', 'Phòng đã có suất chiếu nên không xóa, đã chuyển sang Ngừng hoạt động.');
        } else {
            Room::delete((int) $id);
            AuditLog::write('delete', 'room', $id, ['cinema_id' => (int) $room['cinema_id'], 'name' => $room['name']], null);
            $this->flash('success', 'Đã xóa phòng chiếu.');
        }
        $this->redirect('/admin/rooms');
    }

    private function findRoom(int $id): array
    {
        $room = Room::find($id);
        if (!$room) {
            $this->notFound();
        }
        return $room;
    }

    private function renderForm(?array $room): void
    {
        $this->adminView('admin/rooms/form', [
            'pageTitle' => $room ? 'Sửa phòng chiếu' : 'Thêm phòng chiếu',
            'room'      => $room,
            'cinemas'   => Cinema::activeList(),
            'seatTypes' => SeatType::all(),
        ]);
    }

    /** @return array{0: string, 1: string, 2: string, 3: ?string} [tên, loại phòng, trạng thái, lỗi] */
    private function validatedRoomInput(int $cinemaId, ?int $excludeId): array
    {
        $name = (string) $this->input('name', '');
        $roomType = (string) $this->input('room_type', 'standard');
        $status = (string) $this->input('status', 'active');

        $error = null;
        if (!$this->validLength($name, 50)) {
            $error = 'Tên phòng không được để trống và tối đa 50 ký tự.';
        } elseif (!isset(Room::TYPES[$roomType])) {
            $error = 'Loại phòng không hợp lệ.';
        } elseif (!isset(Room::STATUSES[$status])) {
            $error = 'Trạng thái phòng không hợp lệ.';
        } elseif ($cinemaId && Room::nameExists($cinemaId, $name, $excludeId)) {
            $error = 'Tên phòng đã tồn tại trong rạp này.';
        }
        return [$name, $roomType, $status, $error];
    }

    /** @return array{0: int, 1: string} [seat_type_id, status] */
    private function parseSeatValue(string $value, int $currentTypeId): array
    {
        if (str_starts_with($value, 't:')) {
            return [(int) substr($value, 2), 'active'];
        }
        return [$currentTypeId, $value];
    }
}
