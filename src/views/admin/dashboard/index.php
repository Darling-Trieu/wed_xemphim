<div class="stat-cards">
    <div class="stat-card">
        <div class="value"><?= (int) $movieCount ?></div>
        <div class="label">Tổng số phim</div>
    </div>
    <div class="stat-card">
        <div class="value"><?= (int) $showtimeCount ?></div>
        <div class="label">Suất chiếu</div>
    </div>
    <div class="stat-card">
        <div class="value"><?= (int) $ticketCount ?></div>
        <div class="label">Vé đã bán</div>
    </div>
    <div class="stat-card">
        <div class="value"><?= number_format($revenue, 0, ',', '.') ?>đ</div>
        <div class="label">Doanh thu</div>
    </div>
    <div class="stat-card">
        <div class="value"><?= (int) $customerCount ?></div>
        <div class="label">Khách hàng</div>
    </div>
</div>

<div class="chart-card">
    <h3 style="margin-top:0;">Doanh thu 14 ngày gần nhất</h3>
    <canvas id="revenue-chart" data-api-url="<?= BASE_URL ?>/api.php" height="90"></canvas>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
