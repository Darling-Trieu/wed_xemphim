<?php

require_once __DIR__ . '/../../models/Room.php';
require_once __DIR__ . '/../../models/Cinema.php';
require_once __DIR__ . '/../../models/Seat.php';
require_once __DIR__ . '/../../models/SeatType.php';
require_once __DIR__ . '/../../models/AuditLog.php';

class AdminRoomController extends Controller
{
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
        $this->adminView('admin/rooms/form', [
            'pageTitle' => 'Thêm phòng chiếu',
            'cinemas'   => Cinema::activeList(),
            'seatTypes' => SeatType::all(),
        ]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();

        $cinemaId = (int) $this->input('cinema_id');
        $name = (string) $this->input('name');
        $rows = (int) $this->input('rows', 5);
        $cols = (int) $this->input('cols', 8);
        $defaultSeatTypeId = (int) $this->input('seat_type_id');

        $error = null;
        if (!Cinema::find($cinemaId)) {
            $error = 'Vui lòng chọn rạp chiếu.';
        } elseif ($name === '' || mb_strlen($name) > 50) {
            $error = 'Tên phòng không được để trống và tối đa 50 ký tự.';
        } elseif (Room::nameExists($cinemaId, $name)) {
            $error = 'Tên phòng đã tồn tại trong rạp này.';
        } elseif ($rows < 1 || $rows > 20 || $cols < 1 || $cols > 20) {
            $error = 'Số hàng và số cột phải từ 1 đến 20.';
        } elseif (!SeatType::find($defaultSeatTypeId)) {
            $error = 'Vui lòng chọn loại ghế mặc định.';
        }
        if ($error) {
            $this->flash('error', $error);
            $this->redirect('/admin/rooms/create');
            return;
        }

        // Phòng + toàn bộ ghế được tạo trong 1 transaction
        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            $roomId = Room::create($cinemaId, $name);
            Seat::generateGrid($roomId, $rows, $cols, $defaultSeatTypeId);
            AuditLog::write('create', 'room', $roomId, null,
                ['cinema_id' => $cinemaId, 'name' => $name, 'rows' => $rows, 'cols' => $cols, 'seat_type_id' => $defaultSeatTypeId]);
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        $this->flash('success', 'Đã tạo phòng chiếu và sơ đồ ghế.');
        $this->redirect('/admin/rooms/' . $roomId . '/seats');
    }

    public function seats(string $id): void
    {
        Auth::requireAdmin();
        $room = Room::find((int) $id);
        if (!$room) {
            $this->notFound();
            return;
        }

        $this->adminView('admin/rooms/seats', [
            'pageTitle' => 'Sơ đồ ghế - ' . $room['name'],
            'room'      => $room,
            'seats'     => Seat::allByRoom((int) $id),
            'seatTypes' => SeatType::all(),
        ]);
    }

    public function updateSeats(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();

        if (!Room::find((int) $id)) {
            $this->notFound();
            return;
        }

        // Chỉ sửa ghế thuộc đúng phòng này, loại ghế phải tồn tại
        $current = [];
        foreach (Seat::allByRoom((int) $id) as $seat) {
            $current[(int) $seat['id']] = (int) $seat['seat_type_id'];
        }
        $validTypes = array_map('intval', array_column(SeatType::all(), 'id'));

        $before = [];
        $after = [];
        foreach ((array) ($_POST['seat_type'] ?? []) as $seatId => $typeId) {
            $seatId = (int) $seatId;
            $typeId = (int) $typeId;
            if (!isset($current[$seatId]) || !in_array($typeId, $validTypes, true) || $current[$seatId] === $typeId) {
                continue;
            }
            Seat::updateType($seatId, $typeId);
            $before[$seatId] = $current[$seatId];
            $after[$seatId] = $typeId;
        }
        if ($after) {
            AuditLog::write('update_seats', 'room', $id, ['seat_type' => $before], ['seat_type' => $after]);
        }

        $this->flash('success', 'Đã cập nhật sơ đồ ghế.');
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

        $room = Room::find((int) $id);
        if (!$room) {
            $this->notFound();
            return;
        }

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
}
