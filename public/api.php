<?php

require dirname(__DIR__) . '/src/config/config.php';
require dirname(__DIR__) . '/src/core/Database.php';
require dirname(__DIR__) . '/src/core/Model.php';
require dirname(__DIR__) . '/src/core/Auth.php';
require dirname(__DIR__) . '/src/core/ErrorHandler.php';
require_once dirname(__DIR__) . '/src/models/Showtime.php';
require_once dirname(__DIR__) . '/src/models/Seat.php';
require_once dirname(__DIR__) . '/src/models/BookingSeat.php';
require_once dirname(__DIR__) . '/src/models/Booking.php';
require_once dirname(__DIR__) . '/src/models/SeatHold.php';

ErrorHandler::register(true);
Auth::syncWithDatabase();

header('Content-Type: application/json; charset=utf-8');

function apiError(string $message, int $status = 400): void
{
    http_response_code($status);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? '';
$sessionId = session_id();

switch ($action) {
    case 'seats':
        $showtimeId = (int) ($_GET['showtime_id'] ?? 0);
        $showtime = $showtimeId ? Showtime::find($showtimeId) : null;
        if (!$showtime) {
            apiError('Không tìm thấy suất chiếu.', 404);
        }

        $seats = Seat::allByRoom((int) $showtime['room_id']);
        $bookedIds = BookingSeat::bookedSeatIds($showtimeId);
        $heldSeats = SeatHold::activeByShowtime($showtimeId, $sessionId);

        // Giá chỉ để hiển thị tạm tính; khi đặt vé server tính lại (BR05)
        $result = array_map(function ($seat) use ($bookedIds, $heldSeats, $showtime) {
            $seatId = (int) $seat['id'];
            return [
                'id'         => $seatId,
                'row'        => $seat['seat_row'],
                'number'     => (int) $seat['seat_number'],
                'type'       => $seat['seat_type_name'],
                'price'      => (int) $showtime['base_price'] + (int) $seat['surcharge'],
                'is_active'  => $seat['status'] === 'active',
                'is_aisle'   => $seat['status'] === 'aisle',
                'is_booked'  => in_array($seatId, $bookedIds, true),
                'is_held'    => array_key_exists($seatId, $heldSeats),
                'held_by_me' => $heldSeats[$seatId]['held_by_me'] ?? false,
                'hold_expires_at' => $heldSeats[$seatId]['expires_at'] ?? null,
            ];
        }, $seats);

        echo json_encode([
            'showtime' => [
                'id'          => (int) $showtime['id'],
                'movie'       => $showtime['movie_title'],
                'cinema'      => $showtime['cinema_name'],
                'room'        => $showtime['room_name'],
                'show_date'   => $showtime['show_date'],
                'start_time'  => substr($showtime['start_time'], 0, 5),
                'sale_closed' => Showtime::isSaleClosed($showtime),
            ],
            'seats' => $result,
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'hold_seat':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            apiError('Phương thức không hợp lệ.', 405);
        }
        $showtimeId = (int) ($_POST['showtime_id'] ?? 0);
        $seatId = (int) ($_POST['seat_id'] ?? 0);
        $showtime = $showtimeId ? Showtime::find($showtimeId) : null;
        if (!$showtime || $seatId <= 0) {
            apiError('Dữ liệu giữ ghế không hợp lệ.');
        }
        if (in_array($seatId, BookingSeat::bookedSeatIds($showtimeId), true)) {
            echo json_encode(['held' => false], JSON_UNESCAPED_UNICODE);
            break;
        }
        $seat = Seat::find($seatId);
        if (!$seat || (int) $seat['room_id'] !== (int) $showtime['room_id'] || $seat['status'] !== 'active') {
            apiError('Ghế không thuộc phòng chiếu của suất này.');
        }
        $held = SeatHold::hold($showtimeId, $seatId, $sessionId);
        echo json_encode([
            'held' => $held !== null,
            'expires_at' => $held,
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'release_seat':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            apiError('Phương thức không hợp lệ.', 405);
        }
        SeatHold::release(
            (int) ($_POST['showtime_id'] ?? 0),
            (int) ($_POST['seat_id'] ?? 0),
            $sessionId
        );
        echo json_encode(['released' => true], JSON_UNESCAPED_UNICODE);
        break;

    case 'revenue_chart':
        if (!Auth::isAdmin()) {
            apiError('Không có quyền truy cập.', 403);
        }
        $days = max(7, min(60, (int) ($_GET['days'] ?? 14)));
        echo json_encode(Booking::revenueSeries($days), JSON_UNESCAPED_UNICODE);
        break;

    default:
        apiError('Hành động không hợp lệ.', 404);
}
