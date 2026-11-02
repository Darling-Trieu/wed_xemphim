-- =====================================================================
-- Migration 002: bổ sung dữ liệu demo theo đặc tả (chạy sau 001).
-- Thời gian suất chiếu tính theo NOW() lúc chạy file, nên chạy lại ngay trước buổi demo
-- trên CSDL mới import nếu cần "suất sắp đóng bán".
--   mysql -u root --default-character-set=utf8mb4 movie_booking < database\migrations\002_seed_demo.sql
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
