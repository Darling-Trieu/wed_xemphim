<?php
/**
 * Đóng gói để đưa lên hosting PHP dùng chung (InfinityFree...). Xem docs/DEPLOY.md.
 *
 *   php deploy/build.php            -> deploy/dist/cinema-booking.zip + deploy/dist/hosting.sql
 *   php deploy/build.php --no-sql   -> chỉ tạo file zip
 *
 * hosting.sql: tạo CSDL tạm từ database/schema.sql trên MySQL của Laragon rồi dump ra bản
 * chỉ gồm bảng + dữ liệu (không CREATE DATABASE / USE), import được bằng phpMyAdmin.
 * Suất chiếu mẫu tính theo ngày chạy script, nên chạy lại ngay trước khi import.
 */

date_default_timezone_set('Asia/Ho_Chi_Minh');

$root = dirname(__DIR__);
$dist = __DIR__ . '/dist';
$mysqlBinDir = getenv('MYSQL_BIN_DIR') ?: 'C:/laragon/bin/mysql/mysql-8.0.30-winx64/bin';
const TEMP_DB = 'movie_booking_deploy';

if (!is_dir($dist)) {
    mkdir($dist, 0777, true);
}

// ---------------------------------------------------------------- File zip mã nguồn
$zipPath = "$dist/cinema-booking.zip";
@unlink($zipPath);
$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
    fwrite(STDERR, "Không tạo được $zipPath\n");
    exit(1);
}

$zip->addFile(__DIR__ . '/root.htaccess', '.htaccess');
$zip->addFromString('logs/.htaccess', "Require all denied\n");

$fileCount = 0;
foreach (['public', 'src'] as $dir) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/$dir", FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
        if (preg_match('#^public/backup\.sql$#', $relative)) {
            continue;
        }
        $zip->addFile($file->getPathname(), $relative);
        $fileCount++;
    }
}
$zip->close();

$hasLocalConfig = is_file("$root/src/config/database.local.php");
printf("Đã tạo %s (%d file).\n", $zipPath, $fileCount + 2);
echo $hasLocalConfig
    ? "  Đã kèm src/config/database.local.php (thông tin CSDL hosting).\n"
    : "  CHƯA có src/config/database.local.php: tạo theo database.local.example.php rồi chạy lại,\n"
    . "  hoặc tự tạo file đó trên hosting sau khi upload.\n";

if (in_array('--no-sql', $argv, true)) {
    exit(0);
}

// ---------------------------------------------------------------- File SQL cho phpMyAdmin
$mysql = '"' . $mysqlBinDir . '/mysql.exe" -u root --default-character-set=utf8mb4';
$dump = '"' . $mysqlBinDir . '/mysqldump.exe" -u root --default-character-set=utf8mb4';

$schema = str_replace('movie_booking', TEMP_DB, file_get_contents("$root/database/schema.sql"));
$tmpSchema = sys_get_temp_dir() . '/movie_booking_deploy_schema.sql';
file_put_contents($tmpSchema, "DROP DATABASE IF EXISTS " . TEMP_DB . ";\n" . $schema);

exec("$mysql < \"$tmpSchema\" 2>&1", $out, $code);
if ($code !== 0) {
    fwrite(STDERR, "Không import được schema.sql (MySQL của Laragon đã bật chưa?):\n" . implode("\n", $out) . "\n");
    exit(1);
}

$sqlPath = "$dist/hosting.sql";
exec("$dump --single-transaction --no-tablespaces --skip-comments --skip-add-locks " . TEMP_DB . " > \"$sqlPath\" 2>&1", $out, $code);
exec("$mysql -e \"DROP DATABASE IF EXISTS " . TEMP_DB . "\" 2>&1");
@unlink($tmpSchema);

if ($code !== 0) {
    fwrite(STDERR, "mysqldump lỗi:\n" . file_get_contents($sqlPath) . "\n");
    exit(1);
}

// Dump của MySQL 8 có thể dùng collation mặc định 0900 mà MariaDB không hiểu
$sql = str_replace('utf8mb4_0900_ai_ci', 'utf8mb4_unicode_ci', file_get_contents($sqlPath));
file_put_contents($sqlPath, "-- Cinema Booking: dữ liệu để import bằng phpMyAdmin (tạo lúc " . date('d/m/Y H:i') . ")\n"
    . "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n" . $sql . "\nSET FOREIGN_KEY_CHECKS = 1;\n");

printf("Đã tạo %s (%s KB).\n", $sqlPath, number_format(filesize($sqlPath) / 1024, 0));
