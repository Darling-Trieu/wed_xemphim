# Đưa website lên InfinityFree (hosting PHP + MySQL miễn phí)

Kết quả: website chạy tại địa chỉ dạng `https://ten-ban-chon.rf.gd`, dùng được cho buổi demo.

## Bước 1 – Chuẩn bị trên máy (Laragon đang bật MySQL)

```
php deploy/build.php
```

Tạo ra 2 file trong `deploy/dist/` (thư mục này không đưa lên git):

| File | Dùng để |
|---|---|
| `hosting.sql` | Import vào CSDL trên hosting (bảng + dữ liệu mẫu, suất chiếu tính từ ngày chạy lệnh) |
| `cinema-booking.zip` | Mã nguồn cần upload: `.htaccess`, `public/`, `src/`, `logs/` |

> Suất chiếu mẫu tính theo ngày chạy lệnh (hôm nay + 7 ngày). Nên chạy lệnh này ngay trước khi import.

## Bước 2 – Tạo hosting và CSDL

1. Đăng ký tại <https://www.infinityfree.com> → **Create Account** → chọn tên miền phụ miễn phí (vd `cinema-nhom4.rf.gd`).
2. Vào **Control Panel** của tài khoản hosting → **PHP Config** (Alter PHP Config): chọn **PHP 8.1 trở lên**.
3. **MySQL Databases** → tạo CSDL (vd tên `movie`). Ghi lại 4 thông tin hiển thị:
   **MySQL Hostname** (`sqlXXX.infinityfree.com`), **MySQL DB Name** (`if0_XXXXXXXX_movie`),
   **MySQL Username** (`if0_XXXXXXXX`) và **mật khẩu** (xem ở mục *Account Details* trong client area).
4. Bấm **phpMyAdmin** cạnh CSDL vừa tạo → tab **Import** → chọn `deploy/dist/hosting.sql` → **Go**.
   Kết quả: 16 bảng, 10 phim, khoảng 180 suất chiếu, 11 đơn vé.

## Bước 3 – Cấu hình kết nối CSDL

Trên máy, sao chép `src/config/database.local.example.php` thành `src/config/database.local.php`
và điền 4 thông tin ở bước 2:

```php
<?php
return [
    'host'   => 'sql123.infinityfree.com',
    'port'   => '3306',
    'dbname' => 'if0_12345678_movie',
    'user'   => 'if0_12345678',
    'pass'   => 'mat-khau-cua-ban',
];
```

Chạy lại để gói file này vào zip (không cần tạo lại SQL):

```
php deploy/build.php --no-sql
```

`database.local.php` chứa mật khẩu nên đã được `.gitignore` bỏ qua, không bị đẩy lên GitHub.

## Bước 4 – Upload mã nguồn

1. Giải nén `deploy/dist/cinema-booking.zip` ra một thư mục trên máy.
2. Kết nối FTP bằng **FileZilla** với thông tin ở mục *FTP Details* (host `ftpupload.net`, cổng 21,
   user `if0_XXXXXXXX`, mật khẩu như trên) – hoặc dùng **Online File Manager** trong Control Panel.
3. Mở thư mục **`htdocs`**, xóa file mặc định (`index2.html`...), rồi upload **toàn bộ nội dung** đã giải nén
   vào thẳng `htdocs` (gồm cả file ẩn `.htaccess`):
   ```
   htdocs/
     .htaccess      <- chuyển mọi request vào public/, chặn truy cập src/ và logs/
     public/
     src/
     logs/
   ```
4. Mở `https://ten-ban-chon.rf.gd` → trang chủ hiện danh sách phim là xong. Đăng nhập thử bằng tài khoản demo
   (`admin@cinema.com` / `admin123`, `user@example.com` / `user123`).

Tên miền mới tạo có thể cần vài phút đến vài giờ mới truy cập được.

## Lưu ý

| Vấn đề | Giải thích |
|---|---|
| Không nhận được email xác nhận | InfinityFree chặn hàm `mail()`. Đặt vé vẫn thành công, trang báo "Đặt vé thành công…" (đề tài không bắt buộc email thật) |
| Lịch chiếu trống sau vài ngày | Suất mẫu đã qua. Admin thêm suất trong trang quản trị, hoặc chạy lại `php deploy/build.php` rồi import lại `hosting.sql` (file có `DROP TABLE` nên **xóa toàn bộ dữ liệu cũ**) |
| Trang báo "Lỗi hệ thống, vui lòng thử lại" | Thường do sai thông tin trong `database.local.php` hoặc chưa import SQL. Chi tiết lỗi ghi ở `htdocs/logs/app.log` (xem bằng FileZilla) |
| Gõ `/src/...` hoặc `/logs/...` trên trình duyệt | Bị chặn 403 – đúng như mong muốn |
| Hosting dùng MariaDB thay vì MySQL | Mã nguồn đã dùng cú pháp tương thích cả hai; `hosting.sql` là bản dump thuần (CREATE TABLE + INSERT) |
| Giờ hiển thị lệch | Kết nối CSDL tự đặt múi giờ +07:00 nên giờ suất chiếu / đóng bán luôn theo giờ Việt Nam |
