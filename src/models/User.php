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

    public static function findByPhone(string $phone): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM users WHERE phone = ? LIMIT 1');
        $stmt->execute([$phone]);
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

    /** Đăng nhập bằng email hoặc số điện thoại (UC05). */
    public static function findByLogin(string $login): ?array
    {
        return str_contains($login, '@')
            ? self::findByEmail(strtolower($login))
            : self::findByPhone($login);
    }

    /** Danh sách tài khoản kèm số đơn, tìm theo email / SĐT (UC14). */
    public static function search(string $keyword = ''): array
    {
        $sql = 'SELECT u.*, (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id) AS booking_count
                FROM users u';
        $params = [];
        if ($keyword !== '') {
            $like = '%' . addcslashes($keyword, '%_\\') . '%';
            $sql .= ' WHERE u.email LIKE ? OR u.phone LIKE ?';
            $params = [$like, $like];
        }
        $sql .= ' ORDER BY u.role, u.created_at DESC';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function setStatus(int $id, string $status): void
    {
        self::db()->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$status, $id]);
    }

    public static function countCustomers(): int
    {
        return (int) self::db()->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
    }
}
