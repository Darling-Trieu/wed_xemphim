<?php

class Seat extends Model
{
    public static function allByRoom(int $roomId): array
    {
        $stmt = self::db()->prepare(
            'SELECT s.*, st.name AS seat_type_name, st.surcharge
             FROM seats s
             INNER JOIN seat_types st ON st.id = s.seat_type_id
             WHERE s.room_id = ?
             ORDER BY s.seat_row, s.seat_number'
        );
        $stmt->execute([$roomId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM seats WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(int $roomId, string $row, int $number, int $seatTypeId): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO seats (room_id, seat_row, seat_number, seat_type_id, status) VALUES (?, ?, ?, ?, "active")'
        );
        $stmt->execute([$roomId, $row, $number, $seatTypeId]);
        return (int) self::db()->lastInsertId();
    }

    /** active = ghế bình thường, inactive = Ngừng sử dụng, aisle = ô lối đi (không phải ghế). */
    public const STATUSES = ['active', 'inactive', 'aisle'];

    public static function update(int $id, int $seatTypeId, string $status): void
    {
        self::db()->prepare('UPDATE seats SET seat_type_id = ?, status = ? WHERE id = ?')
            ->execute([$seatTypeId, $status, $id]);
    }

    /** @return int[] id các ghế của phòng đã từng được bán vé (không được biến thành lối đi - BR09) */
    public static function idsWithTickets(int $roomId): array
    {
        $stmt = self::db()->prepare(
            'SELECT DISTINCT bs.seat_id FROM booking_seats bs
             INNER JOIN seats s ON s.id = bs.seat_id WHERE s.room_id = ?'
        );
        $stmt->execute([$roomId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }


    /**
     * Sinh sơ đồ ghế dạng lưới: $rows số hàng (A, B, C...), $cols ghế mỗi hàng,
     * tất cả cùng 1 loại ghế mặc định ban đầu (admin có thể sửa từng ghế sau).
     */
    public static function generateGrid(int $roomId, int $rows, int $cols, int $defaultSeatTypeId): void
    {
        $stmt = self::db()->prepare(
            'INSERT INTO seats (room_id, seat_row, seat_number, seat_type_id, status) VALUES (?, ?, ?, ?, "active")'
        );

        for ($r = 0; $r < $rows; $r++) {
            $rowLabel = self::rowLabel($r);
            for ($c = 1; $c <= $cols; $c++) {
                $stmt->execute([$roomId, $rowLabel, $c, $defaultSeatTypeId]);
            }
        }
    }

    private static function rowLabel(int $index): string
    {
        $label = '';
        do {
            $label = chr(65 + ($index % 26)) . $label;
            $index = intdiv($index, 26) - 1;
        } while ($index >= 0);
        return $label;
    }
}
