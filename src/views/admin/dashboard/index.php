<?php
/**
 * @var array $filters
 * @var ?string $error
 * @var ?array $summary
 * @var array $byDay
 * @var array $byMovie
 */
$money = static fn (int $amount): string => number_format($amount, 0, ',', '.') . 'đ';
$dayChart = [
    'labels'  => array_map(static fn ($d) => date('d/m', strtotime($d['day'])), $byDay),
    'revenue' => array_column($byDay, 'revenue'),
    'tickets' => array_column($byDay, 'tickets'),
];
$movieChart = [
    'labels'  => array_column($byMovie, 'movie_title'),
    'revenue' => array_column($byMovie, 'revenue'),
];
$hasData = $summary && $summary['tickets'] > 0;
?>
<form class="admin-card filter-bar" method="get" action="<?= BASE_URL ?>/admin" style="margin-bottom:20px;">
    <label>Từ ngày <input class="form-control" type="date" name="from" required value="<?= htmlspecialchars($filters['from']) ?>"></label>
    <label>Đến ngày <input class="form-control" type="date" name="to" required value="<?= htmlspecialchars($filters['to']) ?>"></label>
    <select class="form-control" name="movie_id">
        <option value="">-- Tất cả phim --</option>
        <?php foreach ($movies as $movie): ?>
            <option value="<?= (int) $movie['id'] ?>" <?= $filters['movie_id'] === (int) $movie['id'] ? 'selected' : '' ?>><?= htmlspecialchars($movie['title']) ?></option>
        <?php endforeach; ?>
    </select>
    <select class="form-control" name="cinema_id">
        <option value="">-- Tất cả rạp --</option>
        <?php foreach ($cinemas as $cinema): ?>
            <option value="<?= (int) $cinema['id'] ?>" <?= $filters['cinema_id'] === (int) $cinema['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cinema['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select class="form-control" name="room_id">
        <option value="">-- Tất cả phòng --</option>
        <?php foreach ($rooms as $room): ?>
            <option value="<?= (int) $room['id'] ?>" <?= $filters['room_id'] === (int) $room['id'] ? 'selected' : '' ?>><?= htmlspecialchars($room['cinema_name'] . ' - ' . $room['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn">Thống kê</button>
</form>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php else: ?>
    <div class="stat-cards">
        <div class="stat-card"><div class="value"><?= (int) $summary['tickets'] ?></div><div class="label">Vé đã bán</div></div>
        <div class="stat-card"><div class="value"><?= $money($summary['revenue']) ?></div><div class="label">Doanh thu</div></div>
        <div class="stat-card"><div class="value"><?= (int) $summary['bookings'] ?></div><div class="label">Đơn đã xác nhận</div></div>
        <div class="stat-card"><div class="value"><?= (int) $movieCount ?></div><div class="label">Tổng số phim</div></div>
        <div class="stat-card"><div class="value"><?= (int) $customerCount ?></div><div class="label">Khách hàng</div></div>
    </div>
    <p class="movie-meta">Chỉ tính đơn Đã xác nhận (đơn đã hủy không tính); doanh thu cộng theo giá lưu trong vé; ngày = ngày đặt vé.</p>

    <?php if (!$hasData): ?>
        <div class="admin-card"><p class="empty-note">Không có dữ liệu trong khoảng đã chọn</p></div>
    <?php else: ?>
        <div class="chart-card">
            <h3>Doanh thu theo ngày</h3>
            <div class="chart-canvas" style="height:280px;">
                <canvas class="stats-chart" data-kind="line" data-series="<?= htmlspecialchars(json_encode($dayChart, JSON_UNESCAPED_UNICODE)) ?>"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <h3>Doanh thu theo phim</h3>
            <?php // Thanh ngang: mỗi phim một hàng nên chiều cao tăng theo số phim ?>
            <div class="chart-canvas" style="height:<?= max(180, 44 * count($byMovie)) ?>px;">
                <canvas class="stats-chart" data-kind="bar" data-series="<?= htmlspecialchars(json_encode($movieChart, JSON_UNESCAPED_UNICODE)) ?>"></canvas>
            </div>
        </div>

        <h3>Theo phim</h3>
        <div class="table-responsive" style="margin-bottom:20px;">
            <table class="data-table">
                <thead><tr><th>Phim</th><th class="num">Số vé</th><th class="num">Doanh thu</th></tr></thead>
                <tbody>
                    <?php foreach ($byMovie as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['movie_title']) ?></td>
                            <td class="num"><?= (int) $row['tickets'] ?></td>
                            <td class="num"><?= $money($row['revenue']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h3>Theo ngày</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Ngày</th><th class="num">Số vé</th><th class="num">Doanh thu</th></tr></thead>
                <tbody>
                    <?php foreach ($byDay as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($row['day']))) ?></td>
                            <td class="num"><?= (int) $row['tickets'] ?></td>
                            <td class="num"><?= $money($row['revenue']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
<?php endif; ?>

<script src="<?= BASE_URL ?>/js/vendor/chart.umd.js"></script>
