<h1 class="section-title">Lịch sử đặt vé</h1>

<div class="table-responsive">
<table class="data-table">
    <thead>
        <tr>
            <th>Mã đơn</th>
            <th>Phim</th>
            <th>Rạp</th>
            <th>Suất chiếu</th>
            <th class="num">Tổng tiền</th>
            <th>Trạng thái</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($bookings as $b): ?>
            <tr>
                <td><?= htmlspecialchars($b['booking_code']) ?></td>
                <td><?= htmlspecialchars($b['movie_title']) ?></td>
                <td><?= htmlspecialchars($b['cinema_name']) ?></td>
                <td><?= htmlspecialchars(date('d/m/Y', strtotime($b['show_date']))) ?> <?= htmlspecialchars(substr($b['start_time'], 0, 5)) ?></td>
                <td class="num"><?= number_format($b['total_amount'], 0, ',', '.') ?>đ</td>
                <td>
                    <?= Booking::statusLabel($b) ?>
                    <?php if ($b['refund_amount'] !== null): ?>
                        <br><span class="movie-meta">Hoàn <?= number_format($b['refund_amount'], 0, ',', '.') ?>đ</span>
                    <?php endif; ?>
                </td>
                <td><a href="<?= BASE_URL ?>/account/bookings/<?= htmlspecialchars($b['booking_code']) ?>">Xem</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($bookings)): ?>
            <tr><td colspan="7">Bạn chưa đặt vé nào.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>
