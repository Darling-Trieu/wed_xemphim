<?php

/**
 * Thống kê (FR12, UC15): chỉ tính đơn Đã xác nhận (status 'paid'), doanh thu cộng theo
 * giá lưu trong từng vé (booking_seats.price - snapshot). Ngày = ngày đặt vé.
 */
class Report extends Model
{
    /**
     * @param array{from: string, to: string, movie_id: ?int, cinema_id: ?int, room_id: ?int} $filters
     * @return array{tickets: int, revenue: int, bookings: int}
     */
    public static function summary(array $filters): array
    {
        [$where, $params] = self::where($filters);
        $stmt = self::db()->prepare(
            "SELECT COUNT(bs.id) AS tickets, COALESCE(SUM(bs.price), 0) AS revenue, COUNT(DISTINCT b.id) AS bookings
             FROM bookings b
             INNER JOIN booking_seats bs ON bs.booking_id = b.id
             INNER JOIN showtimes st ON st.id = b.showtime_id
             INNER JOIN rooms r ON r.id = st.room_id
             WHERE $where"
        );
        $stmt->execute($params);
        $row = $stmt->fetch();
        return ['tickets' => (int) $row['tickets'], 'revenue' => (int) $row['revenue'], 'bookings' => (int) $row['bookings']];
    }

    /** @return array<int, array{day: string, tickets: int, revenue: int}> mỗi ngày trong khoảng (kể cả ngày không bán) */
    public static function byDay(array $filters): array
    {
        [$where, $params] = self::where($filters);
        $stmt = self::db()->prepare(
            "SELECT DATE(b.created_at) AS day, COUNT(bs.id) AS tickets, SUM(bs.price) AS revenue
             FROM bookings b
             INNER JOIN booking_seats bs ON bs.booking_id = b.id
             INNER JOIN showtimes st ON st.id = b.showtime_id
             INNER JOIN rooms r ON r.id = st.room_id
             WHERE $where
             GROUP BY DATE(b.created_at)"
        );
        $stmt->execute($params);
        $found = array_column($stmt->fetchAll(), null, 'day');

        $days = [];
        for ($ts = strtotime($filters['from']); $ts <= strtotime($filters['to']); $ts = strtotime('+1 day', $ts)) {
            $day = date('Y-m-d', $ts);
            $days[] = [
                'day'     => $day,
                'tickets' => (int) ($found[$day]['tickets'] ?? 0),
                'revenue' => (int) ($found[$day]['revenue'] ?? 0),
            ];
        }
        return $days;
    }

    /** @return array<int, array{movie_title: string, tickets: int, revenue: int}> doanh thu giảm dần */
    public static function byMovie(array $filters): array
    {
        [$where, $params] = self::where($filters);
        $stmt = self::db()->prepare(
            "SELECT MAX(b.movie_title) AS movie_title, COUNT(bs.id) AS tickets, SUM(bs.price) AS revenue
             FROM bookings b
             INNER JOIN booking_seats bs ON bs.booking_id = b.id
             INNER JOIN showtimes st ON st.id = b.showtime_id
             INNER JOIN rooms r ON r.id = st.room_id
             WHERE $where
             GROUP BY st.movie_id
             ORDER BY revenue DESC"
        );
        $stmt->execute($params);
        return array_map(static fn ($row) => [
            'movie_title' => $row['movie_title'],
            'tickets'     => (int) $row['tickets'],
            'revenue'     => (int) $row['revenue'],
        ], $stmt->fetchAll());
    }

    /** @return array{0: string, 1: array} điều kiện WHERE dùng chung + tham số */
    private static function where(array $filters): array
    {
        $where = "b.status = 'paid' AND b.created_at >= ? AND b.created_at < ? + INTERVAL 1 DAY";
        $params = [$filters['from'], $filters['to']];
        foreach (['movie_id' => 'st.movie_id', 'cinema_id' => 'r.cinema_id', 'room_id' => 'st.room_id'] as $key => $column) {
            if (!empty($filters[$key])) {
                $where .= " AND $column = ?";
                $params[] = (int) $filters[$key];
            }
        }
        return [$where, $params];
    }
}
