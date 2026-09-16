<?php

class BookingSeat extends Model
{
    /** Danh sách seat_id đã được đặt (còn hiệu lực) cho 1 suất chiếu. */
    public static function bookedSeatIds(int $showtimeId): array
    {
        $stmt = self::db()->prepare(
            'SELECT bs.seat_id FROM booking_seats bs
             INNER JOIN bookings b ON b.id = bs.booking_id
             WHERE bs.showtime_id = ? AND b.status = "paid"'
        );
        $stmt->execute([$showtimeId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public static function allByBooking(int $bookingId): array
    {
        $stmt = self::db()->prepare(
            'SELECT bs.*, s.seat_row, s.seat_number, st.name AS seat_type_name
             FROM booking_seats bs
             INNER JOIN seats s ON s.id = bs.seat_id
             INNER JOIN seat_types st ON st.id = s.seat_type_id
             WHERE bs.booking_id = ?
             ORDER BY s.seat_row, s.seat_number'
        );
        $stmt->execute([$bookingId]);
        return $stmt->fetchAll();
    }
}
