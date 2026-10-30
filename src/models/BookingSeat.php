<?php

class BookingSeat extends Model
{
    /** Danh sách seat_id đã bán cho 1 suất chiếu (bảng ghế-theo-suất). */
    public static function bookedSeatIds(int $showtimeId): array
    {
        $stmt = self::db()->prepare('SELECT seat_id FROM showtime_seats WHERE showtime_id = ?');
        $stmt->execute([$showtimeId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /** Vé của 1 đơn, đọc từ snapshot (nhãn ghế, loại ghế, giá lúc đặt) - BR06. */
    public static function allByBooking(int $bookingId): array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM booking_seats WHERE booking_id = ?
             ORDER BY LEFT(seat_label, 1), CAST(SUBSTRING(seat_label, 2) AS UNSIGNED)'
        );
        $stmt->execute([$bookingId]);
        return $stmt->fetchAll();
    }
}
