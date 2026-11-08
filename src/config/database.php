<?php
// Thông tin kết nối CSDL MySQL. Mặc định cho Laragon (user root, không mật khẩu).
// Trên hosting: tạo src/config/database.local.php (xem database.local.example.php) để ghi đè
// các giá trị bên dưới. File .local không đưa lên git vì chứa mật khẩu.
$config = [
    'host'    => '127.0.0.1',
    'port'    => '3306',
    // Biến môi trường DB_NAME chỉ dùng khi chạy kiểm thử trên CSDL riêng (tests/)
    'dbname'  => getenv('DB_NAME') ?: 'movie_booking',
    'charset' => 'utf8mb4',
    'user'    => 'root',
    'pass'    => '',
];

$localFile = __DIR__ . '/database.local.php';
if (is_file($localFile) && !getenv('DB_NAME')) {
    $config = array_merge($config, require $localFile);
}

return $config;
