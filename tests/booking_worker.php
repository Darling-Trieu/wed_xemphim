<?php
/**
 * Tiến trình con cho ca kiểm thử đặt vé đồng thời (tests/run_tests.php).
 * php tests/booking_worker.php <userId> <showtimeId> <seatId> <startAtUnixFloat>
 * Chờ đến đúng thời điểm startAt rồi gọi Booking::create, in kết quả dạng JSON.
 */

[$_, $userId, $showtimeId, $seatId, $startAt] = $argv;

putenv('DB_NAME=' . (getenv('DB_NAME') ?: 'movie_booking_test'));
$root = dirname(__DIR__);
require $root . '/src/config/config.php';
require $root . '/src/core/Database.php';
require $root . '/src/core/Model.php';
require $root . '/src/core/Auth.php';
require $root . '/src/models/Booking.php';

Database::getConnection(); // mở kết nối trước để các tiến trình xuất phát cùng lúc

while (microtime(true) < (float) $startAt) {
    // chờ bận cho chính xác tới mili-giây
}

try {
    $code = Booking::create((int) $userId, (int) $showtimeId, [(int) $seatId], true);
    echo json_encode(['ok' => true, 'code' => $code]);
} catch (BookingException $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'fatal' => get_class($e) . ': ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
