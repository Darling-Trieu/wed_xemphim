<?php

class Room extends Model
{
    public static function allByCinema(int $cinemaId): array
    {
        $stmt = self::db()->prepare('SELECT * FROM rooms WHERE cinema_id = ? ORDER BY name');
        $stmt->execute([$cinemaId]);
        return $stmt->fetchAll();
    }

    public static function allWithCinema(): array
    {
        return self::db()->query(
            'SELECT r.*, c.name AS cinema_name FROM rooms r
             INNER JOIN cinemas c ON c.id = r.cinema_id
             ORDER BY c.name, r.name'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT r.*, c.name AS cinema_name FROM rooms r
             INNER JOIN cinemas c ON c.id = r.cinema_id
             WHERE r.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(int $cinemaId, string $name): int
    {
        $stmt = self::db()->prepare('INSERT INTO rooms (cinema_id, name, status) VALUES (?, ?, "active")');
        $stmt->execute([$cinemaId, $name]);
        return (int) self::db()->lastInsertId();
    }

    /** Phòng xếp suất chiếu được (UC12): phòng và rạp đều đang hoạt động. */
    public static function activeWithCinema(): array
    {
        return self::db()->query(
            "SELECT r.*, c.name AS cinema_name FROM rooms r
             INNER JOIN cinemas c ON c.id = r.cinema_id
             WHERE r.status = 'active' AND c.status = 'active'
             ORDER BY c.name, r.name"
        )->fetchAll();
    }

    public static function nameExists(int $cinemaId, string $name, ?int $excludeId = null): bool
    {
        $stmt = self::db()->prepare('SELECT EXISTS(SELECT 1 FROM rooms WHERE cinema_id = ? AND name = ? AND id != ?)');
        $stmt->execute([$cinemaId, $name, $excludeId ?? 0]);
        return (bool) $stmt->fetchColumn();
    }

    public static function hasShowtimes(int $id): bool
    {
        $stmt = self::db()->prepare('SELECT EXISTS(SELECT 1 FROM showtimes WHERE room_id = ?)');
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }

    public static function setStatus(int $id, string $status): void
    {
        self::db()->prepare('UPDATE rooms SET status = ? WHERE id = ?')->execute([$status, $id]);
    }

    /** Chỉ dùng cho phòng chưa từng có suất chiếu (BR09); ghế bị xóa theo (ON DELETE CASCADE). */
    public static function delete(int $id): void
    {
        self::db()->prepare('DELETE FROM rooms WHERE id = ?')->execute([$id]);
    }
}
