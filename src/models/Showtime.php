<?php

class Showtime extends Model
{
    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT st.*, m.title AS movie_title, m.duration_minutes, m.poster_path, m.age_rating,
                    m.status AS movie_status, r.status AS room_status, c.status AS cinema_status,
                    r.name AS room_name, c.id AS cinema_id, c.name AS cinema_name, c.address AS cinema_address
             FROM showtimes st
             INNER JOIN movies m ON m.id = st.movie_id
             INNER JOIN rooms r ON r.id = st.room_id
             INNER JOIN cinemas c ON c.id = r.cinema_id
             WHERE st.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function startTimestamp(array $showtime): int
    {
        return strtotime($showtime['show_date'] . ' ' . $showtime['start_time']);
    }

    /** Phim Ngừng chiếu hoặc phòng / rạp Ngừng hoạt động thì suất không bán được nữa (UC01, UC11). */
    public static function isVenueInactive(array $showtime): bool
    {
        return ($showtime['movie_status'] ?? 'now_showing') === 'ended'
            || ($showtime['room_status'] ?? 'active') !== 'active'
            || ($showtime['cinema_status'] ?? 'active') !== 'active';
    }

    /** Đóng bán khi đã hủy, phim/phòng/rạp ngừng, hoặc còn dưới 15 phút nữa là chiếu (BR03), tính theo thời gian thực. */
    public static function isSaleClosed(array $showtime, ?int $now = null): bool
    {
        $now = $now ?? time();
        return $showtime['status'] !== 'active'
            || self::isVenueInactive($showtime)
            || $now >= self::startTimestamp($showtime) - SALE_CLOSE_MINUTES * 60;
    }

    /** Đã chiếu xong = giờ bắt đầu + thời lượng phim (dùng cho BR08). */
    public static function hasFinished(array $showtime, ?int $now = null): bool
    {
        $now = $now ?? time();
        return $now >= self::startTimestamp($showtime) + (int) $showtime['duration_minutes'] * 60;
    }

    /**
     * Tra cứu suất chiếu còn hoạt động theo phim / rạp / ngày (mỗi tham số optional).
     */
    public const SEARCH_DAYS = 7;

    /**
     * Tra cứu suất chưa hủy, chưa bắt đầu, trong SEARCH_DAYS ngày tới (UC02). Kèm số ghế còn
     * trống; suất đã đóng bán (< 15 phút) vẫn trả về để giao diện hiển thị mờ.
     */
    public static function search(?int $movieId, ?int $cinemaId, ?int $roomId, ?string $date): array
    {
        $sql = 'SELECT st.*, m.title AS movie_title, m.duration_minutes, c.name AS cinema_name, r.name AS room_name,
                       (SELECT COUNT(*) FROM seats s WHERE s.room_id = st.room_id AND s.status = "active")
                     - (SELECT COUNT(*) FROM showtime_seats ss WHERE ss.showtime_id = st.id) AS seats_available
                FROM showtimes st
                INNER JOIN movies m ON m.id = st.movie_id
                INNER JOIN rooms r ON r.id = st.room_id
                INNER JOIN cinemas c ON c.id = r.cinema_id
                WHERE st.status = "active" AND TIMESTAMP(st.show_date, st.start_time) > NOW()
                  AND st.show_date < CURDATE() + INTERVAL ? DAY
                  AND m.status <> "ended" AND r.status = "active" AND c.status = "active"';
        $params = [self::SEARCH_DAYS];

        foreach (['st.movie_id' => $movieId, 'c.id' => $cinemaId, 'r.id' => $roomId, 'st.show_date' => $date] as $column => $value) {
            if ($value) {
                $sql .= " AND $column = ?";
                $params[] = $value;
            }
        }

        $sql .= ' ORDER BY st.show_date, st.start_time';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function allWithDetails(): array
    {
        return self::db()->query(
            'SELECT st.*, m.title AS movie_title, m.duration_minutes, c.name AS cinema_name, r.name AS room_name,
                    (SELECT COUNT(*) FROM showtime_seats ss WHERE ss.showtime_id = st.id) AS sold_count
             FROM showtimes st
             INNER JOIN movies m ON m.id = st.movie_id
             INNER JOIN rooms r ON r.id = st.room_id
             INNER JOIN cinemas c ON c.id = r.cinema_id
             ORDER BY st.show_date DESC, st.start_time DESC'
        )->fetchAll();
    }

    /** Trạng thái tính theo thời gian thực: Mở bán -> Đóng bán (-15') -> Đã chiếu; hoặc Đã hủy. */
    public static function statusLabel(array $showtime): string
    {
        if ($showtime['status'] === 'cancelled') {
            return 'Đã hủy';
        }
        if (self::hasFinished($showtime)) {
            return 'Đã chiếu';
        }
        return self::isSaleClosed($showtime) ? 'Đóng bán' : 'Mở bán';
    }

    /** Số ghế đã bán của suất (ghế-theo-suất). */
    public static function soldCount(int $id): int
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM showtime_seats WHERE showtime_id = ?');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Suất đang hoạt động cùng phòng bị trùng khung [$newStart, $newEnd) (BR02):
     * startA < endB AND startB < endA. $newStart/$newEnd là 'Y-m-d H:i:s'. Suất có
     * end_time <= start_time được hiểu là kết thúc vào ngày hôm sau, nên xét cả ngày trước đó.
     */
    public static function findOverlap(int $roomId, string $newStart, string $newEnd, ?int $excludeId = null): ?array
    {
        $sql = 'SELECT st.*, m.title AS movie_title,
                       TIMESTAMP(st.show_date, st.start_time) AS starts_at,
                       TIMESTAMP(st.show_date, st.end_time) + INTERVAL IF(st.end_time <= st.start_time, 1, 0) DAY AS ends_at
                FROM showtimes st
                INNER JOIN movies m ON m.id = st.movie_id
                WHERE st.room_id = ? AND st.status = "active"
                  AND st.show_date BETWEEN DATE_SUB(DATE(?), INTERVAL 1 DAY) AND DATE(?)
                  AND TIMESTAMP(st.show_date, st.start_time) < ?
                  AND TIMESTAMP(st.show_date, st.end_time) + INTERVAL IF(st.end_time <= st.start_time, 1, 0) DAY > ?';
        $params = [$roomId, $newStart, $newEnd, $newEnd, $newStart];

        if ($excludeId) {
            $sql .= ' AND st.id != ?';
            $params[] = $excludeId;
        }
        $sql .= ' ORDER BY starts_at LIMIT 1';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Thêm / sửa suất trong 1 transaction có khóa dòng phòng chiếu, để 2 admin lưu cùng lúc
     * không chèn được 2 suất trùng giờ. Khi sửa, dòng suất được khóa rồi mới kiểm tra số vé
     * đã bán, để 1 đơn đặt chen vào giữa không làm suất đã bán vé bị đổi giờ / phòng / phim.
     * Trả về [id, null, null], [null, suất bị trùng, null] hoặc [null, null, thông báo lỗi].
     *
     * @return array{0: ?int, 1: ?array, 2: ?string}
     */
    public static function saveWithoutOverlap(array $data, ?int $id = null): array
    {
        $db = self::db();
        $db->beginTransaction();
        try {
            if ($id !== null) {
                $stmt = $db->prepare('SELECT * FROM showtimes WHERE id = ? FOR UPDATE');
                $stmt->execute([$id]);
                $current = $stmt->fetch();
                if ($current && self::soldCount($id) > 0 && self::scheduleChanged($current, $data)) {
                    $db->rollBack();
                    return [null, null, 'Suất chiếu đã bán vé: chỉ được sửa giá cơ bản, không đổi phim / phòng / giờ chiếu.'];
                }
            }

            $db->prepare('SELECT id FROM rooms WHERE id = ? FOR UPDATE')->execute([$data['room_id']]);

            $conflict = self::findOverlap($data['room_id'], $data['starts_at'], $data['ends_at'], $id);
            if ($conflict) {
                $db->rollBack();
                return [null, $conflict, null];
            }

            if ($id === null) {
                $id = self::create($data);
            } else {
                self::update($id, $data);
            }
            $db->commit();
            return [$id, null, null];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /** Đổi phim / phòng / giờ bắt đầu so với suất đang lưu ($data theo dạng của AdminShowtimeController::validate). */
    public static function scheduleChanged(array $existing, array $data): bool
    {
        return (int) $existing['movie_id'] !== (int) $data['movie_id']
            || (int) $existing['room_id'] !== (int) $data['room_id']
            || self::startTimestamp($existing) !== strtotime($data['starts_at']);
    }

    /** Hủy suất (không xóa cứng - BR09). Chỉ suất đang hoạt động và chưa bán vé nào. */
    public static function cancelIfNoTickets(int $id): bool
    {
        $stmt = self::db()->prepare(
            'UPDATE showtimes SET status = "cancelled"
             WHERE id = ? AND status = "active"
               AND NOT EXISTS (SELECT 1 FROM showtime_seats ss WHERE ss.showtime_id = showtimes.id)'
        );
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public static function create(array $data): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO showtimes (movie_id, room_id, show_date, start_time, end_time, base_price, status)
             VALUES (?, ?, ?, ?, ?, ?, "active")'
        );
        $stmt->execute([
            $data['movie_id'], $data['room_id'], $data['show_date'],
            $data['start_time'], $data['end_time'], $data['base_price'],
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = self::db()->prepare(
            'UPDATE showtimes SET movie_id = ?, room_id = ?, show_date = ?, start_time = ?, end_time = ?, base_price = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $data['movie_id'], $data['room_id'], $data['show_date'],
            $data['start_time'], $data['end_time'], $data['base_price'], $id,
        ]);
    }

    public static function count(): int
    {
        return (int) self::db()->query('SELECT COUNT(*) FROM showtimes')->fetchColumn();
    }
}
