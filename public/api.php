<?php

require dirname(__DIR__) . '/src/config/config.php';
require dirname(__DIR__) . '/src/core/Database.php';
require dirname(__DIR__) . '/src/core/Model.php';
require dirname(__DIR__) . '/src/core/Auth.php';
require dirname(__DIR__) . '/src/core/Csrf.php';
require dirname(__DIR__) . '/src/core/ErrorHandler.php';
require dirname(__DIR__) . '/src/core/helpers.php';
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

/** API thay đổi dữ liệu: chỉ nhận POST có CSRF token của session. */
function requirePostWithCsrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        apiError('Phương thức không hợp lệ.', 405);
    }
    $token = $_POST['csrf_token'] ?? null;
    if (!Csrf::verify(is_string($token) ? $token : null)) {
        apiError('Phiên làm việc đã hết hạn, vui lòng tải lại trang.', 419);
    }
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
        requirePostWithCsrf();
        $showtimeId = (int) ($_POST['showtime_id'] ?? 0);
        $seatId = (int) ($_POST['seat_id'] ?? 0);
        $showtime = $showtimeId ? Showtime::find($showtimeId) : null;
        if (!$showtime || $seatId <= 0) {
            apiError('Dữ liệu giữ ghế không hợp lệ.');
        }
        if (Showtime::isSaleClosed($showtime)) {
            apiError('Suất chiếu đã đóng bán.');
        }
        $seat = Seat::find($seatId);
        if (!$seat || (int) $seat['room_id'] !== (int) $showtime['room_id'] || $seat['status'] !== 'active') {
            apiError('Ghế không thuộc phòng chiếu của suất này.');
        }
        if (in_array($seatId, BookingSeat::bookedSeatIds($showtimeId), true)) {
            echo json_encode(['held' => false, 'error' => 'Ghế đã có người đặt.'], JSON_UNESCAPED_UNICODE);
            break;
        }
        try {
            $expiresAt = SeatHold::hold($showtimeId, $seatId, $sessionId);
        } catch (BookingException $e) {
            echo json_encode(['held' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            break;
        }
        echo json_encode([
            'held'       => $expiresAt !== null,
            'expires_at' => $expiresAt,
            'error'      => $expiresAt === null ? 'Ghế vừa được người khác giữ, vui lòng chọn ghế khác.' : null,
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'release_seat':
        requirePostWithCsrf();
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
