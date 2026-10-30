-- =====================================================================
-- CSDL hệ thống đặt vé xem phim trực tuyến (bản đầy đủ, đã gồm migrations 001 + 002)
-- Import file này vào MySQL (Laragon -> HeidiSQL, hoặc: mysql -u root < schema.sql)
-- CSDL cũ đang chạy thì dùng database/migrations/*.sql thay vì import lại file này.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS movie_booking
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE movie_booking;

-- ---------------------------------------------------------------------
-- Người dùng (khách hàng + quản trị viên). Đăng nhập bằng email hoặc SĐT.
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(100)    NOT NULL,
    email         VARCHAR(150)    NOT NULL UNIQUE,
    password_hash VARCHAR(255)    NOT NULL,
    phone         VARCHAR(20)     NULL,
    role          ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    status        ENUM('active','locked')  NOT NULL DEFAULT 'active',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_phone (phone)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Thể loại phim
-- ---------------------------------------------------------------------
CREATE TABLE genres (
    id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Phim
-- ---------------------------------------------------------------------
CREATE TABLE movies (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title            VARCHAR(200) NOT NULL,
    description      TEXT NULL,
    director         VARCHAR(150) NULL,
    actors           VARCHAR(500) NULL,
    language         VARCHAR(50)  NULL,
    duration_minutes SMALLINT UNSIGNED NOT NULL,
    poster_path      VARCHAR(255) NULL,
    trailer_url      VARCHAR(255) NULL,
    age_rating       ENUM('P','K','T13','T16','T18') NOT NULL DEFAULT 'P',
    status           ENUM('now_showing','coming_soon','ended') NOT NULL DEFAULT 'coming_soon',
    release_date     DATE NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE movie_genres (
    movie_id INT UNSIGNED NOT NULL,
    genre_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (movie_id, genre_id),
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Rạp chiếu / phòng chiếu / loại ghế / ghế
-- ---------------------------------------------------------------------
CREATE TABLE cinemas (
    id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name    VARCHAR(150) NOT NULL,
    address VARCHAR(255) NOT NULL,
    city    VARCHAR(100) NOT NULL,
    phone   VARCHAR(20)  NULL,
    status  ENUM('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB;

CREATE TABLE rooms (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cinema_id INT UNSIGNED NOT NULL,
    name      VARCHAR(50) NOT NULL,
    room_type ENUM('standard','deluxe') NOT NULL DEFAULT 'standard',
    status    ENUM('active','inactive') NOT NULL DEFAULT 'active',
    UNIQUE KEY uq_room_name (cinema_id, name),
    FOREIGN KEY (cinema_id) REFERENCES cinemas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Giá ghế = giá cơ bản của suất + phụ thu loại ghế (BR05)
CREATE TABLE seat_types (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name      VARCHAR(50) NOT NULL,
    surcharge INT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY uq_seat_type_name (name)
) ENGINE=InnoDB;

-- status = 'aisle': ô lối đi trong lưới, không phải ghế; 'inactive': ghế ngừng sử dụng
CREATE TABLE seats (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_id      INT UNSIGNED NOT NULL,
    seat_row     VARCHAR(5)  NOT NULL,
    seat_number  INT UNSIGNED NOT NULL,
    seat_type_id INT UNSIGNED NOT NULL,
    status       ENUM('active','inactive','aisle') NOT NULL DEFAULT 'active',
    UNIQUE KEY uq_room_seat (room_id, seat_row, seat_number),
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (seat_type_id) REFERENCES seat_types(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Suất chiếu. end_time = giờ bắt đầu + thời lượng phim + 15 phút dọn phòng.
-- ---------------------------------------------------------------------
CREATE TABLE showtimes (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    movie_id   INT UNSIGNED NOT NULL,
    room_id    INT UNSIGNED NOT NULL,
    show_date  DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time   TIME NOT NULL,
    base_price DECIMAL(10,0) NOT NULL,
    status     ENUM('active','cancelled') NOT NULL DEFAULT 'active',
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    INDEX idx_showtime_lookup (movie_id, show_date),
    INDEX idx_room_schedule (room_id, show_date, start_time)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Đặt vé. status 'paid' = Đã xác nhận. movie_title..show_start là snapshot (BR06).
-- ---------------------------------------------------------------------
CREATE TABLE bookings (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    showtime_id  INT UNSIGNED NOT NULL,
    movie_title  VARCHAR(200) NOT NULL,
    cinema_name  VARCHAR(150) NOT NULL,
    room_name    VARCHAR(50)  NOT NULL,
    show_start   DATETIME     NOT NULL,
    booking_code VARCHAR(20) NOT NULL UNIQUE,
    total_amount DECIMAL(12,0) NOT NULL,
    status       ENUM('paid','cancelled') NOT NULL DEFAULT 'paid',
    -- Khi admin hủy đơn: số tiền hoàn lại cho khách, thời điểm hoàn và lý do hủy
    refund_amount DECIMAL(12,0) NULL,
    refunded_at   DATETIME NULL,
    cancel_reason VARCHAR(255) NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (showtime_id) REFERENCES showtimes(id)
) ENGINE=InnoDB;

-- Vé: mỗi dòng 1 ghế của đơn, lưu snapshot nhãn ghế, loại ghế và giá lúc đặt (BR06).
CREATE TABLE booking_seats (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id     INT UNSIGNED NOT NULL,
    showtime_id    INT UNSIGNED NOT NULL,
    seat_id        INT UNSIGNED NOT NULL,
    seat_label     VARCHAR(10) NOT NULL,
    seat_type_name VARCHAR(50) NOT NULL,
    price          DECIMAL(10,0) NOT NULL,
    INDEX idx_ticket_showtime (showtime_id),
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (showtime_id) REFERENCES showtimes(id),
    FOREIGN KEY (seat_id) REFERENCES seats(id)
) ENGINE=InnoDB;

-- Ghế theo suất (BR01): có dòng = Đã bán, không có dòng = Trống.
-- UNIQUE(showtime_id, seat_id) là chốt chặn ở CSDL: 2 request đặt cùng ghế cùng lúc thì
-- request sau nhận lỗi 1062. Hủy đơn thì xóa dòng để ghế bán lại được.
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

-- Thanh toán mô phỏng: success (Thành công) -> refunded (Đã hoàn)
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

-- ---------------------------------------------------------------------
-- Đánh giá phim: mỗi user 1 đánh giá / phim (BR08)
-- ---------------------------------------------------------------------
CREATE TABLE reviews (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    movie_id   INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    rating     TINYINT UNSIGNED NOT NULL,
    comment    VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_review_user_movie (movie_id, user_id),
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Log đăng nhập và thao tác admin (giao diện chỉ đọc)
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

-- =====================================================================
-- DỮ LIỆU MẪU
-- =====================================================================

-- Tài khoản mặc định: admin@cinema.com / admin123 ; user@example.com / user123
INSERT INTO users (full_name, email, password_hash, phone, role, status) VALUES
('Quản trị viên', 'admin@cinema.com', '$2y$10$qgaGLDQAzOAJvJdJoq89eunNHc82KVIDMA6GBBkOaJto8QnQDDIDK', '0900000000', 'admin', 'active'),
('Nguyễn Văn A',  'user@example.com', '$2y$10$Aq5vE0njSBgMEEclYp7ZdedhJZOQPh5kBrm06fVTzQZPs9Z8IeItm', '0911111111', 'customer', 'active');

INSERT INTO genres (name) VALUES
('Hành động'), ('Tình cảm'), ('Hài'), ('Kinh dị'), ('Hoạt hình'), ('Viễn tưởng'), ('Tâm lý');

INSERT INTO seat_types (name, surcharge) VALUES
('Thường', 0), ('VIP', 10000), ('Đôi', 60000);

INSERT INTO movies (title, description, duration_minutes, poster_path, trailer_url, age_rating, status, release_date) VALUES
('Street Fighter', 'Phiên bản điện ảnh người đóng của thương hiệu game đối kháng huyền thoại, nơi các võ sĩ hàng đầu thế giới đối đầu trong giải đấu sinh tử.', 119, 'street-fighter.jpg', NULL, 'T16', 'now_showing', '2026-10-16'),
('Quỷ Ăn Tạng 4: Hổ Tinh', 'Phần mới của loạt phim kinh dị Thái Lan ăn khách, tiếp tục hành trình trừ tà đối đầu thế lực quỷ dữ.', 110, 'quy-an-tang-4-ho-tinh.jpg', NULL, 'T18', 'now_showing', '2026-10-16'),
('Án Mạng Xém Hoàn Hảo', 'Phim Việt pha trộn trinh thám và hài hước xoay quanh một vụ án mạng tưởng chừng hoàn hảo.', 105, 'an-mang-xem-hoan-hao.jpg', NULL, 'T16', 'now_showing', '2026-10-09'),
('Trái Tim Quái Thú', 'Brad Pitt vào vai một cựu binh phải sinh tồn giữa vùng hoang dã cùng chú chó của mình. Đạo diễn David Ayer.', 120, 'trai-tim-quai-thu.jpg', NULL, 'T16', 'now_showing', '2026-10-02'),
('Shaun the Sheep: Quái Lạ Ghé Nhà', 'Chú cừu Shaun và những người bạn ở nông trại đón một vị khách kỳ lạ, mở ra chuỗi tình huống dở khóc dở cười.', 85, 'shaun-the-sheep-quai-la-ghe-nha.jpg', NULL, 'P', 'now_showing', '2026-10-16'),
('Thần Sư Chung Quỳ: Linh Giới Đại Chiến', 'Phim hoạt hình giả tưởng về Chung Quỳ – vị thần bắt quỷ – trong trận đại chiến nơi linh giới.', 100, 'than-su-chung-quy.jpg', NULL, 'K', 'now_showing', '2026-10-02'),
('Oán Linh Đòi Mạng', 'Phim kinh dị Indonesia: gia đình Tomy chuyển đến khu nhà mới Laddaland và bị những thế lực ma quái đeo bám. Với Titi Kamal, Andri Mashadi.', 99, 'oan-linh-doi-mang.jpg', NULL, 'T18', 'now_showing', '2026-10-23'),
('Clayface', 'Phim kinh dị thuộc vũ trụ DC kể về nguồn gốc của Clayface – kẻ có thể biến đổi hình dạng – một trong những phản diện của Batman.', 115, 'clayface.jpg', NULL, 'T18', 'coming_soon', '2026-10-30'),
('Chị Chị Em Em 3', 'Phần tiếp theo của loạt phim tâm lý Việt về những mối quan hệ đầy toan tính giữa các người phụ nữ. Đạo diễn Nguyễn Hữu Hoàng.', 110, 'chi-chi-em-em-3.jpg', NULL, 'T18', 'coming_soon', '2026-10-30');

INSERT INTO movie_genres (movie_id, genre_id) VALUES
(1,1),
(2,4),
(3,3),(3,7),
(4,1),(4,7),
(5,5),(5,3),
(6,5),(6,6),
(7,4),
(8,4),(8,6),
(9,7),(9,2);

INSERT INTO cinemas (name, address, city, phone, status) VALUES
('CineStar Quận 1', '123 Nguyễn Huệ, Quận 1', 'TP. Hồ Chí Minh', '02838123456', 'active'),
('CineStar Cầu Giấy', '45 Cầu Giấy', 'Hà Nội', '02438123456', 'active');

INSERT INTO rooms (cinema_id, name, room_type, status) VALUES
(1, 'Phòng 1', 'standard', 'active'),
(1, 'Phòng 2', 'standard', 'active'),
(2, 'Phòng 1', 'deluxe', 'active');

-- Sinh sơ đồ ghế mẫu cho từng phòng: 5 hàng (A-E) x 8 cột.
-- Hàng A,B: Thường (id=1) | Hàng C,D: VIP (id=2) | Hàng E: Đôi (id=3)
INSERT INTO seats (room_id, seat_row, seat_number, seat_type_id, status)
SELECT r.id, row_labels.seat_row, nums.n,
       CASE
           WHEN row_labels.seat_row IN ('A','B') THEN 1
           WHEN row_labels.seat_row IN ('C','D') THEN 2
           ELSE 3
       END,
       'active'
FROM rooms r
CROSS JOIN (SELECT 'A' AS seat_row UNION SELECT 'B' UNION SELECT 'C' UNION SELECT 'D' UNION SELECT 'E') row_labels
CROSS JOIN (SELECT 1 AS n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8) nums;

-- Vài suất chiếu mẫu cho phim đang chiếu (end_time = bắt đầu + thời lượng + 15 phút)
INSERT INTO showtimes (movie_id, room_id, show_date, start_time, end_time, base_price, status) VALUES
(1, 1, CURDATE(), '10:00:00', '12:14:00', 85000, 'active'),
(7, 1, CURDATE(), '14:00:00', '15:54:00', 80000, 'active'),
(1, 1, CURDATE(), '19:00:00', '21:14:00', 95000, 'active'),
(5, 2, CURDATE(), '09:30:00', '11:10:00', 70000, 'active'),
(3, 2, CURDATE(), '14:00:00', '16:00:00', 80000, 'active'),
(4, 2, CURDATE(), '19:30:00', '21:45:00', 95000, 'active'),
(6, 3, CURDATE(), '15:00:00', '16:55:00', 75000, 'active'),
(2, 3, CURDATE(), '20:00:00', '22:05:00', 90000, 'active'),
(1, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '18:00:00', '20:14:00', 95000, 'active'),
(2, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '21:00:00', '23:05:00', 90000, 'active');

-- ---------------------------------------------------------------------
-- Khách hàng + đơn vé + đánh giá mẫu (mật khẩu các khách đều là user123)
-- ---------------------------------------------------------------------
INSERT INTO users (full_name, email, password_hash, phone, role, status) VALUES
('Trần Thị Bích Ngọc', 'ngoc.tran@example.com',  '$2y$10$Aq5vE0njSBgMEEclYp7ZdedhJZOQPh5kBrm06fVTzQZPs9Z8IeItm', '0912345678', 'customer', 'active'),
('Lê Minh Khang',      'khang.le@example.com',   '$2y$10$Aq5vE0njSBgMEEclYp7ZdedhJZOQPh5kBrm06fVTzQZPs9Z8IeItm', '0987654321', 'customer', 'active'),
('Phạm Gia Huy',       'huy.pham@example.com',   '$2y$10$Aq5vE0njSBgMEEclYp7ZdedhJZOQPh5kBrm06fVTzQZPs9Z8IeItm', '0903456789', 'customer', 'active'),
('Võ Thanh Trúc',      'truc.vo@example.com',    '$2y$10$Aq5vE0njSBgMEEclYp7ZdedhJZOQPh5kBrm06fVTzQZPs9Z8IeItm', '0934567890', 'customer', 'active'),
('Đặng Quốc Bảo',      'bao.dang@example.com',   '$2y$10$Aq5vE0njSBgMEEclYp7ZdedhJZOQPh5kBrm06fVTzQZPs9Z8IeItm', '0945678901', 'customer', 'active'),
('Hoàng Mai Anh',      'maianh.hoang@example.com','$2y$10$Aq5vE0njSBgMEEclYp7ZdedhJZOQPh5kBrm06fVTzQZPs9Z8IeItm', '0956789012', 'customer', 'active');

-- Đơn vé kèm snapshot suất chiếu: tổng tiền được tính lại từ vé ở cuối
INSERT INTO bookings (user_id, showtime_id, movie_title, cinema_name, room_name, show_start, booking_code, total_amount, status, created_at)
SELECT u.id, b.showtime_id, m.title, c.name, r.name, TIMESTAMP(st.show_date, st.start_time),
       b.code, 0, b.status, NOW() - INTERVAL b.hours_ago HOUR
FROM (
    SELECT 'user@example.com' AS email, 1 AS showtime_id, 'VEDEMO0001' AS code, 'paid' AS status, 50 AS hours_ago
    UNION ALL SELECT 'ngoc.tran@example.com',    3,  'VEDEMO0002', 'paid',      30
    UNION ALL SELECT 'khang.le@example.com',     8,  'VEDEMO0003', 'paid',      26
    UNION ALL SELECT 'huy.pham@example.com',     4,  'VEDEMO0004', 'paid',      20
    UNION ALL SELECT 'truc.vo@example.com',      6,  'VEDEMO0005', 'paid',      12
    UNION ALL SELECT 'bao.dang@example.com',     2,  'VEDEMO0006', 'paid',       8
    UNION ALL SELECT 'maianh.hoang@example.com', 7,  'VEDEMO0007', 'paid',       5
    UNION ALL SELECT 'ngoc.tran@example.com',    9,  'VEDEMO0008', 'paid',       3
    UNION ALL SELECT 'khang.le@example.com',     5,  'VEDEMO0009', 'cancelled', 40
    UNION ALL SELECT 'user@example.com',        10,  'VEDEMO0010', 'paid',       1
) b
JOIN users u     ON u.email = b.email
JOIN showtimes st ON st.id = b.showtime_id
JOIN movies m    ON m.id = st.movie_id
JOIN rooms r     ON r.id = st.room_id
JOIN cinemas c   ON c.id = r.cinema_id;

-- Vé của từng đơn; giá = giá suất chiếu + phụ thu loại ghế (giống Booking::create)
INSERT INTO booking_seats (booking_id, showtime_id, seat_id, seat_label, seat_type_name, price)
SELECT bk.id, st.id, se.id, CONCAT(se.seat_row, se.seat_number), sty.name, st.base_price + sty.surcharge
FROM (
    SELECT 'VEDEMO0001' AS code, 'C' AS seat_row, 4 AS seat_number
    UNION ALL SELECT 'VEDEMO0001', 'C', 5
    UNION ALL SELECT 'VEDEMO0002', 'E', 3
    UNION ALL SELECT 'VEDEMO0002', 'E', 4
    UNION ALL SELECT 'VEDEMO0003', 'D', 4
    UNION ALL SELECT 'VEDEMO0003', 'D', 5
    UNION ALL SELECT 'VEDEMO0003', 'D', 6
    UNION ALL SELECT 'VEDEMO0004', 'A', 1
    UNION ALL SELECT 'VEDEMO0004', 'A', 2
    UNION ALL SELECT 'VEDEMO0004', 'A', 3
    UNION ALL SELECT 'VEDEMO0004', 'A', 4
    UNION ALL SELECT 'VEDEMO0005', 'C', 4
    UNION ALL SELECT 'VEDEMO0005', 'C', 5
    UNION ALL SELECT 'VEDEMO0006', 'B', 6
    UNION ALL SELECT 'VEDEMO0007', 'B', 3
    UNION ALL SELECT 'VEDEMO0007', 'B', 4
    UNION ALL SELECT 'VEDEMO0007', 'B', 5
    UNION ALL SELECT 'VEDEMO0008', 'E', 5
    UNION ALL SELECT 'VEDEMO0008', 'E', 6
    UNION ALL SELECT 'VEDEMO0009', 'D', 1
    UNION ALL SELECT 'VEDEMO0009', 'D', 2
    UNION ALL SELECT 'VEDEMO0010', 'C', 7
    UNION ALL SELECT 'VEDEMO0010', 'C', 8
) x
JOIN bookings bk    ON bk.booking_code = x.code
JOIN showtimes st   ON st.id = bk.showtime_id
JOIN seats se       ON se.room_id = st.room_id AND se.seat_row = x.seat_row AND se.seat_number = x.seat_number
JOIN seat_types sty ON sty.id = se.seat_type_id;

UPDATE bookings bk
JOIN (SELECT booking_id, SUM(price) AS total FROM booking_seats GROUP BY booking_id) s ON s.booking_id = bk.id
SET bk.total_amount = s.total
WHERE bk.booking_code LIKE 'VEDEMO%';

-- Đơn đã hủy: ghi nhận hoàn tiền, ghế không còn trong showtime_seats (đã trả lại)
UPDATE bookings
SET refund_amount = total_amount, refunded_at = NOW() - INTERVAL 36 HOUR, cancel_reason = 'Khách yêu cầu đổi suất chiếu'
WHERE status = 'cancelled' AND booking_code LIKE 'VEDEMO%';

INSERT INTO showtime_seats (showtime_id, seat_id, booking_id)
SELECT bs.showtime_id, bs.seat_id, bs.booking_id
FROM booking_seats bs JOIN bookings bk ON bk.id = bs.booking_id
WHERE bk.status = 'paid';

INSERT INTO payments (booking_id, amount, status, paid_at, refunded_at)
SELECT id, total_amount, IF(status = 'cancelled', 'refunded', 'success'), created_at, refunded_at
FROM bookings;

INSERT INTO reviews (movie_id, user_id, rating, comment)
SELECT r.movie_id, u.id, r.rating, r.comment
FROM (
    SELECT 1 AS movie_id, 'user@example.com' AS email, 5 AS rating, 'Đánh đấm đã mắt, nhạc nền rất cuốn!' AS comment
    UNION ALL SELECT 1, 'ngoc.tran@example.com',    4, 'Hành động hay, cốt truyện hơi đơn giản.'
    UNION ALL SELECT 2, 'khang.le@example.com',     5, 'Sợ thật sự, không khí rùng rợn từ đầu tới cuối.'
    UNION ALL SELECT 5, 'huy.pham@example.com',     5, 'Dẫn con đi xem, cả nhà cười suốt.'
    UNION ALL SELECT 4, 'truc.vo@example.com',      4, 'Brad Pitt diễn tốt, cảnh thiên nhiên đẹp.'
    UNION ALL SELECT 6, 'maianh.hoang@example.com', 5, 'Hình ảnh hoạt hình rất đẹp, đáng xem.'
) r
JOIN users u ON u.email = r.email;

-- =====================================================================
-- DỮ LIỆU DEMO THEO ĐẶC TẢ (giống database/migrations/002_seed_demo.sql)
-- =====================================================================

-- Thông tin chi tiết phim (UC01)
UPDATE movies SET language = 'Tiếng Anh - Phụ đề Việt' WHERE title IN ('Street Fighter', 'Trái Tim Quái Thú', 'Shaun the Sheep: Quái Lạ Ghé Nhà', 'Clayface');
UPDATE movies SET language = 'Tiếng Thái - Phụ đề Việt' WHERE title = 'Quỷ Ăn Tạng 4: Hổ Tinh';
UPDATE movies SET language = 'Tiếng Việt' WHERE title IN ('Án Mạng Xém Hoàn Hảo', 'Chị Chị Em Em 3');
UPDATE movies SET language = 'Tiếng Trung - Lồng tiếng Việt' WHERE title = 'Thần Sư Chung Quỳ: Linh Giới Đại Chiến';
UPDATE movies SET language = 'Tiếng Indonesia - Phụ đề Việt', actors = 'Titi Kamal, Andri Mashadi' WHERE title = 'Oán Linh Đòi Mạng';
UPDATE movies SET director = 'David Ayer', actors = 'Brad Pitt' WHERE title = 'Trái Tim Quái Thú';
UPDATE movies SET director = 'Nguyễn Hữu Hoàng' WHERE title = 'Chị Chị Em Em 3';

-- Phim T13, thời lượng 94 phút (dùng cho ca kiểm thử trùng lịch biên 20:48 / 20:49)
INSERT INTO movies (title, description, director, actors, language, duration_minutes, poster_path, trailer_url, age_rating, status, release_date)
VALUES ('Hành Tinh Cát Đỏ', 'Phi hành đoàn cuối cùng của trạm nghiên cứu trên sao Hỏa phải tìm đường về Trái Đất khi bão cát phá hủy tàu tiếp tế.',
        'Trần Minh Quân', 'Lê Hoàng, Mai Thu', 'Tiếng Việt', 94, NULL, NULL, 'T13', 'now_showing', CURDATE() - INTERVAL 7 DAY);
SET @movie_t13 = LAST_INSERT_ID();
INSERT INTO movie_genres (movie_id, genre_id) SELECT @movie_t13, id FROM genres WHERE name IN ('Viễn tưởng', 'Hành động');

-- Phòng demo có lối đi ở cột 5 và 1 ghế ngừng sử dụng
INSERT INTO rooms (cinema_id, name, room_type, status) VALUES (1, 'Phòng 3', 'deluxe', 'active');
SET @room_demo = LAST_INSERT_ID();

INSERT INTO seats (room_id, seat_row, seat_number, seat_type_id, status)
SELECT @room_demo, rws.seat_row, nums.n,
       CASE WHEN rws.seat_row IN ('A','B','C') THEN (SELECT id FROM seat_types WHERE name = 'Thường')
            WHEN rws.seat_row IN ('D','E')     THEN (SELECT id FROM seat_types WHERE name = 'VIP')
            ELSE (SELECT id FROM seat_types WHERE name = 'Đôi') END,
       CASE WHEN nums.n = 5 THEN 'aisle'
            WHEN rws.seat_row = 'A' AND nums.n = 1 THEN 'inactive'
            ELSE 'active' END
FROM (SELECT 'A' AS seat_row UNION SELECT 'B' UNION SELECT 'C' UNION SELECT 'D' UNION SELECT 'E' UNION SELECT 'F') rws
CROSS JOIN (SELECT 1 AS n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
            UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) nums;

-- Suất 1: đã chiếu xong (bắt đầu 4 giờ trước) -> dùng để demo đánh giá phim
SET @movie_done = (SELECT id FROM movies WHERE title = 'Shaun the Sheep: Quái Lạ Ghé Nhà');
SET @start_done = DATE_FORMAT(NOW() - INTERVAL 4 HOUR, '%Y-%m-%d %H:%i:00');
INSERT INTO showtimes (movie_id, room_id, show_date, start_time, end_time, base_price, status)
SELECT @movie_done, @room_demo, DATE(@start_done), TIME(@start_done),
       TIME(@start_done + INTERVAL (duration_minutes + 15) MINUTE), 75000, 'active'
FROM movies WHERE id = @movie_done;
SET @st_done = LAST_INSERT_ID();

-- Suất 2: phim T18, bắt đầu sau 30 phút -> 15 phút nữa sẽ đóng bán
SET @movie_t18 = (SELECT id FROM movies WHERE title = 'Oán Linh Đòi Mạng');
SET @start_soon = DATE_FORMAT(NOW() + INTERVAL 30 MINUTE, '%Y-%m-%d %H:%i:00');
INSERT INTO showtimes (movie_id, room_id, show_date, start_time, end_time, base_price, status)
SELECT @movie_t18, @room_demo, DATE(@start_soon), TIME(@start_soon),
       TIME(@start_soon + INTERVAL (duration_minutes + 15) MINUTE), 75000, 'active'
FROM movies WHERE id = @movie_t18;

-- Suất 3: phim T13 ngày mai 19:00
INSERT INTO showtimes (movie_id, room_id, show_date, start_time, end_time, base_price, status)
VALUES (@movie_t13, @room_demo, CURDATE() + INTERVAL 1 DAY, '19:00:00', '20:49:00', 75000, 'active');

-- Đơn của user@example.com cho suất đã chiếu xong (ghế D2 VIP + F3 Đôi)
SET @demo_user = (SELECT id FROM users WHERE email = 'user@example.com');
INSERT INTO bookings (user_id, showtime_id, movie_title, cinema_name, room_name, show_start, booking_code, total_amount, status, created_at)
SELECT @demo_user, @st_done, m.title, c.name, r.name, @start_done, 'VEDEMO0011', 0, 'paid', @start_done - INTERVAL 1 DAY
FROM showtimes st JOIN movies m ON m.id = st.movie_id JOIN rooms r ON r.id = st.room_id JOIN cinemas c ON c.id = r.cinema_id
WHERE st.id = @st_done;
SET @bk_done = LAST_INSERT_ID();

INSERT INTO booking_seats (booking_id, showtime_id, seat_id, seat_label, seat_type_name, price)
SELECT @bk_done, @st_done, s.id, CONCAT(s.seat_row, s.seat_number), sty.name, 75000 + sty.surcharge
FROM seats s JOIN seat_types sty ON sty.id = s.seat_type_id
WHERE s.room_id = @room_demo AND ((s.seat_row = 'D' AND s.seat_number = 2) OR (s.seat_row = 'F' AND s.seat_number = 3));

INSERT INTO showtime_seats (showtime_id, seat_id, booking_id)
SELECT showtime_id, seat_id, booking_id FROM booking_seats WHERE booking_id = @bk_done;

UPDATE bookings SET total_amount = (SELECT SUM(price) FROM booking_seats WHERE booking_id = @bk_done) WHERE id = @bk_done;

INSERT INTO payments (booking_id, amount, status, paid_at)
SELECT id, total_amount, 'success', created_at FROM bookings WHERE id = @bk_done;
