-- =====================================================================
-- Nâng cấp CSDL đang có để hỗ trợ hoàn tiền khi admin hủy vé.
-- Chỉ cần chạy 1 lần trên CSDL cũ (import schema.sql mới thì KHÔNG cần chạy file này).
--   mysql -u root --default-character-set=utf8mb4 < database\migration_refund.sql
-- =====================================================================

USE movie_booking;

ALTER TABLE bookings
    ADD COLUMN refund_amount DECIMAL(12,0) NULL AFTER status,
    ADD COLUMN refunded_at   DATETIME NULL AFTER refund_amount,
    ADD COLUMN cancel_reason VARCHAR(255) NULL AFTER refunded_at;

ALTER TABLE booking_seats
    ADD COLUMN is_active TINYINT NULL DEFAULT 1 AFTER price,
    DROP INDEX uq_showtime_seat,
    ADD UNIQUE KEY uq_showtime_seat (showtime_id, seat_id, is_active);

-- Các đơn đã hủy trước đây: coi như đã hoàn đủ tiền và trả ghế lại
UPDATE bookings
SET refund_amount = total_amount, refunded_at = NOW()
WHERE status = 'cancelled' AND refund_amount IS NULL;

UPDATE booking_seats bs
JOIN bookings b ON b.id = bs.booking_id
SET bs.is_active = NULL
WHERE b.status = 'cancelled';
