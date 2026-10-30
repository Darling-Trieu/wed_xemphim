<?php
// Cấu hình chung của ứng dụng.

// Đường dẫn gốc của website khi truy cập qua Laragon.
// Laragon tự tạo vhost <ten-thu-muc>.test trỏ vào thư mục public/,
// nên để trống (site nằm ở domain root). Nếu chạy trong thư mục con
// của localhost (vd http://localhost/New folder/public), đổi thành '/New folder/public'.
define('BASE_URL', '');

define('APP_NAME', 'Cinema Booking');

// Thư mục lưu poster phim tải lên (đường dẫn tuyệt đối trên đĩa).
define('UPLOAD_DIR', dirname(__DIR__, 2) . '/public/uploads/movies');
// Đường dẫn public tương ứng để nhúng vào thẻ <img>.
define('UPLOAD_URL', BASE_URL . '/uploads/movies');

// Thời gian chờ tối đa (giây) không dùng trong luồng hiện tại (khóa ghế ngay khi đặt),
// giữ lại làm hằng số cấu hình cho rõ ràng nếu sau này cần mở rộng.
define('BOOKING_LOCK_SECONDS', 0);

// Quy tắc đặt vé (BR03, BR04)
define('SALE_CLOSE_MINUTES', 15);   // đóng bán trước giờ chiếu
define('MAX_SEATS_PER_BOOKING', 8);
define('CLEANUP_MINUTES', 15);      // thời gian dọn phòng sau mỗi suất (BR02)

date_default_timezone_set('Asia/Ho_Chi_Minh');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
