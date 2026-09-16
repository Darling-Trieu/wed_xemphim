<?php

class BookingSeatConflictException extends RuntimeException
{
}

class Booking extends Model
{
    /**
     * Tạo đơn đặt vé trong 1 transaction: kiểm tra ghế còn trống rồi ghi booking_seats.
     * Ràng buộc UNIQUE(showtime_id, seat_id) ở CSDL là lớp bảo vệ cuối cùng chống
     * 2 request đặt trùng ghế cùng lúc (race condition) -> ném BookingSeatConflictException.
     *
     * @param array<int, array{seat_id:int, price:float}> $seats
     */
    public static function create(int $userId, int $showtimeId, array $seats): string
    {
        $db = self::db();
        $db->beginTransaction();

        try {
            // Kiểm tra lại ghế còn trống ngay trước khi ghi (giảm khả năng va chạm,
            // UNIQUE constraint vẫn là chốt chặn cuối cùng nếu có race condition).
            $alreadyBooked = BookingSeat::bookedSeatIds($showtimeId);
            foreach ($seats as $seat) {
                if (in_array((int) $seat['seat_id'], $alreadyBooked, true)) {
                    throw new BookingSeatConflictException('Một số ghế vừa được người khác đặt, vui lòng chọn lại.');
                }
            }

            $total = array_sum(array_column($seats, 'price'));
            $code = self::generateCode();

            $stmt = $db->prepare(
                'INSERT INTO bookings (user_id, showtime_id, booking_code, total_amount, status)
                 VALUES (?, ?, ?, ?, "paid")'
            );
            $stmt->execute([$userId, $showtimeId, $code, $total]);
            $bookingId = (int) $db->lastInsertId();

            $seatStmt = $db->prepare(
                'INSERT INTO booking_seats (booking_id, showtime_id, seat_id, price) VALUES (?, ?, ?, ?)'
            );
            foreach ($seats as $seat) {
                $seatStmt->execute([$bookingId, $showtimeId, $seat['seat_id'], $seat['price']]);
            }

            $db->commit();
            return $code;
        } catch (PDOException $e) {
            $db->rollBack();
            if ($e->getCode() === '23000') {
                throw new BookingSeatConflictException('Một số ghế vừa được người khác đặt, vui lòng chọn lại.');
            }
            throw $e;
        } catch (BookingSeatConflictException $e) {
            $db->rollBack();
            throw $e;
        }
    }

    private static function generateCode(): string
    {
        return 'VE' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }

    public static function findByCode(string $code): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT b.*, st.show_date, st.start_time, st.movie_id, m.title AS movie_title, m.poster_path,
                    c.name AS cinema_name, r.name AS room_name
             FROM bookings b
             INNER JOIN showtimes st ON st.id = b.showtime_id
             INNER JOIN movies m ON m.id = st.movie_id
             INNER JOIN rooms r ON r.id = st.room_id
             INNER JOIN cinemas c ON c.id = r.cinema_id
             WHERE b.booking_code = ?'
        );
        $stmt->execute([$code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function allByUser(int $userId): array
    {
        $stmt = self::db()->prepare(
            'SELECT b.*, st.show_date, st.start_time, m.title AS movie_title, m.poster_path,
                    c.name AS cinema_name
             FROM bookings b
             INNER JOIN showtimes st ON st.id = b.showtime_id
             INNER JOIN movies m ON m.id = st.movie_id
             INNER JOIN rooms r ON r.id = st.room_id
             INNER JOIN cinemas c ON c.id = r.cinema_id
             WHERE b.user_id = ?
             ORDER BY b.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function allWithDetails(): array
    {
        return self::db()->query(
            'SELECT b.*, u.full_name, u.email, st.show_date, st.start_time, m.title AS movie_title,
                    c.name AS cinema_name
             FROM bookings b
             INNER JOIN users u ON u.id = b.user_id
             INNER JOIN showtimes st ON st.id = b.showtime_id
             INNER JOIN movies m ON m.id = st.movie_id
             INNER JOIN rooms r ON r.id = st.room_id
             INNER JOIN cinemas c ON c.id = r.cinema_id
             ORDER BY b.created_at DESC'
        )->fetchAll();
    }

    public static function cancel(int $id): void
    {
        self::db()->prepare('UPDATE bookings SET status = "cancelled" WHERE id = ?')->execute([$id]);
    }

    public static function countPaid(): int
    {
        return (int) self::db()->query("SELECT COUNT(*) FROM bookings WHERE status = 'paid'")->fetchColumn();
    }

    public static function totalRevenue(): float
    {
        return (float) self::db()->query("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE status = 'paid'")->fetchColumn();
    }

    /** Doanh thu theo từng ngày trong N ngày gần nhất, dùng cho biểu đồ admin. */
    public static function revenueSeries(int $days = 14): array
    {
        $stmt = self::db()->prepare(
            "SELECT DATE(created_at) AS day, COALESCE(SUM(total_amount),0) AS revenue
             FROM bookings
             WHERE status = 'paid' AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(created_at)
             ORDER BY day"
        );
        $stmt->execute([$days]);
        $rows = $stmt->fetchAll();

        $byDay = [];
        foreach ($rows as $row) {
            $byDay[$row['day']] = (float) $row['revenue'];
        }

        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $series[] = ['day' => $day, 'revenue' => $byDay[$day] ?? 0];
        }

        return $series;
    }
}
