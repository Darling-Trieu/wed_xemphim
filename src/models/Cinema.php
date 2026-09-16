<?php

class Cinema extends Model
{
    public static function all(): array
    {
        return self::db()->query('SELECT * FROM cinemas ORDER BY city, name')->fetchAll();
    }

    public static function activeList(): array
    {
        return self::db()->query("SELECT * FROM cinemas WHERE status = 'active' ORDER BY city, name")->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM cinemas WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO cinemas (name, address, city, phone, status) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$data['name'], $data['address'], $data['city'], $data['phone'], $data['status']]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = self::db()->prepare(
            'UPDATE cinemas SET name = ?, address = ?, city = ?, phone = ?, status = ? WHERE id = ?'
        );
        $stmt->execute([$data['name'], $data['address'], $data['city'], $data['phone'], $data['status'], $id]);
    }

    public static function delete(int $id): void
    {
        self::db()->prepare('DELETE FROM cinemas WHERE id = ?')->execute([$id]);
    }

    public static function count(): int
    {
        return (int) self::db()->query('SELECT COUNT(*) FROM cinemas')->fetchColumn();
    }
}
