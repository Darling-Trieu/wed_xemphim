<?php

/** Loại ghế và phụ thu: giá ghế = giá cơ bản của suất + phụ thu (BR05). */
class SeatType extends Model
{
    public static function all(): array
    {
        return self::db()->query('SELECT * FROM seat_types ORDER BY surcharge, id')->fetchAll();
    }

    /** Kèm số ghế đang dùng loại này (để biết có xóa được không). */
    public static function allWithUsage(): array
    {
        return self::db()->query(
            'SELECT st.*, (SELECT COUNT(*) FROM seats s WHERE s.seat_type_id = st.id) AS seat_count
             FROM seat_types st ORDER BY st.surcharge, st.id'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM seat_types WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function nameExists(string $name, ?int $excludeId = null): bool
    {
        $stmt = self::db()->prepare('SELECT EXISTS(SELECT 1 FROM seat_types WHERE name = ? AND id != ?)');
        $stmt->execute([$name, $excludeId ?? 0]);
        return (bool) $stmt->fetchColumn();
    }

    public static function create(string $name, int $surcharge): int
    {
        self::db()->prepare('INSERT INTO seat_types (name, surcharge) VALUES (?, ?)')->execute([$name, $surcharge]);
        return (int) self::db()->lastInsertId();
    }

    /** Đổi phụ thu chỉ ảnh hưởng vé bán sau đó; vé đã bán giữ giá snapshot (BR06). */
    public static function update(int $id, string $name, int $surcharge): void
    {
        self::db()->prepare('UPDATE seat_types SET name = ?, surcharge = ? WHERE id = ?')->execute([$name, $surcharge, $id]);
    }

    public static function delete(int $id): void
    {
        self::db()->prepare('DELETE FROM seat_types WHERE id = ?')->execute([$id]);
    }
}
