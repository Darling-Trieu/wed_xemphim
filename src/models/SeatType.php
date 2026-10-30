<?php

class SeatType extends Model
{
    public static function all(): array
    {
        return self::db()->query('SELECT * FROM seat_types ORDER BY surcharge, id')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM seat_types WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
