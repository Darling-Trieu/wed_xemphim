<?php

require_once __DIR__ . '/../../models/Room.php';
require_once __DIR__ . '/../../models/Cinema.php';
require_once __DIR__ . '/../../models/Seat.php';
require_once __DIR__ . '/../../models/SeatType.php';

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
        $rows = max(1, min(26, (int) $this->input('rows', 5)));
        $cols = max(1, min(30, (int) $this->input('cols', 8)));
        $defaultSeatTypeId = (int) $this->input('seat_type_id');

        $roomId = Room::create($cinemaId, $name);
        Seat::generateGrid($roomId, $rows, $cols, $defaultSeatTypeId);

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

        $seatTypes = $_POST['seat_type'] ?? [];
        foreach ($seatTypes as $seatId => $typeId) {
            Seat::updateType((int) $seatId, (int) $typeId);
        }

        $this->flash('success', 'Đã cập nhật sơ đồ ghế.');
        $this->redirect('/admin/rooms/' . $id . '/seats');
    }

    public function delete(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();
        Seat::deleteAllByRoom((int) $id);
        Room::delete((int) $id);
        $this->flash('success', 'Đã xóa phòng chiếu.');
        $this->redirect('/admin/rooms');
    }
}
