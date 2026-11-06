<?php

require_once __DIR__ . '/Booking.php';

/**
 * Giữ ghế tạm (FR14): khách chọn ghế -> ghế được giữ TTL_SECONDS cho session đó, người khác
 * không chọn được. Hết hạn thì tự nhả. Ghế chỉ thật sự "Đã bán" khi Booking::create thành công.
 */
class SeatHold extends Model
{
    public const TTL_SECONDS = 5 * 60;

    /** @return array<int, array{held_by_me: bool, expires_at: int}> theo seat_id */
    public static function activeByShowtime(int $showtimeId, string $sessionId): array
    {
        self::deleteExpired();
        $stmt = self::db()->prepare(
            'SELECT seat_id, session_id, UNIX_TIMESTAMP(expires_at) AS expires_at
             FROM seat_holds WHERE showtime_id = ? AND expires_at > NOW()'
        );
        $stmt->execute([$showtimeId]);

        $holds = [];
        foreach ($stmt->fetchAll() as $row) {
            $holds[(int) $row['seat_id']] = [
                'held_by_me' => $row['session_id'] === $sessionId,
                'expires_at' => (int) $row['expires_at'],
            ];
        }
        return $holds;
    }

    /**
     * Giữ (hoặc gia hạn) 1 ghế cho session. Trả về thời điểm hết hạn (unix), hoặc null nếu
     * ghế đang được session khác giữ.
     *
     * @throws BookingException khi session đã giữ đủ MAX_SEATS_PER_BOOKING ghế của suất (BR04)
     */
    public static function hold(int $showtimeId, int $seatId, string $sessionId): ?int
    {
        self::deleteExpired();
        $db = self::db();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare('SELECT session_id FROM seat_holds WHERE showtime_id = ? AND seat_id = ? FOR UPDATE');
            $stmt->execute([$showtimeId, $seatId]);
            $holder = $stmt->fetchColumn();

            if ($holder !== false && $holder !== $sessionId) {
                $db->rollBack();
                return null;
            }
            if ($holder === false && self::countBySession($showtimeId, $sessionId) >= MAX_SEATS_PER_BOOKING) {
                $db->rollBack();
                throw new BookingException('Mỗi đơn chỉ được chọn tối đa ' . MAX_SEATS_PER_BOOKING . ' ghế.');
            }

            $expiresAt = time() + self::TTL_SECONDS;
            $db->prepare(
                'INSERT INTO seat_holds (showtime_id, seat_id, session_id, expires_at) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE session_id = VALUES(session_id), expires_at = VALUES(expires_at)'
            )->execute([$showtimeId, $seatId, $sessionId, date('Y-m-d H:i:s', $expiresAt)]);

            $db->commit();
            return $expiresAt;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            // 2 session giữ cùng ghế đúng 1 thời điểm: bên thua coi như ghế đang được người khác giữ
            if ($e instanceof PDOException && in_array($e->errorInfo[1] ?? null, [1062, 1205, 1213], true)) {
                return null;
            }
            throw $e;
        }
    }

    public static function release(int $showtimeId, int $seatId, string $sessionId): void
    {
        self::db()->prepare(
            'DELETE FROM seat_holds WHERE showtime_id = ? AND seat_id = ? AND session_id = ?'
        )->execute([$showtimeId, $seatId, $sessionId]);
    }

    public static function releaseSession(int $showtimeId, string $sessionId): void
    {
        self::db()->prepare(
            'DELETE FROM seat_holds WHERE showtime_id = ? AND session_id = ?'
        )->execute([$showtimeId, $sessionId]);
    }

    /** Ghế đang chọn phải còn được chính session này giữ thì mới được đặt. */
    public static function assertOwned(int $showtimeId, array $seatIds, string $sessionId): void
    {
        self::deleteExpired();
        if (!$seatIds) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($seatIds), '?'));
        $stmt = self::db()->prepare(
            "SELECT COUNT(*) FROM seat_holds
             WHERE showtime_id = ? AND session_id = ? AND expires_at > NOW() AND seat_id IN ($placeholders)"
        );
        $stmt->execute(array_merge([$showtimeId, $sessionId], $seatIds));
        if ((int) $stmt->fetchColumn() !== count(array_unique($seatIds))) {
            throw new BookingException('Một số ghế đã hết thời gian giữ, vui lòng chọn lại.');
        }
    }

    private static function countBySession(int $showtimeId, string $sessionId): int
    {
        $stmt = self::db()->prepare(
            'SELECT COUNT(*) FROM seat_holds WHERE showtime_id = ? AND session_id = ? AND expires_at > NOW()'
        );
        $stmt->execute([$showtimeId, $sessionId]);
        return (int) $stmt->fetchColumn();
    }

    private static function deleteExpired(): void
    {
        self::db()->exec('DELETE FROM seat_holds WHERE expires_at <= NOW()');
    }
}
