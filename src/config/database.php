<?php
// Thông tin kết nối CSDL MySQL (mặc định Laragon: user root, không mật khẩu).
return [
    'host'    => '127.0.0.1',
    'port'    => '3306',
    // Biến môi trường DB_NAME chỉ dùng khi chạy kiểm thử trên CSDL riêng (tests/)
    'dbname'  => getenv('DB_NAME') ?: 'movie_booking',
    'charset' => 'utf8mb4',
    'user'    => 'root',
    'pass'    => '',
];
