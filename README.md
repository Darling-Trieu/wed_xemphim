# Website đặt vé xem phim

Đồ án website đặt vé xem phim trực tuyến: PHP thuần (MVC tự viết) + MySQL + TypeScript + HTML/CSS, chạy trên Laragon.

## Yêu cầu môi trường

- [Laragon](https://laragon.org/) (Apache + PHP 8.1+ + MySQL) — đã cài sẵn theo máy.
- [Node.js](https://nodejs.org/) (để biên dịch TypeScript bằng `tsc`).

## Cài đặt

1. **Đặt mã nguồn vào thư mục Laragon**, ví dụ: `C:\laragon\www\movie-booking` (thư mục dự án chính là thư mục chứa `public/`, `src/`...).

2. **Tạo CSDL**: mở HeidiSQL (đi kèm Laragon) hoặc dùng lệnh:
   ```
   mysql -u root --default-character-set=utf8mb4 < database/schema.sql
   ```
   Lệnh trên tự tạo database `movie_booking`, toàn bộ bảng và dữ liệu mẫu.
   **Lưu ý:** file này xóa database `movie_booking` cũ (nếu có) trước khi tạo lại, mọi dữ liệu đang có sẽ mất.

   Dữ liệu mẫu có sẵn suất chiếu cho 7 ngày tới (mỗi phòng 6 suất / ngày). Muốn thêm suất cho
   CSDL đang dùng mà không import lại (ví dụ vài ngày sau khi các suất mẫu đã qua), chạy:
   ```
   mysql -u root --default-character-set=utf8mb4 movie_booking < database/migrations/003_seed_showtimes.sql
   ```
   Khung giờ đã có suất được bỏ qua nên chạy lại nhiều lần không bị trùng lịch.

   Nếu đang dùng CSDL cũ (không muốn import lại), chạy các migration còn thiếu **theo thứ tự**
   (nên sao lưu trước bằng `mysqldump`):
   ```
   mysql -u root --default-character-set=utf8mb4 movie_booking < database/migrations/001_spec_alignment.sql
   mysql -u root --default-character-set=utf8mb4 movie_booking < database/migrations/002_seed_demo.sql
   mysql -u root --default-character-set=utf8mb4 movie_booking < database/migrations/003_seed_showtimes.sql
   mysql -u root --default-character-set=utf8mb4 movie_booking < database/migrations/004_create_seat_holds.sql
   ```
   Thiếu bảng của migration nào thì các trang liên quan báo "Lỗi hệ thống" (chi tiết ghi ở `logs/app.log`).

3. **Cấu hình kết nối CSDL** tại `src/config/database.php` nếu MySQL của bạn khác mặc định Laragon (user `root`, không mật khẩu, cổng `3306`).

4. **Cấu hình gửi email**: hệ thống dùng hàm `mail()` của PHP để gửi email xác nhận sau khi đặt vé. Cấu hình SMTP trong `php.ini` (với Laragon, thường là `sendmail_path`) và có thể đặt biến môi trường `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`.

5. **Khởi động Laragon** (Start All). Laragon tự nhận diện thư mục `public/` bên trong dự án và tạo virtual host trỏ đúng vào đó — truy cập qua `http://<ten-thu-muc-du-an>.test` (xem trong Laragon > Menu > www).

   Nếu không dùng auto vhost, có thể chạy PHP dev server để thử nhanh:
   ```
   php -S localhost:8000 -t public
   ```

6. **Biên dịch TypeScript** (chỉ cần chạy lại mỗi khi sửa file trong `ts/`):
   ```
   npm install
   npm run build      # biên dịch 1 lần, xuất ra public/js/
   npm run watch       # hoặc: tự biên dịch lại khi có thay đổi
   ```

## Tài khoản mẫu

| Vai trò | Email | Mật khẩu |
|---|---|---|
| Admin | admin@cinema.com | admin123 |
| Khách hàng | user@example.com (hoặc SĐT 0911111111) | user123 |
| Khách hàng 2 (demo 2 phiên) | ngoc.tran@example.com | user123 |

## Kiểm thử và tài liệu

- Kiểm thử tự động (cần MySQL đang chạy; tự tạo CSDL riêng `movie_booking_test`, không đụng CSDL thật):
  ```
  php tests/run_tests.php
  ```
- [docs/RTM.md](docs/RTM.md) — ma trận truy vết yêu cầu FR / BR / NFR ↔ ca kiểm thử, kết quả Đạt / Không đạt.
- [docs/DEMO.md](docs/DEMO.md) — kịch bản demo 3 luồng bắt buộc (tạo phòng → phim → suất; đặt vé; 2 phiên cùng 1 ghế).

## Cấu trúc thư mục

```
public/       Web root (Laragon docroot) — front controller, API JSON, CSS, JS đã biên dịch, ảnh upload
src/          Mã nguồn PHP: config, core (Router/Auth/Csrf/helpers...), controllers, models, views
ts/           Mã nguồn TypeScript (sơ đồ ghế + giữ ghế, biểu đồ thống kê admin)
database/     schema.sql (DDL + dữ liệu mẫu), migrations/ cho CSDL cũ
tests/        Kiểm thử tự động
docs/         RTM, kịch bản demo
```

## Ghi chú thiết kế

- Thanh toán là **giả lập**: đơn được xác nhận và ghi thanh toán "Thành công" ngay khi bấm Xác nhận; hủy đơn → "Đã hoàn".
- **Giữ ghế 5 phút**: chọn ghế là giữ ghế cho session (bảng `seat_holds`), tối đa 8 ghế / phiên; đăng nhập giữa chừng vẫn giữ ghế.
- **Chống bán trùng**: đặt vé chạy trong 1 transaction; ràng buộc `UNIQUE(showtime_id, seat_id)` trên bảng `showtime_seats` là chốt chặn cuối cùng khi 2 người xác nhận cùng lúc.
- **Snapshot vé**: đơn và vé lưu tên phim, giờ chiếu, phòng, nhãn ghế, loại ghế, giá lúc đặt.
- Không dùng framework PHP — Router/Controller/Model/Auth/Csrf là các lớp nhẹ tự viết trong `src/core/`.
