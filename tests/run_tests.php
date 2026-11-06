<?php
/**
 * Kiểm thử tự động theo đặc tả Chương 2 (Giai đoạn 3).
 *
 *   php tests/run_tests.php
 *
 * Yêu cầu: MySQL của Laragon đang chạy (root, không mật khẩu), PHP có curl + pdo_mysql.
 * Script tự tạo CSDL riêng movie_booking_test từ database/schema.sql (không đụng CSDL thật),
 * bật 2 server `php -S` (cổng 8099, 8100) dùng chung thư mục session, chạy các ca, rồi tắt server.
 */

const TEST_DB = 'movie_booking_test';
const PORTS = [8099, 8100];

if (getenv('DB_NAME') && getenv('DB_NAME') !== TEST_DB) {
    throw new RuntimeException('Tests chỉ được dùng movie_booking_test.');
}
putenv('DB_NAME=' . TEST_DB);
$authGroup = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--auth-only=')) {
        $authGroup = substr($arg, strlen('--auth-only='));
        if (!in_array($authGroup, ['register', 'profile', 'password'], true)) {
            throw new RuntimeException('Nhóm auth không hợp lệ.');
        }
    }
}

date_default_timezone_set('Asia/Ho_Chi_Minh'); // cùng múi giờ với ứng dụng (src/config/config.php)

$root = dirname(__DIR__);
$mysqlCandidates = glob('C:/laragon/bin/mysql/*/bin/mysql.exe');
$mysqlBin = getenv('MYSQL_BIN') ?: (end($mysqlCandidates) ?: 'mysql');
$sessionDir = sys_get_temp_dir() . '/movie_booking_test_sessions';

// ---------------------------------------------------------------- Hạ tầng kiểm thử

$results = [];

function check(string $case, string $name, bool $ok, string $detail = ''): void
{
    global $results;
    $results[] = [$case, $name, $ok, $detail];
    printf("  [%s] %s%s\n", $ok ? 'ĐẠT' : 'KHÔNG ĐẠT', $name, $ok || $detail === '' ? '' : "\n         -> $detail");
}

function section(string $title): void
{
    echo "\n== $title\n";
}

final class Client
{
    private string $jar;

    public function __construct(private string $base)
    {
        $this->jar = tempnam(sys_get_temp_dir(), 'jar');
    }

    public function handle(string $method, string $path, array $data = [])
    {
        $ch = curl_init($this->base . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEJAR      => $this->jar,
            CURLOPT_COOKIEFILE     => $this->jar,
            CURLOPT_TIMEOUT        => 30,
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        return $ch;
    }

    public static function parse($ch, string $raw): array
    {
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($raw, 0, $headerSize);
        $location = preg_match('/^Location:\s*(.+)$/mi', $headers, $m) ? trim($m[1]) : null;
        $result = [
            'status'   => curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
            'location' => $location,
            'body'     => substr($raw, $headerSize),
        ];
        curl_close($ch); // ghi cookie jar
        return $result;
    }

    public function get(string $path): array
    {
        $ch = $this->handle('GET', $path);
        return self::parse($ch, (string) curl_exec($ch));
    }

    public function post(string $path, array $data): array
    {
        $ch = $this->handle('POST', $path, $data);
        return self::parse($ch, (string) curl_exec($ch));
    }

    /** Theo redirect 1 bước (để đọc flash message). */
    public function follow(array $response): array
    {
        return $response['location'] ? $this->get($response['location']) : $response;
    }

    public function csrf(string $pagePath): string
    {
        $page = $this->get($pagePath);
        return preg_match('/name="csrf_token" value="([^"]+)"/', $page['body'], $m) ? $m[1] : '';
    }

    public function login(string $login, string $password): array
    {
        $token = $this->csrf('/login');
        return $this->post('/login', ['csrf_token' => $token, 'login' => $login, 'email' => $login, 'password' => $password]);
    }
}

function flashError(string $html): string
{
    return preg_match('/class="alert alert-error"[^>]*>\s*(.*?)\s*<\/div>/s', $html, $m) ? html_entity_decode(strip_tags($m[1])) : '';
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=' . TEST_DB . ';charset=utf8mb4', 'root', '', [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function scalar(string $sql, array $params = [])
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function movieId(string $title): int
{
    return (int) scalar('SELECT id FROM movies WHERE title = ?', [$title]);
}

function userId(string $email): int
{
    return (int) scalar('SELECT id FROM users WHERE email = ?', [$email]);
}

function seatId(int $roomId, string $label): int
{
    return (int) scalar('SELECT id FROM seats WHERE room_id = ? AND CONCAT(seat_row, seat_number) = ?', [$roomId, $label]);
}

/** Tạo suất chiếu trực tiếp trong CSDL làm dữ liệu kiểm thử (end = start + thời lượng + 15'). */
function makeShowtime(int $movieId, int $roomId, int $startTs, int $basePrice = 75000): int
{
    $duration = (int) scalar('SELECT duration_minutes FROM movies WHERE id = ?', [$movieId]);
    db()->prepare(
        'INSERT INTO showtimes (movie_id, room_id, show_date, start_time, end_time, base_price, status)
         VALUES (?, ?, ?, ?, ?, ?, "active")'
    )->execute([
        $movieId, $roomId, date('Y-m-d', $startTs), date('H:i:s', $startTs),
        date('H:i:s', $startTs + ($duration + 15) * 60), $basePrice,
    ]);
    return (int) db()->lastInsertId();
}

/** Gọi API giữ / nhả ghế như trình duyệt: CSRF token lấy từ data-csrf của sơ đồ ghế. */
function holdSeat(Client $c, int $showtimeId, int $seatId, string $action = 'hold_seat', bool $withCsrf = true): array
{
    $data = ['showtime_id' => $showtimeId, 'seat_id' => $seatId];
    if ($withCsrf) {
        $page = $c->get("/showtimes/$showtimeId/seats")['body'];
        $data['csrf_token'] = preg_match('/data-csrf="([^"]+)"/', $page, $m) ? $m[1] : '';
    }
    return $c->post("/api.php?action=$action", $data);
}

function holdOk(array $response): bool
{
    return (json_decode($response['body'], true)['held'] ?? false) === true;
}

/** Đặt vé qua giao diện web: màn hình xác nhận -> xác nhận. Trả về response của bước xác nhận. */
function book(Client $c, int $showtimeId, array $seatIds, array $extra = ['age_confirmed' => '1']): array
{
    // Token lấy từ trang hồ sơ (cùng phiên): trang ghế của suất đã đóng bán không còn form,
    // ca 4 mô phỏng người dùng mở trang lúc còn bán rồi mới bấm xác nhận.
    $token = $c->csrf('/account');
    $data = array_merge(['csrf_token' => $token, 'seat_ids' => implode(',', $seatIds)], $extra);
    $confirm = $c->post("/booking/$showtimeId/confirm", $data);
    if ($confirm['status'] !== 200) {
        return $confirm;
    }
    foreach ($seatIds as $seatId) {
        $hold = holdSeat($c, $showtimeId, $seatId, 'hold_seat');
        if (!(json_decode($hold['body'], true)['held'] ?? false)) {
            throw new RuntimeException("Không giữ được ghế $seatId bằng session của client kiểm thử.");
        }
    }
    return $c->post("/booking/$showtimeId", $data);
}

function bookingCodeFrom(array $response): ?string
{
    return $response['location'] && preg_match('#/booking/success/([A-Z0-9]+)#', $response['location'], $m) ? $m[1] : null;
}

// ---------------------------------------------------------------- Chuẩn bị CSDL + server

foreach (PORTS as $port) {
    $existing = @fsockopen('127.0.0.1', $port);
    if ($existing) {
        fclose($existing);
        throw new RuntimeException("Cổng $port đang được dùng; không gửi test đến server không thuộc bộ kiểm thử.");
    }
}
echo "Tạo CSDL " . TEST_DB . " từ database/schema.sql ...\n";
$sqlFile = sys_get_temp_dir() . '/movie_booking_test_schema.sql';
file_put_contents($sqlFile, "DROP DATABASE IF EXISTS " . TEST_DB . ";\n"
    . str_replace('movie_booking', TEST_DB, file_get_contents($root . '/database/schema.sql')));
exec('"' . $mysqlBin . '" -u root --default-character-set=utf8mb4 < "' . $sqlFile . '" 2>&1', $out, $code);
if ($code !== 0) {
    fwrite(STDERR, "Không import được schema:\n" . implode("\n", $out) . "\n");
    exit(1);
}

if (!is_dir($sessionDir)) {
    mkdir($sessionDir, 0777, true);
}
$servers = [];
foreach (PORTS as $port) {
    $cmd = [PHP_BINARY, '-d', 'session.save_path=' . $sessionDir, '-S', "127.0.0.1:$port", '-t', $root . '/public'];
    $servers[] = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['file', 'NUL', 'w'], 2 => ['file', 'NUL', 'w']], $pipes, $root,
        array_merge(getenv(), ['DB_NAME' => TEST_DB]));
    fclose($pipes[0]);
}
register_shutdown_function(static function () use (&$servers) {
    foreach ($servers as $proc) {
        $status = proc_get_status($proc);
        if ($status['running']) {
            proc_terminate($proc);
        }
        proc_close($proc);
    }
});
foreach (PORTS as $port) {
    for ($i = 0; $i < 50 && !@fsockopen('127.0.0.1', $port); $i++) {
        usleep(100000);
    }
}

$A = 'http://127.0.0.1:' . PORTS[0];
$B = 'http://127.0.0.1:' . PORTS[1];

$userA = new Client($A);
$userA->login('user@example.com', 'user123');
$userB = new Client($B);
$userB->login('ngoc.tran@example.com', 'user123');
$admin = new Client($A);
$admin->login('admin@cinema.com', 'admin123');

$moviePG = movieId('Shaun the Sheep: Quái Lạ Ghé Nhà');
$movieT18 = movieId('Oán Linh Đòi Mạng');
$movieT13 = movieId('Hành Tinh Cát Đỏ');

if ($authGroup !== null) {
    require __DIR__ . '/account_tests.php';
    $passed = count(array_filter($results, fn ($r) => $r[2]));
    printf("\nTỔNG: %d/%d kiểm tra đạt\n", $passed, count($results));
    exit($passed === count($results) ? 0 : 1);
}

section('Quyền sở hữu ghế giữ tạm');
$holdShowtime = makeShowtime($moviePG, 1, strtotime('+9 days 10:00'));
$holdSeat = seatId(1, 'A1');
$holdToken = $userA->csrf('/account');
$holdData = ['csrf_token' => $holdToken, 'seat_ids' => (string) $holdSeat];
$beforeHolds = (int) scalar('SELECT COUNT(*) FROM bookings');
$r = $userA->post("/booking/$holdShowtime", $holdData);
check('hold', 'Không giữ ghế: không được tạo đơn', $r['status'] === 302
    && str_contains(flashError($userA->follow($r)['body']), 'hết thời gian giữ')
    && (int) scalar('SELECT COUNT(*) FROM bookings') === $beforeHolds);
holdSeat($userB, $holdShowtime, $holdSeat, 'hold_seat');
$r = $userA->post("/booking/$holdShowtime", $holdData);
check('hold', 'Hold của session khác: không được tạo đơn', $r['status'] === 302
    && str_contains(flashError($userA->follow($r)['body']), 'hết thời gian giữ')
    && (int) scalar('SELECT COUNT(*) FROM bookings') === $beforeHolds);
holdSeat($userB, $holdShowtime, $holdSeat, 'release_seat');
holdSeat($userA, $holdShowtime, $holdSeat, 'hold_seat');
db()->prepare('UPDATE seat_holds SET expires_at=DATE_SUB(NOW(), INTERVAL 1 SECOND) WHERE showtime_id=?')->execute([$holdShowtime]);
$r = $userA->post("/booking/$holdShowtime", $holdData);
check('hold', 'Hold hết hạn: không được tạo đơn', $r['status'] === 302
    && str_contains(flashError($userA->follow($r)['body']), 'hết thời gian giữ')
    && (int) scalar('SELECT COUNT(*) FROM bookings') === $beforeHolds);
check('hold', 'Giữ lại đúng session thì đặt được', bookingCodeFrom(book($userA, $holdShowtime, [$holdSeat])) !== null);

$seatX = seatId(1, 'B1');
$r = holdSeat($userA, $holdShowtime, $seatX, 'hold_seat', false);
check('hold', 'API giữ ghế không có CSRF token -> 419, không giữ ghế', $r['status'] === 419
    && (int) scalar('SELECT COUNT(*) FROM seat_holds WHERE showtime_id = ? AND seat_id = ?', [$holdShowtime, $seatX]) === 0);

$greedy = new Client($A);
$heldCount = 0;
foreach (['C1', 'C2', 'C3', 'C4', 'C5', 'C6', 'C7', 'C8', 'D1'] as $label) {
    $last = holdSeat($greedy, $holdShowtime, seatId(1, $label));
    $heldCount += holdOk($last) ? 1 : 0;
}
check('hold', 'Một phiên chỉ giữ tối đa 8 ghế / suất (ghế thứ 9 bị từ chối kèm lý do)', $heldCount === 8
    && str_contains(json_decode($last['body'], true)['error'] ?? '', 'tối đa 8'), "giữ được $heldCount ghế");
$greedy->login('huy.pham@example.com', 'user123');
$greedy->post('/logout', ['csrf_token' => $greedy->csrf('/account')]);
check('hold', 'Đăng xuất -> nhả toàn bộ ghế đang giữ', (int) scalar('SELECT COUNT(*) FROM seat_holds WHERE showtime_id = ?', [$holdShowtime]) === 0);

$guestHold = new Client($A);
$guestSeat = seatId(1, 'D5');
check('hold', 'Khách chưa đăng nhập giữ được ghế', holdOk(holdSeat($guestHold, $holdShowtime, $guestSeat)));
$guestHold->login('bao.dang@example.com', 'user123');
$seatsJson = json_decode($guestHold->get("/api.php?action=seats&showtime_id=$holdShowtime")['body'], true);
$mine = array_values(array_filter($seatsJson['seats'], fn ($s) => $s['id'] === $guestSeat))[0] ?? [];
check('hold', 'Đăng nhập xong: ghế vẫn là của mình (giữ ghế đi theo session mới)', ($mine['held_by_me'] ?? false) === true);
$r = $guestHold->post("/booking/$holdShowtime", ['csrf_token' => $guestHold->csrf('/account'), 'seat_ids' => (string) $guestSeat]);
check('hold', 'Khách chọn ghế -> đăng nhập -> đặt được đúng ghế đó', bookingCodeFrom($r) !== null, flashError($guestHold->follow($r)['body']));

// ---------------------------------------------------------------- Ca 1: đặt cùng ghế đồng thời
section('Ca 1 - Hai phiên đặt cùng 1 ghế gần như đồng thời (BR01)');

$st1 = makeShowtime($moviePG, 1, strtotime('+2 days 10:00'));
$seat = seatId(1, 'B5');
$tokA = $userA->csrf("/showtimes/$st1/seats");
$tokB = $userB->csrf("/showtimes/$st1/seats");
$holdA = holdSeat($userA, $st1, $seat, 'hold_seat');
$holdB = holdSeat($userB, $st1, $seat, 'hold_seat');
check('1', 'Session A giữ ghế; session B không lấy được ghế đang giữ',
    (json_decode($holdA['body'], true)['held'] ?? false) === true
    && (json_decode($holdB['body'], true)['held'] ?? true) === false);
$mh = curl_multi_init();
$hA = $userA->handle('POST', "/booking/$st1", ['csrf_token' => $tokA, 'seat_ids' => (string) $seat]);
$hB = $userB->handle('POST', "/booking/$st1", ['csrf_token' => $tokB, 'seat_ids' => (string) $seat]);
curl_multi_add_handle($mh, $hA);
curl_multi_add_handle($mh, $hB);
do {
    curl_multi_exec($mh, $running);
    curl_multi_select($mh);
} while ($running > 0);
$rA = Client::parse($hA, curl_multi_getcontent($hA));
$rB = Client::parse($hB, curl_multi_getcontent($hB));
curl_multi_close($mh);
$successes = (int) (bookingCodeFrom($rA) !== null) + (int) (bookingCodeFrom($rB) !== null);
$loser = bookingCodeFrom($rA) ? [$userB, $rB] : [$userA, $rA];
$loserMsg = flashError($loser[0]->follow($loser[1])['body']);
check('1', 'HTTP song song (2 server): đúng 1 đơn thành công', $successes === 1, "số đơn thành công = $successes");
check('1', 'Phiên không sở hữu hold bị từ chối khi gửi đơn song song', str_contains($loserMsg, 'hết thời gian giữ'), "flash = '$loserMsg'");
check('1', 'CSDL chỉ có 1 dòng ghế-theo-suất cho B5', (int) scalar('SELECT COUNT(*) FROM showtime_seats WHERE showtime_id = ? AND seat_id = ?', [$st1, $seat]) === 1);

// 10 tiến trình cùng gọi Booking::create vào đúng 1 thời điểm -> ép xảy ra race ở UNIQUE
$st1b = makeShowtime($moviePG, 1, strtotime('+2 days 14:00'));
$seat = seatId(1, 'C3');
$customerIds = db()->query("SELECT id FROM users WHERE role = 'customer' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
$startAt = microtime(true) + 1.5;
$procs = [];
$workerPipes = [];
for ($i = 0; $i < 10; $i++) {
    $procs[] = proc_open(
        [PHP_BINARY, '-d', 'session.save_path=' . $sessionDir, $root . '/tests/booking_worker.php', $customerIds[$i % count($customerIds)], $st1b, $seat, sprintf('%.4f', $startAt)],
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes2, $root, array_merge(getenv(), ['DB_NAME' => TEST_DB])
    );
    $workerPipes[] = $pipes2;
}
$outcomes = [];
foreach ($procs as $i => $proc) {
    $outcomes[] = json_decode(stream_get_contents($workerPipes[$i][1]), true) ?? ['ok' => false, 'fatal' => stream_get_contents($workerPipes[$i][2])];
    proc_close($proc);
}
$ok = count(array_filter($outcomes, fn ($o) => $o['ok']));
$conflicts = count(array_filter($outcomes, fn ($o) => !$o['ok'] && str_contains($o['error'] ?? '', 'đã có người đặt')));
$fatal = array_filter($outcomes, fn ($o) => isset($o['fatal']));
check('1', '10 tiến trình đồng thời: đúng 1 thành công, 9 nhận thông báo ghế đã có người đặt',
    $ok === 1 && $conflicts === 9, "thành công=$ok, xung đột=$conflicts, lỗi khác=" . json_encode(array_values($fatal), JSON_UNESCAPED_UNICODE));
check('1', 'Không còn đơn "mồ côi" (đơn không có ghế) sau rollback',
    (int) scalar('SELECT COUNT(*) FROM bookings b WHERE showtime_id = ? AND NOT EXISTS (SELECT 1 FROM showtime_seats ss WHERE ss.booking_id = b.id)', [$st1b]) === 0);

// ---------------------------------------------------------------- Ca 2: sửa giá từ client
section('Ca 2 - Sửa giá trong request từ client (BR05)');

$st2 = makeShowtime($moviePG, 1, strtotime('+3 days 10:00'), 75000);
$seats2 = [seatId(1, 'A1'), seatId(1, 'C1'), seatId(1, 'E1')]; // Thường, VIP, Đôi
$r = book($userA, $st2, $seats2, ['price' => '1000', 'prices' => ['1', '1', '1'], 'total' => '3', 'total_amount' => '3']);
$code2 = bookingCodeFrom($r);
$prices = $code2 ? db()->query("SELECT GROUP_CONCAT(price ORDER BY price) FROM booking_seats bs JOIN bookings b ON b.id = bs.booking_id WHERE b.booking_code = '$code2'")->fetchColumn() : '';
$total2 = $code2 ? (int) scalar('SELECT total_amount FROM bookings WHERE booking_code = ?', [$code2]) : 0;
check('2', 'Giá lưu = giá server tính: 75.000 / 85.000 / 135.000', $prices === '75000,85000,135000', "giá lưu = $prices");
check('2', 'Tổng tiền = 295.000 (bỏ qua total gửi lên)', $total2 === 295000, "tổng = $total2");
check('2', 'Có bản ghi thanh toán mô phỏng Thành công đúng số tiền',
    $code2 && scalar('SELECT CONCAT(p.status, ":", p.amount) FROM payments p JOIN bookings b ON b.id = p.booking_id WHERE b.booking_code = ?', [$code2]) === 'success:295000');

// ---------------------------------------------------------------- Ca 3: 9 ghế / ghế phòng khác
section('Ca 3 - Gửi 9 ghế, ghế phòng khác (BR04)');

$st3 = makeShowtime($moviePG, 1, strtotime('+3 days 14:00'));
$nine = array_map(fn ($n) => seatId(1, 'A' . $n), range(1, 8));
$nine[] = seatId(1, 'B1');
$r = book($userA, $st3, $nine);
$msg = flashError($userA->follow($r)['body']);
check('3', '9 ghế qua API bị chặn: "Mỗi đơn chỉ được chọn tối đa 8 ghế"', bookingCodeFrom($r) === null && str_contains($msg, 'tối đa 8 ghế'), "flash = '$msg'");
$r = book($userA, $st3, [seatId(2, 'A1')]);
$msg = flashError($userA->follow($r)['body']);
check('3', 'Ghế thuộc phòng khác bị chặn', bookingCodeFrom($r) === null && str_contains($msg, 'Ghế không hợp lệ'), "flash = '$msg'");
$demoRoom = (int) scalar("SELECT id FROM rooms WHERE name = 'Phòng 3' AND cinema_id = 1");
$st3b = makeShowtime($moviePG, $demoRoom, strtotime('+3 days 21:00'));
$r = book($userA, $st3b, [seatId($demoRoom, 'A1')]); // ghế Ngừng sử dụng trong seed
check('3', 'Ghế Ngừng sử dụng bị chặn', bookingCodeFrom($r) === null);
$r = book($userA, $st3, array_slice($nine, 0, 8));
check('3', '8 ghế vẫn đặt được', bookingCodeFrom($r) !== null);

// ---------------------------------------------------------------- Ca 4: còn 14 phút
section('Ca 4 - Đặt suất còn 14 phút nữa chiếu (BR03)');

$st4 = makeShowtime($moviePG, 2, time() + 14 * 60 + 30);
$r = book($userA, $st4, [seatId(2, 'A1')]);
$msg = flashError($userA->follow($r)['body']);
check('4', 'Bị chặn: "Suất chiếu đã đóng bán"', bookingCodeFrom($r) === null && str_contains($msg, 'Suất chiếu đã đóng bán'), "flash = '$msg'");
$st4b = makeShowtime($moviePG, 3, time() + 16 * 60 + 30);
check('4', 'Suất còn 16 phút vẫn đặt được', bookingCodeFrom(book($userA, $st4b, [seatId(3, 'A1')])) !== null);

// ---------------------------------------------------------------- Ca 5: T18 không xác nhận tuổi
section('Ca 5 - Phim T18 không tích xác nhận độ tuổi (BR07)');

$st5 = makeShowtime($movieT18, 1, strtotime('+4 days 10:00'));
$r = book($userA, $st5, [seatId(1, 'A1')], []);
check('5', 'Gọi trực tiếp không có age_confirmed: không tạo đơn, báo "Cần xác nhận đúng độ tuổi quy định"',
    bookingCodeFrom($r) === null && str_contains($r['body'], 'Cần xác nhận đúng độ tuổi quy định'), 'status ' . $r['status']);
check('5', 'age_confirmed=0 cũng bị chặn', bookingCodeFrom(book($userA, $st5, [seatId(1, 'A1')], ['age_confirmed' => '0'])) === null);
check('5', 'Không có đơn nào được tạo cho suất', (int) scalar('SELECT COUNT(*) FROM bookings WHERE showtime_id = ?', [$st5]) === 0);
check('5', 'Có xác nhận thì đặt được', bookingCodeFrom(book($userA, $st5, [seatId(1, 'A1')])) !== null);
$token = $userA->csrf("/showtimes/$st5/seats");
$confirmPage = $userA->post("/booking/$st5/confirm", ['csrf_token' => $token, 'seat_ids' => (string) seatId(1, 'A2')]);
check('5', 'Màn hình xác nhận có ô xác nhận độ tuổi', str_contains($confirmPage['body'], 'name="age_confirmed"'));
$stP = makeShowtime($moviePG, 1, strtotime('+4 days 14:00'));
check('5', 'Phim P không cần xác nhận tuổi', bookingCodeFrom(book($userA, $stP, [seatId(1, 'A1')], [])) !== null);

// ---------------------------------------------------------------- Ca 7: admin hủy đơn
section('Ca 7 - Admin hủy đơn (UC13)');

$st7 = makeShowtime($moviePG, 1, strtotime('+5 days 10:00'));
$seat7 = seatId(1, 'D4');
$code7 = bookingCodeFrom(book($userA, $st7, [$seat7]));
$token = $admin->csrf("/admin/bookings/$code7");
$r = $admin->post("/admin/bookings/$code7/cancel", ['csrf_token' => $token, 'cancel_reason' => '']);
check('7', 'Không nhập lý do -> không hủy', scalar('SELECT status FROM bookings WHERE booking_code = ?', [$code7]) === 'paid');
$r = $admin->post("/admin/bookings/$code7/cancel", ['csrf_token' => $token, 'cancel_reason' => 'Khách đổi lịch']);
check('7', 'Đơn -> Đã hủy', scalar('SELECT status FROM bookings WHERE booking_code = ?', [$code7]) === 'cancelled');
check('7', 'Thanh toán -> Đã hoàn', scalar('SELECT p.status FROM payments p JOIN bookings b ON b.id = p.booking_id WHERE b.booking_code = ?', [$code7]) === 'refunded');
check('7', 'Ghế trống lại (không còn trong ghế-theo-suất)', (int) scalar('SELECT COUNT(*) FROM showtime_seats WHERE showtime_id = ? AND seat_id = ?', [$st7, $seat7]) === 0);
check('7', 'Người khác đặt lại được ghế vừa trả', bookingCodeFrom(book($userB, $st7, [$seat7])) !== null);
check('7', 'Có ghi log hủy đơn', (int) scalar("SELECT COUNT(*) FROM audit_logs WHERE action = 'cancel' AND entity = 'booking' AND entity_id = ?", [$code7]) === 1);
$token = $admin->csrf('/admin/bookings/VEDEMO0011');
$r = $admin->post('/admin/bookings/VEDEMO0011/cancel', ['csrf_token' => $token, 'cancel_reason' => 'Thử hủy suất đã chiếu']);
$msg = flashError($admin->follow($r)['body']);
check('7', 'Hủy đơn của suất đã chiếu bị chặn', scalar("SELECT status FROM bookings WHERE booking_code = 'VEDEMO0011'") === 'paid'
    && str_contains($msg, 'Không thể hủy đơn của suất đã chiếu'), "flash = '$msg'");

// ---------------------------------------------------------------- Ca 8: snapshot
section('Ca 8 - Sửa phim / giá sau khi đặt, "Vé của tôi" vẫn hiện thông tin cũ (BR06)');

$movie8 = movieId('Án Mạng Xém Hoàn Hảo');
$st8 = makeShowtime($movie8, 1, strtotime('+6 days 10:00'), 75000);
$code8 = bookingCodeFrom(book($userA, $st8, [seatId(1, 'C2')]));
db()->prepare('UPDATE movies SET title = ? WHERE id = ?')->execute(['Tên Phim Đã Đổi', $movie8]);
db()->prepare('UPDATE showtimes SET base_price = 99000, start_time = "11:30:00" WHERE id = ?')->execute([$st8]);
db()->prepare("UPDATE seat_types SET name = 'VIP Mới', surcharge = 50000 WHERE name = 'VIP'")->execute();
$detail = $userA->get("/account/bookings/$code8")['body'];
$list = $userA->get('/account/bookings')['body'];
check('8', 'Chi tiết vé vẫn hiện tên phim cũ', str_contains($detail, 'Án Mạng Xém Hoàn Hảo') && !str_contains($detail, 'Tên Phim Đã Đổi'));
check('8', 'Chi tiết vé vẫn hiện giờ chiếu cũ 10:00, giá cũ 85.000đ, loại ghế cũ VIP',
    str_contains($detail, '10:00') && str_contains($detail, '85.000đ') && !str_contains($detail, 'VIP Mới'));
check('8', 'Danh sách "Vé của tôi" vẫn hiện tên phim cũ', str_contains($list, 'Án Mạng Xém Hoàn Hảo'));
db()->prepare('UPDATE movies SET title = ? WHERE id = ?')->execute(['Án Mạng Xém Hoàn Hảo', $movie8]);
db()->prepare("UPDATE seat_types SET name = 'VIP', surcharge = 10000 WHERE name = 'VIP Mới'")->execute();

// ---------------------------------------------------------------- Ca 9: IDOR / quyền admin
section('Ca 9 - Xem đơn người khác, gọi API admin (phân quyền)');

$r = $userB->get("/account/bookings/$code8");
check('9', 'User B mở chi tiết đơn của User A -> 404', $r['status'] === 404, 'status ' . $r['status']);
$r = $userB->get("/booking/success/$code8");
check('9', 'User B mở trang thành công đơn của User A -> 404', $r['status'] === 404, 'status ' . $r['status']);
$r = $userA->get('/admin/bookings');
check('9', 'User mở trang admin -> 403', $r['status'] === 403, 'status ' . $r['status']);
$r = $userA->get('/api.php?action=revenue_chart');
check('9', 'User gọi API admin -> 403', $r['status'] === 403, 'status ' . $r['status']);
$token = $userA->csrf('/account');
$r = $userA->post("/admin/bookings/$code8/cancel", ['csrf_token' => $token, 'cancel_reason' => 'x']);
check('9', 'User POST hủy đơn qua route admin -> 403, đơn không đổi', $r['status'] === 403 && scalar('SELECT status FROM bookings WHERE booking_code = ?', [$code8]) === 'paid');

// ---------------------------------------------------------------- Ca 6: trùng lịch biên
section('Ca 6 - Trùng lịch biên: A 19:00 (94 phút) chiếm phòng tới 20:49 (BR02, UC12)');

$day6 = date('Y-m-d', strtotime('+8 days'));
$addShowtime = function (array $fields) use ($admin): array {
    $token = $admin->csrf('/admin/showtimes/create');
    return $admin->post('/admin/showtimes', array_merge(['csrf_token' => $token], $fields));
};
$editShowtime = function (int $id, array $fields) use ($admin): array {
    $token = $admin->csrf("/admin/showtimes/$id/edit");
    return $admin->post("/admin/showtimes/$id", array_merge(['csrf_token' => $token], $fields));
};
$base6 = ['movie_id' => $movieT13, 'room_id' => 2, 'show_date' => $day6, 'base_price' => '75000'];
$r = $addShowtime($base6 + ['start_time' => '19:00']);
$idA = (int) scalar('SELECT id FROM showtimes WHERE room_id = 2 AND show_date = ? AND start_time = "19:00:00"', [$day6]);
check('6', 'Thêm suất A 19:00 thành công, giờ kết thúc chiếm phòng 20:49',
    $r['status'] === 302 && scalar('SELECT end_time FROM showtimes WHERE id = ?', [$idA]) === '20:49:00');
$r = $addShowtime($base6 + ['start_time' => '20:48']);
check('6', 'Thêm suất B 20:48 -> báo trùng kèm tên phim và giờ 19:00 – 20:49',
    $r['status'] === 200 && str_contains($r['body'], 'Phòng đã có suất trùng thời gian')
    && str_contains($r['body'], 'Hành Tinh Cát Đỏ') && str_contains($r['body'], '19:00 – 20:49'), flashError($r['body']));
check('6', 'Form giữ lại dữ liệu đã nhập khi báo trùng', str_contains($r['body'], 'value="20:48"'));
$r = $addShowtime($base6 + ['start_time' => '20:49']);
$idB = (int) scalar('SELECT id FROM showtimes WHERE room_id = 2 AND show_date = ? AND start_time = "20:49:00"', [$day6]);
check('6', 'Thêm suất B 20:49 -> hợp lệ', $r['status'] === 302 && $idB > 0);
$r = $editShowtime($idA, array_merge($base6, ['start_time' => '19:00', 'base_price' => '80000']));
check('6', 'Sửa suất A (giữ giờ) không bị báo trùng với chính nó', $r['status'] === 302
    && (int) scalar('SELECT base_price FROM showtimes WHERE id = ?', [$idA]) === 80000, flashError($r['body']));
$r = $editShowtime($idA, $base6 + ['start_time' => '19:30']);
check('6', 'Sửa A sang 19:30 -> trùng với B (kiểm tra trùng cả khi Sửa)', $r['status'] === 200 && str_contains($r['body'], 'Phòng đã có suất trùng thời gian'));
$token = $admin->csrf('/admin/showtimes');
$admin->post("/admin/showtimes/$idB/cancel", ['csrf_token' => $token]);
$r = $editShowtime($idA, $base6 + ['start_time' => '19:30']);
check('6', 'Suất đã hủy không tính trùng: sau khi hủy B, A dời sang 19:30 được', $r['status'] === 302);
$r = $addShowtime(['movie_id' => $movieT13, 'room_id' => 2, 'show_date' => date('Y-m-d', strtotime('-1 day')), 'start_time' => '10:00', 'base_price' => '75000']);
check('6', 'Giờ bắt đầu trong quá khứ bị chặn', $r['status'] === 200 && str_contains($r['body'], 'Giờ bắt đầu phải ở tương lai'));
$r = $addShowtime(array_merge($base6, ['start_time' => '08:00', 'base_price' => '75000.5']));
check('6', 'Giá cơ bản không nguyên bị chặn', $r['status'] === 200 && str_contains($r['body'], 'Giá cơ bản phải là số nguyên'));
$r = $addShowtime(array_merge($base6, ['start_time' => '08:00', 'base_price' => '0']));
check('6', 'Giá cơ bản = 0 bị chặn', $r['status'] === 200 && str_contains($r['body'], 'Giá cơ bản phải là số nguyên'));
db()->exec("UPDATE movies SET status = 'ended' WHERE id = $movieT13");
$r = $addShowtime($base6 + ['start_time' => '08:00']);
db()->exec("UPDATE movies SET status = 'now_showing' WHERE id = $movieT13");
check('6', 'Phim Ngừng chiếu không xếp suất được', $r['status'] === 200 && str_contains($r['body'], 'Đang chiếu hoặc Sắp chiếu'));
$soldShowtime = $st7; // đã có vé từ ca 7
$r = $editShowtime($soldShowtime, ['movie_id' => $moviePG, 'room_id' => 1, 'show_date' => date('Y-m-d', strtotime('+5 days')), 'start_time' => '11:00', 'base_price' => '75000']);
check('6', 'Suất đã bán vé không đổi được giờ chiếu', $r['status'] === 200 && str_contains($r['body'], 'chỉ được sửa giá cơ bản'));
check('6', 'Thêm / sửa suất được ghi log', (int) scalar("SELECT COUNT(*) FROM audit_logs WHERE entity = 'showtime' AND action IN ('create','update')") >= 3);

// ---------------------------------------------------------------- Ca 12: không xóa cứng
section('Ca 12 - Xóa phim / phòng / suất / rạp đã có vé -> không xóa cứng (BR09)');

$token = $admin->csrf('/admin/movies');
$admin->post("/admin/movies/$moviePG/delete", ['csrf_token' => $token]);
check('12', 'Phim đã có suất: vẫn còn trong CSDL, chuyển Ngừng chiếu', scalar('SELECT status FROM movies WHERE id = ?', [$moviePG]) === 'ended');
check('12', 'Phim Ngừng chiếu không hiện ở trang chủ', !str_contains($userA->get('/')['body'], 'Shaun the Sheep'));
db()->exec("UPDATE movies SET status = 'now_showing' WHERE id = $moviePG");
db()->exec("INSERT INTO movies (title, duration_minutes, age_rating, status) VALUES ('Phim Nháp Chưa Có Suất', 90, 'P', 'coming_soon')");
$draft = (int) db()->lastInsertId();
$admin->post("/admin/movies/$draft/delete", ['csrf_token' => $token]);
check('12', 'Phim chưa từng có suất thì xóa được', (int) scalar('SELECT COUNT(*) FROM movies WHERE id = ?', [$draft]) === 0);

$token = $admin->csrf('/admin/rooms');
$admin->post('/admin/rooms/1/delete', ['csrf_token' => $token]);
check('12', 'Phòng đã có suất: vẫn còn (kèm ghế), chuyển Ngừng hoạt động',
    scalar('SELECT status FROM rooms WHERE id = 1') === 'inactive' && (int) scalar('SELECT COUNT(*) FROM seats WHERE room_id = 1') === 40);
db()->exec("UPDATE rooms SET status = 'active' WHERE id = 1");

$token = $admin->csrf('/admin/showtimes');
$r = $admin->post("/admin/showtimes/$st7/cancel", ['csrf_token' => $token]);
check('12', 'Suất đã có vé: không hủy được, vẫn hoạt động', scalar('SELECT status FROM showtimes WHERE id = ?', [$st7]) === 'active'
    && str_contains(flashError($admin->follow($r)['body']), 'Chỉ hủy được suất chiếu chưa có vé'));
$r = $admin->post('/admin/showtimes/' . $st3b . '/cancel', ['csrf_token' => $token]);
check('12', 'Suất chưa có vé: chuyển Đã hủy, không bị xóa khỏi CSDL', scalar('SELECT status FROM showtimes WHERE id = ?', [$st3b]) === 'cancelled');
check('12', 'Không còn route xóa suất', $admin->post("/admin/showtimes/$st7/delete", ['csrf_token' => $token])['status'] === 404);

$token = $admin->csrf('/admin/cinemas');
$admin->post('/admin/cinemas/1/delete', ['csrf_token' => $token]);
check('12', 'Rạp đã có phòng: vẫn còn, chuyển Ngừng hoạt động', scalar('SELECT status FROM cinemas WHERE id = 1') === 'inactive'
    && (int) scalar('SELECT COUNT(*) FROM rooms WHERE cinema_id = 1') > 0);
db()->exec("UPDATE cinemas SET status = 'active' WHERE id = 1");
check('12', 'Thao tác ngừng / xóa được ghi log', (int) scalar("SELECT COUNT(*) FROM audit_logs WHERE action IN ('deactivate','delete','cancel') AND entity IN ('movie','room','showtime','cinema')") >= 5);

// ---------------------------------------------------------------- UC05: đăng nhập / đăng xuất
section('UC05 - Đăng nhập bằng email hoặc SĐT, thông báo, log, đăng xuất');

$c = new Client($A);
$r = $c->login('', '');
check('UC05', 'Thiếu trường -> "Cần nhập các trường bắt buộc"', str_contains($r['body'], 'Cần nhập các trường bắt buộc'));
$r = $c->login('user@example.com', 'sai-mat-khau');
$r2 = $c->login('khong-ton-tai@example.com', 'user123');
check('UC05', 'Sai mật khẩu / sai tài khoản -> cùng 1 câu "Tài khoản hoặc mật khẩu chưa đúng"',
    str_contains($r['body'], 'Tài khoản hoặc mật khẩu chưa đúng') && str_contains($r2['body'], 'Tài khoản hoặc mật khẩu chưa đúng'));
$r = $c->login('0911111111', 'user123');
check('UC05', 'Đăng nhập bằng số điện thoại', $r['status'] === 302 && $c->get('/account')['status'] === 200);
check('UC05', 'Ghi log đăng nhập thất bại và thành công',
    (int) scalar("SELECT COUNT(*) FROM audit_logs WHERE action = 'login_failed'") >= 2
    && (int) scalar("SELECT COUNT(*) FROM audit_logs WHERE action = 'login_success'") >= 1);
check('UC05', 'Đăng xuất bằng GET không còn (chống CSRF đăng xuất)', $c->get('/logout')['status'] === 404);
check('UC05', 'POST /logout không có token -> 419', $c->post('/logout', [])['status'] === 419);
$c->post('/logout', ['csrf_token' => $c->csrf('/account')]);
check('UC05', 'Đăng xuất hủy session: /account chuyển về đăng nhập', $c->get('/account')['status'] === 302);
$r = (new Client($A))->login('admin@cinema.com', 'admin123');
check('UC05', 'Admin đăng nhập -> trang quản trị', str_ends_with((string) $r['location'], '/admin'), 'location ' . $r['location']);

// ---------------------------------------------------------------- Ca 10: tài khoản bị khóa
section('Ca 10 - Tài khoản bị khóa (UC05, UC14)');

$adminId = userId('admin@cinema.com');
$token = $admin->csrf('/admin/customers');
$r = $admin->post("/admin/customers/$adminId/toggle", ['csrf_token' => $token]);
check('10', 'Admin không tự khóa được mình: "Không thể tự khóa tài khoản đang đăng nhập"',
    scalar('SELECT status FROM users WHERE id = ?', [$adminId]) === 'active'
    && str_contains(flashError($admin->follow($r)['body']), 'Không thể tự khóa tài khoản đang đăng nhập'));
$khang = userId('khang.le@example.com');
$admin->post("/admin/customers/$khang/toggle", ['csrf_token' => $token]);
check('10', 'Admin khóa tài khoản khác -> Bị khóa, có ghi log',
    scalar('SELECT status FROM users WHERE id = ?', [$khang]) === 'locked'
    && (int) scalar("SELECT COUNT(*) FROM audit_logs WHERE action = 'lock' AND entity = 'user' AND entity_id = ?", [$khang]) === 1);
db()->exec("UPDATE users SET status = 'active' WHERE id = $khang");
$list = $admin->get('/admin/customers?q=0987654321')['body'];
check('10', 'Tìm tài khoản theo SĐT', str_contains($list, 'khang.le@example.com') && !str_contains($list, 'ngoc.tran@example.com'));
check('10', 'Từ khóa > 100 ký tự bị từ chối', str_contains($admin->get('/admin/customers?q=' . str_repeat('a', 101))['body'], 'tối đa 100 ký tự'));


$victim = new Client($A);
$victim->login('khang.le@example.com', 'user123');
db()->prepare("UPDATE users SET status = 'locked' WHERE email = 'khang.le@example.com'")->execute();
$r = $victim->get('/account');
check('10', 'Session đang mở bị chặn ở request kế tiếp', $r['status'] === 302 && str_contains((string) $r['location'], '/login'), 'status ' . $r['status']);
$r = (new Client($A))->login('khang.le@example.com', 'user123');
check('10', 'Đăng nhập bị từ chối: "Tài khoản đang bị khóa, vui lòng liên hệ quản trị viên"',
    $r['status'] === 200 && str_contains($r['body'], 'Tài khoản đang bị khóa, vui lòng liên hệ quản trị viên'), 'status ' . $r['status']);
db()->prepare("UPDATE users SET status = 'active' WHERE email = 'khang.le@example.com'")->execute();

// ---------------------------------------------------------------- Ca 15: CSRF
section('Ca 15 - CSRF');

$r = $userA->post("/booking/$stP", ['seat_ids' => (string) seatId(1, 'B2')]);
check('15', 'Đặt vé không có CSRF token -> 419, không tạo đơn', $r['status'] === 419, 'status ' . $r['status']);
$r = $userA->post('/account', ['full_name' => 'Hacker', 'phone' => '0911111111']);
check('15', 'Sửa hồ sơ không có CSRF token -> 419', $r['status'] === 419, 'status ' . $r['status']);
$r = $admin->post("/admin/showtimes/$st7/cancel", []);
check('15', 'Thao tác admin không có CSRF token -> 419', $r['status'] === 419, 'status ' . $r['status']);

$movieX = movieId('Clayface');
$token = $admin->csrf("/admin/movies/$movieX/edit");
$movieRow = db()->query("SELECT * FROM movies WHERE id = $movieX")->fetch();
$admin->post("/admin/movies/$movieX", ['csrf_token' => $token, 'title' => $movieRow['title'], 'description' => '',
    'duration_minutes' => $movieRow['duration_minutes'], 'age_rating' => $movieRow['age_rating'], 'status' => $movieRow['status'],
    'release_date' => $movieRow['release_date'], 'trailer_url' => 'javascript:alert(document.cookie)']);
check('15', 'Trailer "javascript:..." bị từ chối (chống XSS qua href)', scalar('SELECT trailer_url FROM movies WHERE id = ?', [$movieX]) === null);

// ---------------------------------------------------------------- Ca 16: guest quay lại đúng suất
section('Ca 16 - Guest bấm Đặt vé -> đăng nhập -> quay lại đúng suất');

$st16 = makeShowtime($moviePG, 2, strtotime('+7 days 10:00'));
$guest = new Client($A);
$page = $guest->get("/showtimes/$st16/seats");
check('16', 'Guest xem được sơ đồ ghế và có nút "Đăng nhập để đặt vé"', $page['status'] === 200 && str_contains($page['body'], 'Đăng nhập để đặt vé'));
$r = $guest->get("/booking/$st16");
check('16', 'Bấm Đặt vé khi chưa đăng nhập -> chuyển tới /login', $r['status'] === 302 && str_ends_with((string) $r['location'], '/login'));
$r = $guest->login('user@example.com', 'user123');
check('16', 'Đăng nhập xong quay lại /booking/' . $st16, str_ends_with((string) $r['location'], "/booking/$st16"), 'location ' . $r['location']);
$r = $guest->follow($r);
check('16', '... rồi về đúng sơ đồ ghế của suất', str_ends_with((string) $r['location'], "/showtimes/$st16/seats"), 'location ' . $r['location']);

// ---------------------------------------------------------------- Ca 17: phim / phòng ngừng thì không bán
section('Ca 17 - Phim Ngừng chiếu / phòng Ngừng hoạt động -> suất không bán được (UC01, UC11)');

$st17 = makeShowtime($movieT13, 2, strtotime('+6 days 10:00') /* trong 7 ngày tra cứu (UC02) */);
$bookingsBefore = (int) scalar('SELECT COUNT(*) FROM bookings');
db()->exec("UPDATE movies SET status = 'ended' WHERE id = $movieT13");
$r = book($userA, $st17, [seatId(2, 'A1')]);
check('17', 'Phim Ngừng chiếu: đặt vé bị từ chối, quay về sơ đồ ghế, không tạo đơn',
    str_ends_with((string) $r['location'], "/showtimes/$st17/seats")
    && str_contains(flashError($userA->follow($r)['body']), 'không còn mở bán')
    && (int) scalar('SELECT COUNT(*) FROM bookings') === $bookingsBefore, 'location ' . $r['location']);
check('17', 'Lịch chiếu không còn liệt kê suất của phim Ngừng chiếu', !str_contains($userA->get('/showtimes')['body'], "/showtimes/$st17/seats"));
check('17', 'Trang chi tiết phim Ngừng chiếu -> 404', $userA->get("/movies/$movieT13")['status'] === 404);
db()->exec("UPDATE movies SET status = 'now_showing' WHERE id = $movieT13");

db()->exec('UPDATE rooms SET status = "inactive" WHERE id = 2');
$r = book($userA, $st17, [seatId(2, 'A1')]);
check('17', 'Phòng Ngừng hoạt động: đặt vé bị từ chối, không tạo đơn',
    bookingCodeFrom($r) === null && (int) scalar('SELECT COUNT(*) FROM bookings') === $bookingsBefore, 'location ' . $r['location']);
check('17', 'Lịch chiếu không còn liệt kê suất của phòng Ngừng hoạt động', !str_contains($userA->get('/showtimes')['body'], "/showtimes/$st17/seats"));
db()->exec('UPDATE rooms SET status = "active" WHERE id = 2');
check('17', 'Hoạt động lại thì suất hiện lại trong lịch chiếu', str_contains($userA->get('/showtimes')['body'], "/showtimes/$st17/seats"));

// ---------------------------------------------------------------- Ca 18: đánh giá phim (BR08)
section('Ca 18 - Chỉ người đã xem phim mới đánh giá được (BR08)');

$reviewsBefore = (int) scalar('SELECT COUNT(*) FROM reviews');
$token = $userB->csrf('/account');
$r = $userB->post("/movies/$movieT13/reviews", ['csrf_token' => $token, 'rating' => 5, 'comment' => 'Chưa xem mà vẫn khen']);
check('18', 'Chưa xem phim -> bị từ chối, không lưu đánh giá',
    str_contains(flashError($userB->follow($r)['body']), 'sau khi đã xem phim')
    && (int) scalar('SELECT COUNT(*) FROM reviews') === $reviewsBefore);
check('18', 'Chưa xem phim -> trang phim không hiện form đánh giá', !str_contains($userB->get("/movies/$movieT13")['body'], '/reviews"'));

// user@example.com có đơn VEDEMO0011 của suất Shaun the Sheep đã chiếu xong (dữ liệu mẫu)
$token = $userA->csrf('/account');
$r = $userA->post("/movies/$moviePG/reviews", ['csrf_token' => $token, 'rating' => 4, 'comment' => str_repeat('x', 600)]);
check('18', 'Nhận xét > 500 ký tự -> báo lỗi, không ra 500', $r['status'] === 302
    && str_contains(flashError($userA->follow($r)['body']), 'tối đa 500 ký tự'), 'status ' . $r['status']);
$r = $userA->post("/movies/$moviePG/reviews", ['csrf_token' => $token, 'rating' => 4, 'comment' => 'Xem xong rồi, rất vui']);
check('18', 'Đã xem xong phim -> đánh giá được',
    scalar('SELECT comment FROM reviews WHERE movie_id = ? AND user_id = ?', [$moviePG, userId('user@example.com')]) === 'Xem xong rồi, rất vui');

// ---------------------------------------------------------------- Ca 19: dữ liệu sai không gây lỗi 500
section('Ca 19 - Dữ liệu nhập sai -> báo lỗi rõ ràng, không "Lỗi hệ thống"');

$token = $admin->csrf('/admin/genres');
$r = $admin->post('/admin/genres', ['csrf_token' => $token, 'name' => 'Hài']);
check('19', 'Thêm thể loại trùng tên -> "đã tồn tại"', $r['status'] === 302
    && str_contains(flashError($admin->follow($r)['body']), 'đã tồn tại'), 'status ' . $r['status']);
$r = $admin->post('/admin/genres', ['csrf_token' => $token, 'name' => 'Thể loại Kiểm thử']);
check('19', 'Thêm thể loại được ghi log',(int) scalar("SELECT COUNT(*) FROM audit_logs WHERE entity = 'genre'") === 1);

$moviesBefore = (int) scalar('SELECT COUNT(*) FROM movies');
$r = $admin->post('/admin/movies', ['csrf_token' => $token, 'title' => 'Phim Sai Trạng Thái', 'duration_minutes' => 90,
    'age_rating' => 'P', 'status' => 'bogus']);
check('19', 'Phim có trạng thái không hợp lệ -> báo lỗi, không tạo phim', $r['status'] === 302
    && str_contains(flashError($admin->follow($r)['body']), 'Trạng thái phim không hợp lệ')
    && (int) scalar('SELECT COUNT(*) FROM movies') === $moviesBefore, 'status ' . $r['status']);
$r = $admin->post('/admin/movies', ['csrf_token' => $token, 'title' => '', 'duration_minutes' => 90, 'age_rating' => 'P', 'status' => 'coming_soon']);
check('19', 'Phim không có tên -> báo lỗi', str_contains(flashError($admin->follow($r)['body']), 'Tên phim không được để trống'));

$r = $admin->post('/admin/cinemas', ['csrf_token' => $token, 'name' => '', 'address' => 'x', 'city' => 'y', 'status' => 'active']);
check('19', 'Rạp không có tên -> báo lỗi, không tạo rạp', $r['status'] === 302
    && str_contains(flashError($admin->follow($r)['body']), 'Tên rạp không được để trống')
    && (int) scalar("SELECT COUNT(*) FROM cinemas WHERE name = ''") === 0, 'status ' . $r['status']);
check('19', 'Sửa rạp không tồn tại -> 404', $admin->post('/admin/cinemas/99999', ['csrf_token' => $token, 'name' => 'a',
    'address' => 'b', 'city' => 'c', 'status' => 'active'])['status'] === 404);

$guest = new Client($A);
$token = $guest->csrf('/register');
$r = $guest->post('/register', ['csrf_token' => $token, 'full_name' => 'Khách Mới', 'email' => 'khach.moi@example.com',
    'phone' => str_repeat('9', 30), 'password' => 'abc123', 'password_confirm' => 'abc123']);
check('19', 'Đăng ký với SĐT sai định dạng -> báo lỗi, không tạo tài khoản', $r['status'] === 200
    && str_contains($r['body'], 'Số điện thoại phải gồm 10 chữ số')
    && (int) scalar("SELECT COUNT(*) FROM users WHERE email = 'khach.moi@example.com'") === 0, 'status ' . $r['status']);
$r = $guest->post('/register', ['csrf_token' => $token, 'email' => 'thieu.ten@example.com', 'password' => 'abc123', 'password_confirm' => 'abc123']);
check('19', 'Đăng ký thiếu trường họ tên -> báo lỗi, không ra 500', $r['status'] === 200
    && str_contains($r['body'], 'Vui lòng nhập đầy đủ'), 'status ' . $r['status']);

// ---------------------------------------------------------------- Ca 20: FR09
section('Ca 20 - Loại ghế & phụ thu, sửa phòng, ghế Ngừng sử dụng / Lối đi (FR09, UC11)');

$token = $admin->csrf('/admin/seat-types');
$admin->post('/admin/seat-types', ['csrf_token' => $token, 'name' => 'Sweetbox', 'surcharge' => '30000']);
$sweetbox = (int) scalar("SELECT id FROM seat_types WHERE name = 'Sweetbox'");
check('20', 'Thêm loại ghế mới kèm phụ thu', $sweetbox > 0 && (int) scalar('SELECT surcharge FROM seat_types WHERE id = ?', [$sweetbox]) === 30000);
$r = $admin->post('/admin/seat-types', ['csrf_token' => $token, 'name' => 'Sweetbox', 'surcharge' => '1000']);
check('20', 'Tên loại ghế trùng -> báo lỗi', str_contains(flashError($admin->follow($r)['body']), 'đã tồn tại'));
$r = $admin->post('/admin/seat-types', ['csrf_token' => $token, 'name' => 'Âm', 'surcharge' => '-5']);
check('20', 'Phụ thu âm / không phải số -> báo lỗi', str_contains(flashError($admin->follow($r)['body']), 'Phụ thu phải là số nguyên')
    && (int) scalar("SELECT COUNT(*) FROM seat_types WHERE name = 'Âm'") === 0);

$vip = (int) scalar("SELECT id FROM seat_types WHERE name = 'VIP'");
$st20 = makeShowtime($moviePG, 2, strtotime('+10 days 10:00'), 75000);
$oldCode = bookingCodeFrom(book($userA, $st20, [seatId(2, 'C1')]));
$admin->post("/admin/seat-types/$vip", ['csrf_token' => $token, 'name' => 'VIP', 'surcharge' => '20000']);
$newCode = bookingCodeFrom(book($userB, $st20, [seatId(2, 'C2')]));
check('20', 'Đổi phụ thu VIP: vé mới theo giá mới (95.000), vé cũ giữ giá cũ (85.000)',
    (int) scalar('SELECT total_amount FROM bookings WHERE booking_code = ?', [$newCode]) === 95000
    && (int) scalar('SELECT total_amount FROM bookings WHERE booking_code = ?', [$oldCode]) === 85000);
$admin->post("/admin/seat-types/$vip", ['csrf_token' => $token, 'name' => 'VIP', 'surcharge' => '10000']);

$r = $admin->post("/admin/seat-types/$vip/delete", ['csrf_token' => $token]);
check('20', 'Loại ghế đang được dùng -> không xóa được', (int) scalar('SELECT COUNT(*) FROM seat_types WHERE id = ?', [$vip]) === 1
    && str_contains(flashError($admin->follow($r)['body']), 'không xóa được'));
$admin->post("/admin/seat-types/$sweetbox/delete", ['csrf_token' => $token]);
check('20', 'Loại ghế chưa dùng -> xóa được', (int) scalar('SELECT COUNT(*) FROM seat_types WHERE id = ?', [$sweetbox]) === 0);

$token = $admin->csrf('/admin/rooms/create');
$admin->post('/admin/rooms', ['csrf_token' => $token, 'cinema_id' => 2, 'name' => 'Phòng Test FR09', 'room_type' => 'deluxe',
    'rows' => 3, 'cols' => 4, 'seat_type_id' => 1]);
$room20 = (int) scalar("SELECT id FROM rooms WHERE name = 'Phòng Test FR09'");
check('20', 'Tạo phòng Deluxe 3x4 -> 12 ghế', $room20 > 0 && scalar('SELECT room_type FROM rooms WHERE id = ?', [$room20]) === 'deluxe'
    && (int) scalar('SELECT COUNT(*) FROM seats WHERE room_id = ?', [$room20]) === 12);
$token = $admin->csrf("/admin/rooms/$room20/edit");
$admin->post("/admin/rooms/$room20", ['csrf_token' => $token, 'name' => 'Phòng Test Đã Sửa', 'room_type' => 'standard', 'status' => 'active']);
check('20', 'Sửa tên + loại phòng', scalar('SELECT CONCAT(name, "|", room_type) FROM rooms WHERE id = ?', [$room20]) === 'Phòng Test Đã Sửa|standard');
$r = $admin->post("/admin/rooms/$room20", ['csrf_token' => $token, 'name' => 'Phòng 1', 'room_type' => 'standard', 'status' => 'active']);
check('20', 'Sửa trùng tên phòng khác trong cùng rạp -> báo lỗi', str_contains(flashError($admin->follow($r)['body']), 'đã tồn tại'));

$token = $admin->csrf("/admin/rooms/$room20/seats");
$a1 = seatId($room20, 'A1');
$a2 = seatId($room20, 'A2');
$admin->post("/admin/rooms/$room20/seats", ['csrf_token' => $token, 'seat' => [$a1 => 'inactive', $a2 => 'aisle']]);
check('20', 'Đặt ghế Ngừng sử dụng / Lối đi', scalar('SELECT status FROM seats WHERE id = ?', [$a1]) === 'inactive'
    && scalar('SELECT status FROM seats WHERE id = ?', [$a2]) === 'aisle');
$st20b = makeShowtime($moviePG, $room20, strtotime('+10 days 14:00'));
$r = holdSeat($userA, $st20b, $a1);
check('20', 'Ghế Ngừng sử dụng không giữ / đặt được', !holdOk($r));
$soldSeat = seatId(2, 'C1');
$admin->post('/admin/rooms/2/seats', ['csrf_token' => $admin->csrf('/admin/rooms/2/seats'), 'seat' => [$soldSeat => 'aisle']]);
check('20', 'Ghế đã có vé không đổi thành lối đi được', scalar('SELECT status FROM seats WHERE id = ?', [$soldSeat]) === 'active');

// ---------------------------------------------------------------- Ca 13: thống kê
section('Ca 13 - Thống kê: chỉ tính đơn Đã xác nhận, Từ ngày > Đến ngày báo lỗi (FR12, UC15)');

$today = date('Y-m-d');
$r = $admin->get('/admin?from=' . $today . '&to=' . date('Y-m-d', strtotime('-1 day')));
check('13', 'Từ ngày > Đến ngày -> "Đến ngày phải sau hoặc bằng Từ ngày"', str_contains($r['body'], 'Đến ngày phải sau hoặc bằng Từ ngày'));

$st13 = makeShowtime($moviePG, $room20, strtotime('+11 days 10:00'), 75000);
bookingCodeFrom(book($userA, $st13, [seatId($room20, 'A3'), seatId($room20, 'A4')]));
$cancelCode = bookingCodeFrom(book($userB, $st13, [seatId($room20, 'B1')]));
$admin->post("/admin/bookings/$cancelCode/cancel", ['csrf_token' => $admin->csrf("/admin/bookings/$cancelCode"), 'cancel_reason' => 'Test thống kê']);
$stats = $admin->get("/admin?from=$today&to=$today&room_id=$room20")['body'];
$card = fn (string $label) => preg_match('#<div class="value">([^<]+)</div><div class="label">' . $label . '#u', $stats, $m) ? $m[1] : '';
check('13', 'Vé đã bán = 2 (bỏ 1 vé của đơn đã hủy)', $card('Vé đã bán') === '2', 'vé = ' . $card('Vé đã bán'));
check('13', 'Doanh thu = 150.000đ theo giá trong vé', $card('Doanh thu') === '150.000đ', 'doanh thu = ' . $card('Doanh thu'));
check('13', 'Có bảng theo phim và biểu đồ', str_contains($stats, 'Theo phim') && str_contains($stats, 'stats-chart'));
$empty = $admin->get('/admin?from=2020-01-01&to=2020-01-02')['body'];
check('13', 'Khoảng không có đơn -> "Không có dữ liệu trong khoảng đã chọn"', str_contains($empty, 'Không có dữ liệu trong khoảng đã chọn'));

// ---------------------------------------------------------------- Ca 21: tra cứu suất
section('Ca 21 - Tra cứu suất: số ghế trống, suất đóng bán hiện mờ, 7 ngày tới (FR02, UC02)');

$st21 = makeShowtime($moviePG, $room20, time() + 10 * 60);
$page = $userA->get("/showtimes?room_id=$room20&date=" . date('Y-m-d', time() + 10 * 60))['body'];
check('21', 'Suất còn 10 phút: hiện "Đã đóng bán", không có link đặt vé', str_contains($page, 'is-closed')
    && str_contains($page, 'Đã đóng bán') && !str_contains($page, "/showtimes/$st21/seats"));
$st21b = makeShowtime($moviePG, $room20, strtotime('+2 days 09:00'));
$page = $userA->get("/showtimes?room_id=$room20&date=" . date('Y-m-d', strtotime('+2 days')))['body'];
$activeSeats = (int) scalar("SELECT COUNT(*) FROM seats WHERE room_id = ? AND status = 'active'", [$room20]);
check('21', "Hiện số ghế còn trống của suất (Còn $activeSeats ghế)", str_contains($page, "Còn $activeSeats ghế trống"));
$page = $userA->get('/showtimes?date=' . date('Y-m-d', strtotime('+9 days')))['body'];
check('21', 'Chọn ngày ngoài 7 ngày tới -> báo lỗi', str_contains($page, 'Chỉ tra cứu được lịch chiếu trong 7 ngày tới'));
$page = $userA->get("/showtimes?room_id=$room20&movie_id=" . movieId('Hành Tinh Cát Đỏ'))['body'];
check('21', 'Không có suất -> "Chưa có lịch chiếu cho lựa chọn này"', str_contains($page, 'Chưa có lịch chiếu cho lựa chọn này'));

// ---------------------------------------------------------------- Lỗi hệ thống
section('Lỗi CSDL không lộ chi tiết SQL');
db()->exec('RENAME TABLE showtime_seats TO showtime_seats_tmp');
$r = $userA->get("/showtimes/$st16/seats");
$api = $userA->get("/api.php?action=seats&showtime_id=$st16");
db()->exec('RENAME TABLE showtime_seats_tmp TO showtime_seats');
check('NFR', 'API trả "Lỗi hệ thống, vui lòng thử lại." và không có chữ SQLSTATE',
    $api['status'] === 500 && str_contains($api['body'], 'Lỗi hệ thống') && !str_contains($api['body'], 'SQLSTATE'), $api['body']);

// ---------------------------------------------------------------- Tổng kết
require __DIR__ . '/account_tests.php';
$passed = count(array_filter($results, fn ($r) => $r[2]));
printf("\nTỔNG: %d/%d kiểm tra đạt\n", $passed, count($results));
exit($passed === count($results) ? 0 : 1);
