<?php
/**
 * Trang cài đặt dùng 1 lần trên hosting: nạp deploy-data/hosting.sql vào CSDL cấu hình trong
 * src/config/database.local.php, rồi tự xóa chính nó và file SQL.
 * deploy/build.php chép file này vào public/ với tên ngẫu nhiên; mở URL đó trên trình duyệt.
 */
header('Content-Type: text/plain; charset=utf-8');
set_time_limit(300);

$root = dirname(__DIR__);
$sqlFile = $root . '/deploy-data/hosting.sql';
$config = require $root . '/src/config/database.php';

if (!is_file($sqlFile)) {
    exit("Không thấy deploy-data/hosting.sql (có thể đã cài xong trước đó).\n");
}

mysqli_report(MYSQLI_REPORT_OFF);
$db = @new mysqli($config['host'], $config['user'], $config['pass'], $config['dbname'], (int) $config['port']);
if ($db->connect_errno) {
    exit("Không kết nối được CSDL: " . $db->connect_error
        . "\nKiểm tra đã tạo CSDL '{$config['dbname']}' trong Control Panel -> MySQL Databases chưa.\n");
}
$db->set_charset('utf8mb4');

if (!$db->multi_query(file_get_contents($sqlFile))) {
    exit("Lỗi SQL: " . $db->error . "\n");
}
$count = 0;
do {
    $count++;
    if ($result = $db->store_result()) {
        $result->free();
    }
    if ($db->errno) {
        exit("Lỗi SQL ở câu thứ $count: " . $db->error . "\n");
    }
} while ($db->more_results() && $db->next_result());
if ($db->errno) {
    exit("Lỗi SQL ở câu thứ $count: " . $db->error . "\n");
}

$tables = $db->query('SHOW TABLES')->num_rows;
$movies = $db->query('SELECT COUNT(*) FROM movies')->fetch_row()[0];
$showtimes = $db->query('SELECT COUNT(*) FROM showtimes')->fetch_row()[0];

@unlink($sqlFile);
@rmdir(dirname($sqlFile));
@unlink(__FILE__);

echo "CAI DAT XONG: $tables bang, $movies phim, $showtimes suat chieu.\n";
echo "Da xoa file SQL va trang cai dat nay. Mo trang chu de dung website.\n";
