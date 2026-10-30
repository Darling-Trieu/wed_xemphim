-- =====================================================================
-- Migration 001: đưa CSDL về đúng đặc tả Chương 2.
-- Áp dụng cho CSDL đang có (đã chạy schema.sql cũ + migration_refund.sql).
-- Cài mới từ schema.sql thì KHÔNG cần chạy file này.
--   mysql -u root --default-character-set=utf8mb4 movie_booking < database\migrations\001_spec_alignment.sql
-- =====================================================================

-- ---------------------------------------------------------------------
-- UC04/UC06: SĐT là định danh đăng nhập nên phải duy nhất; họ tên tối đa 100 ký tự
-- ---------------------------------------------------------------------
UPDATE users SET phone = NULL WHERE phone = '';
ALTER TABLE users
    MODIFY full_name VARCHAR(100) NOT NULL,
    ADD UNIQUE KEY uq_users_phone (phone);

-- ---------------------------------------------------------------------
-- UC01/UC10: bổ sung đạo diễn, diễn viên, ngôn ngữ; nhãn tuổi có thêm K
-- ---------------------------------------------------------------------
ALTER TABLE movies
    MODIFY title VARCHAR(200) NOT NULL,
    MODIFY age_rating ENUM('P','K','T13','T16','T18') NOT NULL DEFAULT 'P',
    ADD COLUMN director VARCHAR(150) NULL AFTER description,
    ADD COLUMN actors   VARCHAR(500) NULL AFTER director,
    ADD COLUMN language VARCHAR(50)  NULL AFTER actors;

-- ---------------------------------------------------------------------
-- UC11: loại phòng, tên phòng duy nhất trong 1 rạp, ô lối đi trong sơ đồ
-- ---------------------------------------------------------------------
ALTER TABLE rooms
    ADD COLUMN room_type ENUM('standard','deluxe') NOT NULL DEFAULT 'standard' AFTER name,
    ADD UNIQUE KEY uq_room_name (cinema_id, name);

ALTER TABLE seats
    MODIFY status ENUM('active','inactive','aisle') NOT NULL DEFAULT 'active';

-- ---------------------------------------------------------------------
-- BR05: giá = giá cơ bản suất + phụ thu loại ghế (thay cho hệ số nhân)
-- ---------------------------------------------------------------------
ALTER TABLE seat_types
    ADD COLUMN surcharge INT UNSIGNED NOT NULL DEFAULT 0 AFTER name;

UPDATE seat_types SET surcharge = CASE name
    WHEN 'VIP' THEN 10000
    WHEN 'Đôi' THEN 60000
    ELSE 0
END;

ALTER TABLE seat_types
    DROP COLUMN price_multiplier,
    ADD UNIQUE KEY uq_seat_type_name (name);

-- ---------------------------------------------------------------------
-- BR06: snapshot thông tin suất chiếu trên đơn
-- ---------------------------------------------------------------------
ALTER TABLE bookings
    ADD COLUMN movie_title VARCHAR(200) NULL AFTER showtime_id,
    ADD COLUMN cinema_name VARCHAR(150) NULL AFTER movie_title,
    ADD COLUMN room_name   VARCHAR(50)  NULL AFTER cinema_name,
    ADD COLUMN show_start  DATETIME     NULL AFTER room_name;

UPDATE bookings b
JOIN showtimes st ON st.id = b.showtime_id
JOIN movies m     ON m.id = st.movie_id
JOIN rooms r      ON r.id = st.room_id
JOIN cinemas c    ON c.id = r.cinema_id
SET b.movie_title = m.title,
    b.cinema_name = c.name,
    b.room_name   = r.name,
    b.show_start  = TIMESTAMP(st.show_date, st.start_time);

ALTER TABLE bookings
    MODIFY movie_title VARCHAR(200) NOT NULL,
    MODIFY cinema_name VARCHAR(150) NOT NULL,
    MODIFY room_name   VARCHAR(50)  NOT NULL,
    MODIFY show_start  DATETIME     NOT NULL;

-- ---------------------------------------------------------------------
-- BR01: ghế theo suất. Có dòng = Đã bán, không có dòng = Trống.
-- UNIQUE(showtime_id, seat_id) chặn bán trùng (lỗi 1062); hủy đơn thì xóa dòng.
-- ---------------------------------------------------------------------
CREATE TABLE showtime_seats (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    showtime_id INT UNSIGNED NOT NULL,
    seat_id     INT UNSIGNED NOT NULL,
    booking_id  INT UNSIGNED NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_showtime_seat (showtime_id, seat_id),
    FOREIGN KEY (showtime_id) REFERENCES showtimes(id),
    FOREIGN KEY (seat_id) REFERENCES seats(id),
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO showtime_seats (showtime_id, seat_id, booking_id)
SELECT bs.showtime_id, bs.seat_id, bs.booking_id
FROM booking_seats bs
JOIN bookings b ON b.id = bs.booking_id
WHERE b.status = 'paid' AND bs.is_active = 1;

-- ---------------------------------------------------------------------
-- BR06: booking_seats giờ là "vé", lưu snapshot nhãn ghế, loại ghế, giá
-- ---------------------------------------------------------------------
ALTER TABLE booking_seats
    ADD COLUMN seat_label     VARCHAR(10) NULL AFTER seat_id,
    ADD COLUMN seat_type_name VARCHAR(50) NULL AFTER seat_label;

UPDATE booking_seats bs
JOIN seats s       ON s.id = bs.seat_id
JOIN seat_types sty ON sty.id = s.seat_type_id
SET bs.seat_label = CONCAT(s.seat_row, s.seat_number),
    bs.seat_type_name = sty.name;

ALTER TABLE booking_seats
    MODIFY seat_label     VARCHAR(10) NOT NULL,
    MODIFY seat_type_name VARCHAR(50) NOT NULL,
    DROP INDEX uq_showtime_seat,
    DROP COLUMN is_active,
    ADD INDEX idx_ticket_showtime (showtime_id);

-- ---------------------------------------------------------------------
-- UC07/UC13: thanh toán mô phỏng. Thành công -> Đã hoàn
-- ---------------------------------------------------------------------
CREATE TABLE payments (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id  INT UNSIGNED NOT NULL UNIQUE,
    amount      DECIMAL(12,0) NOT NULL,
    method      VARCHAR(30) NOT NULL DEFAULT 'simulated',
    status      ENUM('success','refunded') NOT NULL DEFAULT 'success',
    paid_at     DATETIME NOT NULL,
    refunded_at DATETIME NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO payments (booking_id, amount, status, paid_at, refunded_at)
SELECT id, total_amount,
       IF(status = 'cancelled', 'refunded', 'success'),
       created_at,
       IF(status = 'cancelled', COALESCE(refunded_at, NOW()), NULL)
FROM bookings;

-- ---------------------------------------------------------------------
-- BR08: mỗi user 1 đánh giá / phim, nhận xét tối đa 500 ký tự
-- ---------------------------------------------------------------------
DELETE r1 FROM reviews r1
JOIN reviews r2 ON r2.movie_id = r1.movie_id AND r2.user_id = r1.user_id AND r2.id > r1.id;

UPDATE reviews SET comment = LEFT(comment, 500) WHERE CHAR_LENGTH(comment) > 500;

ALTER TABLE reviews
    MODIFY comment VARCHAR(500) NULL,
    ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
    ADD UNIQUE KEY uq_review_user_movie (movie_id, user_id);

-- ---------------------------------------------------------------------
-- Log: đăng nhập (thành công/thất bại) và thao tác admin. Giao diện chỉ đọc.
-- ---------------------------------------------------------------------
CREATE TABLE audit_logs (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NULL,
    action      VARCHAR(50)  NOT NULL,
    entity      VARCHAR(50)  NOT NULL,
    entity_id   VARCHAR(50)  NULL,
    before_data JSON NULL,
    after_data  JSON NULL,
    ip_address  VARCHAR(45)  NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_entity (entity, entity_id),
    INDEX idx_audit_created (created_at)
) ENGINE=InnoDB;
