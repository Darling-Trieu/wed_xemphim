<?php

require_once __DIR__ . '/Showtime.php';
require_once __DIR__ . '/Seat.php';
require_once __DIR__ . '/AuditLog.php';

/** Lỗi nghiệp vụ khi đặt / hủy vé: thông báo được hiển thị nguyên văn cho người dùng. */
class BookingException extends RuntimeException
{
}

class BookingSeatConflictException extends BookingException
{
}

class BookingAgeException extends BookingException
{
}

class Booking extends Model
{
    public const AGE_RESTRICTED = ['T13', 'T16', 'T18'];

    public static function requiresAgeConfirm(string $ageRating): bool
    {
        return in_array($ageRating, self::AGE_RESTRICTED, true);
    }

    /** "1,2,2,x" -> [1, 2]: số nguyên dương, không trùng, tăng dần. */
    public static function normalizeSeatIds($raw): array
    {
        $parts = is_array($raw) ? $raw : explode(',', (string) $raw);
        $ids = array_filter(array_map('intval', $parts), static fn ($id) => $id > 0);
        $ids = array_values(array_unique($ids));
        sort($ids);
        return $ids;
    }

    /**
     * Kiểm tra suất + ghế và tính giá ở server (BR03, BR04, BR05, BR07). Giá client gửi lên
     * (nếu có) bị bỏ qua hoàn toàn. $ageConfirmed = null: bỏ qua kiểm tra độ tuổi
     * (dùng khi hiển thị màn hình xác nhận).
     *
     * @return array<int, array{seat_id:int, seat_label:string, seat_type_name:string, price:int}>
     */
    public static function prepareTickets(array $showtime, array $seatIds, ?bool $ageConfirmed): array
    {
        if ($showtime['status'] === 'cancelled') {
            throw new BookingException('Suất chiếu đã bị hủy.');
        }
        if (Showtime::isSaleClosed($showtime)) {
            throw new BookingException('Suất chiếu đã đóng bán.');
        }

        if (count($seatIds) === 0) {
            throw new BookingException('Vui lòng chọn ít nhất 1 ghế.');
        }
        if (count($seatIds) > MAX_SEATS_PER_BOOKING) {
            throw new BookingException('Mỗi đơn chỉ được chọn tối đa ' . MAX_SEATS_PER_BOOKING . ' ghế.');
        }

        $seatById = [];
        foreach (Seat::allByRoom((int) $showtime['room_id']) as $seat) {
            $seatById[(int) $seat['id']] = $seat;
        }

        $tickets = [];
        foreach ($seatIds as $seatId) {
            $seat = $seatById[$seatId] ?? null;
            if (!$seat || $seat['status'] !== 'active') {
                throw new BookingException('Ghế không hợp lệ hoặc không thuộc phòng chiếu của suất này, vui lòng chọn lại.');
            }
            $tickets[] = [
                'seat_id'        => $seatId,
                'seat_label'     => $seat['seat_row'] . $seat['seat_number'],
                'seat_type_name' => $seat['seat_type_name'],
                'price'          => (int) $showtime['base_price'] + (int) $seat['surcharge'],
            ];
        }

        if ($ageConfirmed === false && self::requiresAgeConfirm($showtime['age_rating'])) {
            throw new BookingAgeException('Cần xác nhận đúng độ tuổi quy định.');
        }

        return $tickets;
    }

    /**
     * Tạo đơn trong MỘT transaction (UC07): kiểm tra suất + ghế, tính lại giá, tạo đơn kèm
     * snapshot, ghi ghế-theo-suất, vé, thanh toán mô phỏng. UNIQUE(showtime_id, seat_id) của
     * showtime_seats là chốt chặn cuối: request đến sau nhận lỗi 1062 -> rollback toàn bộ.
     */
    public static function create(int $userId, int $showtimeId, array $seatIds, bool $ageConfirmed): string
    {
        $db = self::db();
        $db->beginTransaction();

        try {
            // Khóa chia sẻ dòng suất chiếu: admin không hủy/sửa suất được giữa chừng,
            // các đơn khác của cùng suất vẫn chạy song song.
            $db->prepare('SELECT id FROM showtimes WHERE id = ? FOR SHARE')->execute([$showtimeId]);
            $showtime = Showtime::find($showtimeId);
            if (!$showtime) {
                throw new BookingException('Không tìm thấy suất chiếu.');
            }

            $tickets = self::prepareTickets($showtime, $seatIds, $ageConfirmed);

            $taken = self::soldLabels($showtimeId, array_column($tickets, 'seat_id'));
            if ($taken) {
                throw new BookingSeatConflictException('Ghế ' . implode(', ', $taken) . ' đã có người đặt, vui lòng chọn ghế khác.');
            }

            $total = array_sum(array_column($tickets, 'price'));
            [$bookingId, $code] = self::insertBooking($userId, $showtime, $total);

            // Ghi theo thứ tự seat_id tăng dần để 2 đơn chồng ghế không deadlock lẫn nhau
            $soldStmt = $db->prepare('INSERT INTO showtime_seats (showtime_id, seat_id, booking_id) VALUES (?, ?, ?)');
            foreach ($tickets as $ticket) {
                try {
                    $soldStmt->execute([$showtimeId, $ticket['seat_id'], $bookingId]);
                } catch (PDOException $e) {
                    if (self::isDuplicateKey($e)) {
                        throw new BookingSeatConflictException('Ghế ' . $ticket['seat_label'] . ' đã có người đặt, vui lòng chọn ghế khác.');
                    }
                    throw $e;
                }
            }

            $ticketStmt = $db->prepare(
                'INSERT INTO booking_seats (booking_id, showtime_id, seat_id, seat_label, seat_type_name, price)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            foreach ($tickets as $ticket) {
                $ticketStmt->execute([
                    $bookingId, $showtimeId, $ticket['seat_id'],
                    $ticket['seat_label'], $ticket['seat_type_name'], $ticket['price'],
                ]);
            }

            $db->prepare(
                'INSERT INTO payments (booking_id, amount, method, status, paid_at) VALUES (?, ?, "simulated", "success", NOW())'
            )->execute([$bookingId, $total]);

            $db->commit();
            return $code;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            // Deadlock / chờ khóa quá lâu: coi như ghế vừa bị người khác giữ
            if ($e instanceof PDOException && in_array($e->errorInfo[1] ?? null, [1205, 1213], true)) {
                throw new BookingSeatConflictException('Một số ghế vừa có người đặt, vui lòng chọn lại.');
            }
            throw $e;
        }
    }

    /** @return string[] nhãn các ghế (trong $seatIds) đã bán cho suất này */
    public static function soldLabels(int $showtimeId, array $seatIds): array
    {
        $placeholders = implode(',', array_fill(0, count($seatIds), '?'));
        $stmt = self::db()->prepare(
            "SELECT CONCAT(s.seat_row, s.seat_number) FROM showtime_seats ss
             INNER JOIN seats s ON s.id = ss.seat_id
             WHERE ss.showtime_id = ? AND ss.seat_id IN ($placeholders)
             ORDER BY ss.seat_id"
        );
        $stmt->execute(array_merge([$showtimeId], $seatIds));
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /** @return array{0:int, 1:string} [id, booking_code] */
    private static function insertBooking(int $userId, array $showtime, int $total): array
    {
        $stmt = self::db()->prepare(
            'INSERT INTO bookings (user_id, showtime_id, movie_title, cinema_name, room_name, show_start,
                                   booking_code, total_amount, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, "paid")'
        );

        // Mã đơn ngẫu nhiên, UNIQUE ở CSDL; trùng (rất hiếm) thì sinh mã khác
        for ($attempt = 1; ; $attempt++) {
            $code = self::generateCode();
            try {
                $stmt->execute([
                    $userId,
                    $showtime['id'],
                    $showtime['movie_title'],
                    $showtime['cinema_name'],
                    $showtime['room_name'],
                    $showtime['show_date'] . ' ' . $showtime['start_time'],
                    $code,
                    $total,
                ]);
                return [(int) self::db()->lastInsertId(), $code];
            } catch (PDOException $e) {
                if (!self::isDuplicateKey($e) || $attempt >= 3) {
                    throw $e;
                }
            }
        }
    }

    private static function isDuplicateKey(PDOException $e): bool
    {
        return ($e->errorInfo[1] ?? null) === 1062;
    }

    private static function generateCode(): string
    {
        return 'VE' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
    }

    /** Thông tin đơn lấy từ snapshot trên bảng bookings, không JOIN sang phim/suất hiện tại (BR06). */
    private const SELECT_BOOKING =
        'SELECT b.*, DATE(b.show_start) AS show_date, TIME(b.show_start) AS start_time,
                u.full_name, u.email, p.status AS payment_status
         FROM bookings b
         INNER JOIN users u ON u.id = b.user_id
         LEFT JOIN payments p ON p.booking_id = b.id';

    public static function findByCode(string $code): ?array
    {
        $stmt = self::db()->prepare(self::SELECT_BOOKING . ' WHERE b.booking_code = ?');
        $stmt->execute([$code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function allByUser(int $userId): array
    {
        $stmt = self::db()->prepare(self::SELECT_BOOKING . ' WHERE b.user_id = ? ORDER BY b.created_at DESC, b.id DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function allWithDetails(): array
    {
        return self::db()->query(self::SELECT_BOOKING . ' ORDER BY b.created_at DESC, b.id DESC')->fetchAll();
    }

    /**
     * Admin hủy đơn trong MỘT transaction (UC13): đơn -> Đã hủy, thanh toán -> Đã hoàn,
     * xóa ghế-theo-suất để ghế trống lại, ghi log. Chỉ hủy được đơn Đã xác nhận
     * của suất chưa bắt đầu.
     */
    public static function cancelAndRefund(int $id, string $reason): void
    {
        $db = self::db();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare(
                'SELECT b.*, st.show_date, st.start_time FROM bookings b
                 INNER JOIN showtimes st ON st.id = b.showtime_id
                 WHERE b.id = ? FOR UPDATE'
            );
            $stmt->execute([$id]);
            $booking = $stmt->fetch();

            if (!$booking) {
                throw new BookingException('Không tìm thấy đơn vé.');
            }
            if ($booking['status'] !== 'paid') {
                throw new BookingException('Đơn vé ' . $booking['booking_code'] . ' đã được hủy trước đó.');
            }
            if (time() >= strtotime($booking['show_date'] . ' ' . $booking['start_time'])) {
                throw new BookingException('Không thể hủy đơn của suất đã chiếu.');
            }

            $db->prepare(
                'UPDATE bookings
                 SET status = "cancelled", refund_amount = total_amount, refunded_at = NOW(), cancel_reason = ?
                 WHERE id = ?'
            )->execute([$reason, $id]);
            $db->prepare('UPDATE payments SET status = "refunded", refunded_at = NOW() WHERE booking_id = ?')->execute([$id]);
            $db->prepare('DELETE FROM showtime_seats WHERE booking_id = ?')->execute([$id]);

            AuditLog::write('cancel', 'booking', $booking['booking_code'],
                ['status' => 'paid', 'payment' => 'success'],
                ['status' => 'cancelled', 'payment' => 'refunded', 'refund_amount' => (int) $booking['total_amount'], 'reason' => $reason]
            );

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function statusLabel(array $booking): string
    {
        if ($booking['status'] === 'paid') {
            return 'Đã xác nhận';
        }
        return $booking['refund_amount'] !== null ? 'Đã hủy · đã hoàn tiền' : 'Đã hủy';
    }

    public static function paymentLabel(?string $paymentStatus): string
    {
        return [
            'success'  => 'Thành công',
            'refunded' => 'Đã hoàn',
        ][$paymentStatus] ?? 'Không có';
    }

    public static function totalRefunded(): float
    {
        return (float) self::db()->query("SELECT COALESCE(SUM(refund_amount),0) FROM bookings WHERE status = 'cancelled'")->fetchColumn();
    }

    public static function countPaid(): int
    {
        return (int) self::db()->query("SELECT COUNT(*) FROM bookings WHERE status = 'paid'")->fetchColumn();
    }

    public static function totalRevenue(): float
    {
        return (float) self::db()->query("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE status = 'paid'")->fetchColumn();
    }

    /** Doanh thu theo từng ngày trong N ngày gần nhất, dùng cho biểu đồ admin. */
    public static function revenueSeries(int $days = 14): array
    {
        $stmt = self::db()->prepare(
            "SELECT DATE(created_at) AS day, COALESCE(SUM(total_amount),0) AS revenue
             FROM bookings
             WHERE status = 'paid' AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(created_at)
             ORDER BY day"
        );
        $stmt->execute([$days]);
        $rows = $stmt->fetchAll();

        $byDay = [];
        foreach ($rows as $row) {
            $byDay[$row['day']] = (float) $row['revenue'];
        }

        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $series[] = ['day' => $day, 'revenue' => $byDay[$day] ?? 0];
        }

        return $series;
    }
}
