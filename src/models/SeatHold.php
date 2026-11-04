<?php

class SeatHold extends Model
{
    public const TTL_SECONDS = 10 * 60;

    public static function activeByShowtime(int $showtimeId, string $sessionId): array
    {
        self::deleteExpired();
        $stmt = self::db()->prepare(
            'SELECT seat_id, session_id FROM seat_holds WHERE showtime_id = ? AND expires_at > NOW()'
        );
        $stmt->execute([$showtimeId]);

        $holds = [];
        foreach ($stmt->fetchAll() as $row) {
            $holds[(int) $row['seat_id']] = $row['session_id'] === $sessionId;
        }
        return $holds;
    }

    public static function hold(int $showtimeId, int $seatId, string $sessionId): bool
    {
        self::deleteExpired();
        $db = self::db();
        $db->beginTransaction();
        $stmt = $db->prepare(
            'SELECT session_id FROM seat_holds
             WHERE showtime_id = ? AND seat_id = ? AND expires_at > NOW()
             FOR UPDATE'
        );
        try {
            $stmt->execute([$showtimeId, $seatId]);
            $current = $stmt->fetchColumn();
            if ($current !== false && $current !== $sessionId) {
                $db->rollBack();
                return false;
            }

            $db->prepare(
                'INSERT INTO seat_holds (showtime_id, seat_id, session_id, expires_at)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE session_id = VALUES(session_id),
                                         expires_at = VALUES(expires_at)'
            )->execute([
                $showtimeId, $seatId, $sessionId,
                date('Y-m-d H:i:s', time() + self::TTL_SECONDS),
            ]);
            $db->commit();
            return true;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
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

    public static function assertOwned(int $showtimeId, array $seatIds, string $sessionId): void
    {
        self::deleteExpired();
        if (!$seatIds) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($seatIds), '?'));
        $stmt = self::db()->prepare(
            "SELECT seat_id FROM seat_holds
             WHERE showtime_id = ? AND session_id = ? AND expires_at > NOW()
               AND seat_id IN ($placeholders)"
        );
        $stmt->execute(array_merge([$showtimeId, $sessionId], $seatIds));
        $owned = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        if (count($owned) !== count(array_unique($seatIds))) {
            throw new BookingException('Một số ghế đã hết thời gian giữ, vui lòng chọn lại.');
        }
    }

    private static function deleteExpired(): void
    {
        self::db()->exec('DELETE FROM seat_holds WHERE expires_at <= NOW()');
    }
}
