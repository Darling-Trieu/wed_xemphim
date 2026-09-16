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

    public static function delete(int $id): void
    {
        self::db()->prepare('DELETE FROM rooms WHERE id = ?')->execute([$id]);
    }
}
