# Cinema Booking – Hệ thống đặt vé xem phim (Nhóm 4)

Website tra cứu phim và lịch chiếu, chọn ghế, đặt vé; kèm khu vực quản trị phim, rạp / phòng, sơ đồ ghế,
suất chiếu, đơn vé, tài khoản và thống kê. Trọng tâm nghiệp vụ: **không chồng lịch chiếu trong cùng phòng**
và **không bán trùng ghế** kể cả khi nhiều người đặt cùng lúc.

**Công nghệ:** PHP 8.1 thuần (MVC tự viết, không framework) · MySQL 8 (InnoDB) · TypeScript · HTML/CSS · chạy trên Laragon.

## Chức năng

| Vai trò | Chức năng |
|---|---|
| **Khách (Guest)** | Xem phim Đang chiếu / Sắp chiếu và chi tiết phim · tra cứu suất theo phim, rạp, phòng, ngày (7 ngày tới, có số ghế trống) · xem sơ đồ ghế và chọn ghế (được yêu cầu đăng nhập khi đặt, ghế đã chọn được giữ nguyên) · đăng ký / đăng nhập |
| **Người dùng (User)** | Đặt vé 1–8 ghế (giữ ghế 5 phút, màn hình xác nhận, xác nhận độ tuổi phim T13/T16/T18) · nhận mã booking và email xác nhận · xem "Vé của tôi" · đánh giá phim sau khi xem xong · sửa hồ sơ, đổi mật khẩu |
| **Quản trị (Admin)** | Quản lý phim, thể loại, rạp, phòng chiếu, sơ đồ ghế (loại ghế, lối đi, ghế ngừng sử dụng), loại ghế & phụ thu · tạo / sửa / hủy suất chiếu (chặn trùng giờ) · theo dõi và hủy đơn (hoàn tiền mô phỏng) · khóa / mở khóa tài khoản · thống kê vé / doanh thu theo ngày, phim, rạp, phòng · xem nhật ký hệ thống |

## Cài đặt nhanh (CSDL mới)

Yêu cầu: [Laragon](https://laragon.org/) (Apache + PHP 8.1+ + MySQL 8), [Node.js](https://nodejs.org/) nếu cần sửa TypeScript.

1. **Đặt mã nguồn** vào thư mục `www` của Laragon, ví dụ `C:\laragon\www\movie-booking` (thư mục chứa `public/`, `src/`).
2. **Bật Laragon** (Start All), rồi **tạo CSDL + dữ liệu mẫu** bằng HeidiSQL hoặc lệnh:
   ```
   mysql -u root --default-character-set=utf8mb4 < database/schema.sql
   ```
   > ⚠️ File này **xóa database `movie_booking` cũ** (nếu có) rồi tạo lại từ đầu.
3. **Mở website**: Laragon tự tạo virtual host trỏ vào `public/` → `http://<ten-thu-muc>.test` (Laragon → Menu → www).
   Không dùng vhost thì chạy server thử nghiệm của PHP:
   ```
   php -S localhost:8000 -t public
   ```

JavaScript đã được biên dịch sẵn trong `public/js/`. Chỉ khi sửa file trong `ts/` mới cần:
```
npm install
npm run build      # biên dịch 1 lần ra public/js/
npm run watch      # tự biên dịch lại khi sửa
```

## Nâng cấp CSDL đang dùng

Không muốn import lại (giữ dữ liệu cũ) thì sao lưu trước rồi chạy các migration còn thiếu **theo thứ tự**:

```
mysqldump -u root movie_booking > movie_booking_backup.sql

mysql -u root --default-character-set=utf8mb4 movie_booking < database/migrations/001_spec_alignment.sql
mysql -u root --default-character-set=utf8mb4 movie_booking < database/migrations/002_seed_demo.sql
mysql -u root --default-character-set=utf8mb4 movie_booking < database/migrations/003_seed_showtimes.sql
mysql -u root --default-character-set=utf8mb4 movie_booking < database/migrations/004_create_seat_holds.sql
```

| Migration | Nội dung |
|---|---|
| `001_spec_alignment.sql` | Bảng ghế-theo-suất (`showtime_seats`), thanh toán, nhật ký, snapshot vé, phụ thu loại ghế, đánh giá 1 lần / phim |
| `002_seed_demo.sql` | Phim T13, phòng demo có lối đi, suất sắp đóng bán, suất đã chiếu xong có vé |
| `003_seed_showtimes.sql` | Sinh suất chiếu cho hôm nay + 7 ngày tới (chạy lại nhiều lần không tạo suất trùng) |
| `004_create_seat_holds.sql` | Bảng giữ ghế tạm `seat_holds` |

## Cấu hình

| Mục | Vị trí | Mặc định |
|---|---|---|
| Kết nối CSDL | `src/config/database.php` | `127.0.0.1:3306`, user `root`, không mật khẩu, CSDL `movie_booking` |
| Đường dẫn gốc | `BASE_URL` trong `src/config/config.php` | `''` (site ở gốc domain). Chạy trong thư mục con thì đổi, vd `'/movie-booking/public'` |
| Email xác nhận | `php.ini` (Laragon: `sendmail_path`), biến môi trường `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Gửi bằng `mail()`; chưa cấu hình thì vẫn đặt vé được, chỉ báo chưa gửi được email |
| Quy tắc đặt vé | `src/config/config.php` | Đóng bán trước 15 phút, tối đa 8 ghế / đơn, dọn phòng 15 phút |

## Tài khoản và dữ liệu mẫu

| Vai trò | Đăng nhập (email hoặc SĐT) | Mật khẩu |
|---|---|---|
| Admin | `admin@cinema.com` | `admin123` |
| Khách hàng | `user@example.com` / `0911111111` | `user123` |
| Khách hàng 2 (demo 2 phiên) | `ngoc.tran@example.com` | `user123` |

Dữ liệu mẫu gồm 10 phim (đủ nhãn P, K, T13, T16, T18), 2 rạp, 4 phòng có sơ đồ ghế Thường / VIP / Đôi,
khoảng 190 suất chiếu trong 7 ngày tính từ lúc import, 11 đơn vé và một số đánh giá.

## Kiểm thử và tài liệu

```
php tests/run_tests.php
```

Cần MySQL đang chạy. Script tự tạo CSDL riêng `movie_booking_test` (không đụng dữ liệu thật), bật 2 server
`php -S` (cổng 8099, 8100) và chạy 25 ca kiểm thử / 170 kiểm tra: đặt trùng ghế song song, trùng suất biên,
suất đóng bán, số ghế biên, tính giá theo loại ghế, snapshot, phân quyền, CSRF, thống kê...

- [docs/RTM.md](docs/RTM.md) – ma trận truy vết yêu cầu FR / quy tắc nghiệp vụ / NFR ↔ ca kiểm thử, kết quả Đạt / Không đạt.
- [docs/DEMO.md](docs/DEMO.md) – kịch bản demo 3 luồng bắt buộc: tạo phòng → phim → suất; đặt vé; hai phiên cùng chọn một ghế.

## Cấu trúc thư mục

```
public/                 Web root: index.php (front controller), api.php (API JSON), css/, js/, asset/, uploads/
src/
  config/               config.php (hằng số, session), database.php
  core/                 Router, Controller, Model, Database, Auth, Csrf, ErrorHandler, Mailer, helpers
  controllers/          Trang khách: Home, Movie, Showtime, Booking, Auth, Account
  controllers/admin/    Trang quản trị: Dashboard, Movie, Genre, Cinema, Room, SeatType, Showtime, Ticket, Customer, Log
  models/               Truy vấn CSDL (prepared statement) và quy tắc nghiệp vụ: Booking, SeatHold, Showtime, Report...
  views/                Giao diện PHP (layouts/, partials/, theo từng trang)
  routes.php            Khai báo URL → Controller@action
ts/                     Mã nguồn TypeScript: sơ đồ ghế + giữ ghế, biểu đồ thống kê, menu
database/               schema.sql (tạo mới + dữ liệu mẫu), migrations/ (nâng cấp CSDL cũ)
tests/                  Kiểm thử tự động
docs/                   RTM, kịch bản demo
logs/                   app.log – chi tiết lỗi hệ thống (không đưa lên git)
```

## Quy tắc nghiệp vụ và thiết kế chính

- **Một ghế / một suất chỉ bán một lần.** Đặt vé chạy trong một transaction: kiểm tra suất còn mở bán, ghế
  thuộc đúng phòng và còn trống, tính lại giá ở server, tạo đơn + vé + thanh toán. Ràng buộc
  `UNIQUE(showtime_id, seat_id)` trên bảng `showtime_seats` là chốt chặn cuối: request đến sau bị rollback và nhận
  thông báo *"Ghế … đã có người đặt"*.
- **Giữ ghế 5 phút.** Chọn ghế là giữ ghế cho phiên (`seat_holds`), người khác thấy "Đang được giữ"; tối đa 8 ghế /
  phiên; đăng nhập giữa chừng vẫn giữ ghế; hết 5 phút tự nhả.
- **Không chồng lịch.** Suất chiếm phòng từ giờ bắt đầu đến giờ bắt đầu + thời lượng phim + 15 phút dọn phòng;
  hai suất cùng phòng trùng nhau khi `startA < endB AND startB < endA` (bỏ qua suất đã hủy).
- **Đóng bán trước 15 phút** giờ chiếu; suất đã bắt đầu không đặt được.
- **Giá ghế = giá cơ bản của suất + phụ thu loại ghế** (vd 75.000đ: Thường 75.000đ, VIP 85.000đ, Đôi 135.000đ).
- **Snapshot vé.** Đơn và vé lưu tên phim, giờ chiếu, phòng, nhãn ghế, loại ghế, giá tại thời điểm đặt; admin sửa
  phim / suất / giá sau đó không làm đổi lịch sử.
- **Không xóa cứng dữ liệu đã dùng.** Phim / phòng / rạp đã có suất chỉ chuyển trạng thái Ngừng; suất chỉ hủy được
  khi chưa có vé; ghế đã có vé không đổi thành lối đi.
- **Thanh toán mô phỏng.** Xác nhận đặt vé → thanh toán "Thành công"; admin hủy đơn (bắt buộc lý do, chỉ khi suất
  chưa chiếu) → "Đã hoàn" và ghế trống lại.
- **Bảo mật.** Mật khẩu `password_hash`, đổi session ID khi đăng nhập, prepared statement cho mọi truy vấn,
  `htmlspecialchars` khi in dữ liệu, CSRF token cho mọi form / API thay đổi dữ liệu, kiểm tra quyền ở server,
  tài khoản bị khóa bị đăng xuất ở request kế tiếp, lỗi CSDL không lộ câu SQL.
- **Nhật ký.** Đăng nhập (thành công / thất bại) và mọi thao tác quản trị được ghi lại kèm dữ liệu trước / sau;
  admin chỉ xem, không sửa / xóa được.

## Xử lý sự cố thường gặp

| Hiện tượng | Nguyên nhân / cách xử lý |
|---|---|
| Trang báo "Lỗi hệ thống, vui lòng thử lại" | Xem `logs/app.log`. Thường do CSDL cũ thiếu bảng → chạy các migration ở mục *Nâng cấp CSDL* |
| Lịch chiếu trống / "Chưa có lịch chiếu cho lựa chọn này" | Suất mẫu đã qua ngày → chạy lại `database/migrations/003_seed_showtimes.sql` (hoặc import lại `schema.sql`) |
| Đặt vé xong báo "chưa gửi được email" | Chưa cấu hình `sendmail` / SMTP của PHP; đơn vẫn được tạo bình thường |
| Lệnh `mysql` báo `Unknown OS character set 'cp1258'` | Chỉ là cảnh báo của Windows, luôn thêm `--default-character-set=utf8mb4` |
| Sửa `ts/*.ts` nhưng trình duyệt không đổi | Chạy `npm run build` và tải lại trang bằng Ctrl+F5 |
