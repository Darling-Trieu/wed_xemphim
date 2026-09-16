<h1 class="section-title">Lịch sử đặt vé</h1>

<table class="data-table">
    <thead>
        <tr>
            <th>Mã vé</th>
            <th>Phim</th>
            <th>Rạp</th>
            <th>Suất chiếu</th>
            <th>Tổng tiền</th>
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
                <td><?= number_format($b['total_amount'], 0, ',', '.') ?>đ</td>
                <td><?= $b['status'] === 'paid' ? 'Đã thanh toán' : 'Đã hủy' ?></td>
                <td><a href="<?= BASE_URL ?>/account/bookings/<?= htmlspecialchars($b['booking_code']) ?>">Xem</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($bookings)): ?>
            <tr><td colspan="7">Bạn chưa đặt vé nào.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
