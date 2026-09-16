# Website đặt vé xem phim

Đồ án website đặt vé xem phim trực tuyến: PHP thuần (MVC tự viết) + MySQL + TypeScript + HTML/CSS, chạy trên Laragon.

## Yêu cầu môi trường

- [Laragon](https://laragon.org/) (Apache + PHP 8.1+ + MySQL) — đã cài sẵn theo máy.
- [Node.js](https://nodejs.org/) (để biên dịch TypeScript bằng `tsc`).

## Cài đặt

1. **Đặt mã nguồn vào thư mục Laragon**, ví dụ: `C:\laragon\www\movie-booking` (thư mục dự án chính là thư mục chứa `public/`, `src/`...).

2. **Tạo CSDL**: mở HeidiSQL (đi kèm Laragon) hoặc dùng lệnh:
   ```
   mysql -u root < database/schema.sql
   ```
   Lệnh trên tự tạo database `movie_booking`, toàn bộ bảng và dữ liệu mẫu.

3. **Cấu hình kết nối CSDL** tại `src/config/database.php` nếu MySQL của bạn khác mặc định Laragon (user `root`, không mật khẩu, cổng `3306`).

4. **Khởi động Laragon** (Start All). Laragon tự nhận diện thư mục `public/` bên trong dự án và tạo virtual host trỏ đúng vào đó — truy cập qua `http://<ten-thu-muc-du-an>.test` (xem trong Laragon > Menu > www).

   Nếu không dùng auto vhost, có thể chạy PHP dev server để thử nhanh:
   ```
   php -S localhost:8000 -t public
   ```

5. **Biên dịch TypeScript** (chỉ cần chạy lại mỗi khi sửa file trong `ts/`):
   ```
   npm install
   npm run build      # biên dịch 1 lần, xuất ra public/js/
   npm run watch       # hoặc: tự biên dịch lại khi có thay đổi
   ```

## Tài khoản mẫu

| Vai trò | Email | Mật khẩu |
|---|---|---|
| Admin | admin@cinema.com | admin123 |
| Khách hàng | user@example.com | user123 |

## Cấu trúc thư mục

```
public/       Web root (Laragon docroot) — front controller, CSS, JS đã biên dịch, ảnh upload
src/          Mã nguồn PHP: config, core (Router/Auth/...), controllers, models, views
ts/           Mã nguồn TypeScript (sơ đồ ghế, biểu đồ doanh thu admin)
database/     schema.sql — DDL + dữ liệu mẫu
```

## Ghi chú thiết kế

- Thanh toán là **giả lập**: vé được đánh dấu "đã thanh toán" ngay sau khi xác nhận đặt vé, không tích hợp cổng thanh toán thật.
- Ghế được khóa **ngay khi bấm "Đặt vé"**: server kiểm tra ghế còn trống trong 1 transaction rồi ghi vào CSDL. Ràng buộc `UNIQUE(showtime_id, seat_id)` trên bảng `booking_seats` là lớp bảo vệ cuối cùng chống 2 người đặt trùng ghế cùng lúc.
- Không dùng framework PHP — Router/Controller/Model/Auth/Csrf là các lớp nhẹ tự viết trong `src/core/`.
