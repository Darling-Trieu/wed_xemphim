<?php

class Showtime extends Model
{
    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT st.*, m.title AS movie_title, m.duration_minutes, m.poster_path,
                    r.name AS room_name, c.id AS cinema_id, c.name AS cinema_name, c.address AS cinema_address
             FROM showtimes st
             INNER JOIN movies m ON m.id = st.movie_id
             INNER JOIN rooms r ON r.id = st.room_id
             INNER JOIN cinemas c ON c.id = r.cinema_id
             WHERE st.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Tra cứu suất chiếu còn hoạt động theo phim / rạp / ngày (mỗi tham số optional).
     */
    public static function search(?int $movieId, ?int $cinemaId, ?string $date): array
    {
        $sql = 'SELECT st.*, m.title AS movie_title, c.name AS cinema_name, r.name AS room_name
                FROM showtimes st
                INNER JOIN movies m ON m.id = st.movie_id
                INNER JOIN rooms r ON r.id = st.room_id
                INNER JOIN cinemas c ON c.id = r.cinema_id
                WHERE st.status = "active" AND st.show_date >= CURDATE()';
        $params = [];

        if ($movieId) {
            $sql .= ' AND st.movie_id = ?';
            $params[] = $movieId;
        }
        if ($cinemaId) {
            $sql .= ' AND c.id = ?';
            $params[] = $cinemaId;
        }
        if ($date) {
            $sql .= ' AND st.show_date = ?';
            $params[] = $date;
        }

        $sql .= ' ORDER BY st.show_date, st.start_time';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function allWithDetails(): array
    {
        return self::db()->query(
            'SELECT st.*, m.title AS movie_title, c.name AS cinema_name, r.name AS room_name
             FROM showtimes st
             INNER JOIN movies m ON m.id = st.movie_id
             INNER JOIN rooms r ON r.id = st.room_id
             INNER JOIN cinemas c ON c.id = r.cinema_id
             ORDER BY st.show_date DESC, st.start_time DESC'
        )->fetchAll();
    }

    public static function overlapExists(int $roomId, string $date, string $start, string $end, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM showtimes
                WHERE room_id = ? AND show_date = ? AND status = "active"
                AND NOT (end_time <= ? OR start_time >= ?)';
        $params = [$roomId, $date, $start, $end];

        if ($excludeId) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return ((int) $stmt->fetchColumn()) > 0;
    }

    public static function create(array $data): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO showtimes (movie_id, room_id, show_date, start_time, end_time, base_price, status)
             VALUES (?, ?, ?, ?, ?, ?, "active")'
        );
        $stmt->execute([
            $data['movie_id'], $data['room_id'], $data['show_date'],
            $data['start_time'], $data['end_time'], $data['base_price'],
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = self::db()->prepare(
            'UPDATE showtimes SET movie_id = ?, room_id = ?, show_date = ?, start_time = ?, end_time = ?, base_price = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $data['movie_id'], $data['room_id'], $data['show_date'],
            $data['start_time'], $data['end_time'], $data['base_price'], $id,
        ]);
    }

    public static function delete(int $id): void
    {
        self::db()->prepare('DELETE FROM showtimes WHERE id = ?')->execute([$id]);
    }

    public static function count(): int
    {
        return (int) self::db()->query('SELECT COUNT(*) FROM showtimes')->fetchColumn();
    }
}
