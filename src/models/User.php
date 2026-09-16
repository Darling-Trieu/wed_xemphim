<?php

class User extends Model
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(string $fullName, string $email, string $password, ?string $phone): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO users (full_name, email, password_hash, phone, role, status)
             VALUES (?, ?, ?, ?, "customer", "active")'
        );
        $stmt->execute([$fullName, $email, password_hash($password, PASSWORD_BCRYPT), $phone]);
        return (int) self::db()->lastInsertId();
    }

    public static function updateProfile(int $id, string $fullName, ?string $phone): void
    {
        $stmt = self::db()->prepare('UPDATE users SET full_name = ?, phone = ? WHERE id = ?');
        $stmt->execute([$fullName, $phone, $id]);
    }

    public static function allCustomers(): array
    {
        return self::db()->query(
            "SELECT * FROM users WHERE role = 'customer' ORDER BY created_at DESC"
        )->fetchAll();
    }

    public static function toggleStatus(int $id): void
    {
        self::db()->prepare(
            "UPDATE users SET status = IF(status = 'active', 'locked', 'active') WHERE id = ?"
        )->execute([$id]);
    }

    public static function countCustomers(): int
    {
        return (int) self::db()->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
    }
}
