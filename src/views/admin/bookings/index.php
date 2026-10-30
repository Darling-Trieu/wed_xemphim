<h1>Quản lý đơn đặt vé</h1>
<p class="movie-meta">Tổng tiền đã hoàn cho khách: <strong><?= number_format($totalRefunded, 0, ',', '.') ?>đ</strong></p>

<div class="table-responsive">
<table class="data-table">
    <thead>
        <tr><th>Mã vé</th><th>Khách hàng</th><th>Phim</th><th>Rạp</th><th>Suất chiếu</th><th class="num">Tổng tiền</th><th>Trạng thái</th><th></th></tr>
    </thead>
    <tbody>
        <?php foreach ($bookings as $b): ?>
            <tr>
                <td><?= htmlspecialchars($b['booking_code']) ?></td>
                <td><?= htmlspecialchars($b['full_name']) ?><br><span class="movie-meta"><?= htmlspecialchars($b['email']) ?></span></td>
                <td><?= htmlspecialchars($b['movie_title']) ?></td>
                <td><?= htmlspecialchars($b['cinema_name']) ?></td>
                <td><?= htmlspecialchars(date('d/m/Y', strtotime($b['show_date']))) ?> <?= htmlspecialchars(substr($b['start_time'], 0, 5)) ?></td>
                <td class="num"><?= number_format($b['total_amount'], 0, ',', '.') ?>đ</td>
                <td>
                    <?= Booking::statusLabel($b) ?>
                    <br><span class="movie-meta">TT: <?= Booking::paymentLabel($b['payment_status']) ?></span>
                    <?php if ($b['refund_amount'] !== null): ?>
                        <br><span class="movie-meta">Hoàn <?= number_format($b['refund_amount'], 0, ',', '.') ?>đ</span>
                    <?php endif; ?>
                </td>
                <td class="actions-cell">
                    <a class="btn btn-small btn-secondary" href="<?= BASE_URL ?>/admin/bookings/<?= htmlspecialchars($b['booking_code']) ?>">Xem</a>
                    <?php if ($b['status'] === 'paid'): ?>
                        <?php // Hủy đơn bắt buộc nhập lý do nên thực hiện ở trang chi tiết ?>
                        <a class="btn btn-small btn-danger" href="<?= BASE_URL ?>/admin/bookings/<?= htmlspecialchars($b['booking_code']) ?>">Hủy vé</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($bookings)): ?>
            <tr><td colspan="8">Chưa có đơn đặt vé nào.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>
