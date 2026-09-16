-- =====================================================================
-- CSDL hệ thống đặt vé xem phim trực tuyến
-- Import file này vào MySQL (Laragon -> HeidiSQL, hoặc: mysql -u root < schema.sql)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS movie_booking
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE movie_booking;

-- ---------------------------------------------------------------------
-- Người dùng (khách hàng + quản trị viên)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(150)    NOT NULL,
    email         VARCHAR(150)    NOT NULL UNIQUE,
    password_hash VARCHAR(255)    NOT NULL,
    phone         VARCHAR(20)     NULL,
    role          ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    status        ENUM('active','locked')  NOT NULL DEFAULT 'active',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
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
    title            VARCHAR(255) NOT NULL,
    description      TEXT NULL,
    duration_minutes SMALLINT UNSIGNED NOT NULL,
    poster_path      VARCHAR(255) NULL,
    trailer_url      VARCHAR(255) NULL,
    age_rating       VARCHAR(10) NOT NULL DEFAULT 'P',
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
    status    ENUM('active','inactive') NOT NULL DEFAULT 'active',
    FOREIGN KEY (cinema_id) REFERENCES cinemas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE seat_types (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(50) NOT NULL,
    price_multiplier DECIMAL(4,2) NOT NULL DEFAULT 1.00
) ENGINE=InnoDB;

CREATE TABLE seats (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_id      INT UNSIGNED NOT NULL,
    seat_row     VARCHAR(5)  NOT NULL,
    seat_number  INT UNSIGNED NOT NULL,
    seat_type_id INT UNSIGNED NOT NULL,
    status       ENUM('active','inactive') NOT NULL DEFAULT 'active',
    UNIQUE KEY uq_room_seat (room_id, seat_row, seat_number),
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (seat_type_id) REFERENCES seat_types(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Suất chiếu
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
-- Đặt vé
-- ---------------------------------------------------------------------
CREATE TABLE bookings (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    showtime_id  INT UNSIGNED NOT NULL,
    booking_code VARCHAR(20) NOT NULL UNIQUE,
    total_amount DECIMAL(12,0) NOT NULL,
    status       ENUM('paid','cancelled') NOT NULL DEFAULT 'paid',
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (showtime_id) REFERENCES showtimes(id)
) ENGINE=InnoDB;

-- Ghế đã bán cho từng suất chiếu. UNIQUE(showtime_id, seat_id) là chốt chặn
-- ở tầng CSDL để 2 request đặt vé cùng lúc không thể trùng ghế.
CREATE TABLE booking_seats (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id  INT UNSIGNED NOT NULL,
    showtime_id INT UNSIGNED NOT NULL,
    seat_id     INT UNSIGNED NOT NULL,
    price       DECIMAL(10,0) NOT NULL,
    UNIQUE KEY uq_showtime_seat (showtime_id, seat_id),
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (showtime_id) REFERENCES showtimes(id),
    FOREIGN KEY (seat_id) REFERENCES seats(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Đánh giá phim
-- ---------------------------------------------------------------------
CREATE TABLE reviews (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    movie_id   INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    rating     TINYINT UNSIGNED NOT NULL,
    comment    TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5)
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

INSERT INTO seat_types (name, price_multiplier) VALUES
('Thường', 1.00), ('VIP', 1.50), ('Đôi', 2.00);

INSERT INTO movies (title, description, duration_minutes, poster_path, trailer_url, age_rating, status, release_date) VALUES
('Hành Trình Vô Định', 'Một cuộc phiêu lưu hành động nghẹt thở xuyên qua nhiều vùng đất nguy hiểm.', 128, NULL, NULL, 'T16', 'now_showing', '2026-08-01'),
('Nụ Cười Mùa Hạ', 'Câu chuyện tình cảm nhẹ nhàng giữa hai người trẻ trong mùa hè định mệnh.', 105, NULL, NULL, 'T13', 'now_showing', '2026-07-15'),
('Lời Nguyền Căn Nhà Cũ', 'Bộ phim kinh dị ám ảnh về một căn nhà bỏ hoang mang bí mật đen tối.', 98, NULL, NULL, 'T18', 'now_showing', '2026-08-20'),
('Vũ Trụ Song Song', 'Hành trình viễn tưởng khám phá những chiều không gian chưa từng biết đến.', 132, NULL, NULL, 'T13', 'coming_soon', '2026-10-10'),
('Chú Mèo Tinh Nghịch', 'Phim hoạt hình gia đình vui nhộn dành cho mọi lứa tuổi.', 90, NULL, NULL, 'P', 'coming_soon', '2026-10-25');

INSERT INTO movie_genres (movie_id, genre_id) VALUES
(1,1),(1,6),
(2,2),
(3,4),
(4,6),
(5,5),(5,3);

INSERT INTO cinemas (name, address, city, phone, status) VALUES
('CineStar Quận 1', '123 Nguyễn Huệ, Quận 1', 'TP. Hồ Chí Minh', '02838123456', 'active'),
('CineStar Cầu Giấy', '45 Cầu Giấy', 'Hà Nội', '02438123456', 'active');

INSERT INTO rooms (cinema_id, name, status) VALUES
(1, 'Phòng 1', 'active'),
(1, 'Phòng 2', 'active'),
(2, 'Phòng 1', 'active');

-- Sinh sơ đồ ghế mẫu cho từng phòng: 5 hàng (A-E) x 8 cột.
-- Hàng A,B: Thường (id=1) | Hàng C,D: VIP (id=2) | Hàng E: Đôi (id=3, ghép cặp)
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

-- Vài suất chiếu mẫu cho phim đang chiếu (điều chỉnh ngày theo nhu cầu demo)
INSERT INTO showtimes (movie_id, room_id, show_date, start_time, end_time, base_price, status) VALUES
(1, 1, CURDATE(), '10:00:00', '12:15:00', 85000, 'active'),
(1, 1, CURDATE(), '19:00:00', '21:15:00', 95000, 'active'),
(2, 2, CURDATE(), '14:00:00', '15:50:00', 80000, 'active'),
(3, 3, CURDATE(), '20:00:00', '21:45:00', 90000, 'active'),
(1, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '18:00:00', '20:15:00', 95000, 'active');
