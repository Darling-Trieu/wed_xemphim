-- =====================================================================
-- Migration 003: thêm suất chiếu mẫu cho 7 ngày tới (hôm nay -> +7 ngày) để test nhiều ngày.
-- Hôm nay chỉ lấy các khung giờ còn mở bán (bắt đầu sau ít nhất 15 phút nữa).
-- Mỗi phòng đang hoạt động có 6 suất / ngày: 09:00, 11:30, 14:00, 16:30, 19:00, 21:30;
-- phim Đang chiếu được xoay vòng giữa các phòng / khung giờ.
-- Giá: sáng 70.000đ, chiều 80.000đ, tối 95.000đ; thứ 7 / chủ nhật +10.000đ.
-- Khung giờ đã có suất trong phòng (trùng giờ theo BR02) được bỏ qua, nên chạy lại file
-- này không tạo suất trùng. Ngày bắt đầu tính theo CURDATE() lúc chạy.
--   mysql -u root --default-character-set=utf8mb4 movie_booking < database\migrations\003_seed_showtimes.sql
-- =====================================================================

INSERT INTO showtimes (movie_id, room_id, show_date, start_time, end_time, base_price, status)
WITH RECURSIVE days AS (
    SELECT 0 AS d
    UNION ALL SELECT d + 1 FROM days WHERE d < 7
),
slots AS (
    SELECT 0 AS slot_no, '09:00:00' AS t
    UNION ALL SELECT 1, '11:30:00'
    UNION ALL SELECT 2, '14:00:00'
    UNION ALL SELECT 3, '16:30:00'
    UNION ALL SELECT 4, '19:00:00'
    UNION ALL SELECT 5, '21:30:00'
),
-- Các khung cách nhau 150 phút: chỉ lấy phim chiếm phòng (thời lượng + 15' dọn phòng) <= 150'
mv AS (
    SELECT id, duration_minutes,
           ROW_NUMBER() OVER (ORDER BY id) - 1 AS rn,
           COUNT(*) OVER () AS cnt
    FROM movies
    WHERE status = 'now_showing' AND duration_minutes + 15 <= 150
),
rm AS (
    SELECT r.id, ROW_NUMBER() OVER (ORDER BY r.id) - 1 AS rn
    FROM rooms r
    INNER JOIN cinemas c ON c.id = r.cinema_id
    WHERE r.status = 'active' AND c.status = 'active'
),
plan AS (
    SELECT rm.id AS room_id, slots.slot_no,
           CURDATE() + INTERVAL days.d DAY AS show_date,
           TIMESTAMP(CURDATE() + INTERVAL days.d DAY, slots.t) AS starts_at,
           days.d * 5 + rm.rn * 2 + slots.slot_no AS k
    FROM days CROSS JOIN slots CROSS JOIN rm
)
SELECT mv.id, plan.room_id, plan.show_date, TIME(plan.starts_at),
       TIME(plan.starts_at + INTERVAL (mv.duration_minutes + 15) MINUTE),
       CASE WHEN plan.slot_no < 2 THEN 70000 WHEN plan.slot_no < 4 THEN 80000 ELSE 95000 END
           + IF(DAYOFWEEK(plan.show_date) IN (1, 7), 10000, 0),
       'active'
FROM plan
INNER JOIN mv ON mv.rn = plan.k MOD mv.cnt
WHERE plan.starts_at > NOW() + INTERVAL 15 MINUTE
  AND NOT EXISTS (
    SELECT 1 FROM showtimes st
    WHERE st.room_id = plan.room_id AND st.status = 'active'
      AND TIMESTAMP(st.show_date, st.start_time) < plan.starts_at + INTERVAL (mv.duration_minutes + 15) MINUTE
      AND TIMESTAMP(st.show_date, st.end_time) + INTERVAL IF(st.end_time <= st.start_time, 1, 0) DAY > plan.starts_at
);
