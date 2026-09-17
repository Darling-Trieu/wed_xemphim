<h1>Quản lý đơn đặt vé</h1>

<div class="table-responsive">
<table class="data-table">
    <thead>
        <tr><th>Mã vé</th><th>Khách hàng</th><th>Phim</th><th>Rạp</th><th>Suất chiếu</th><th>Tổng tiền</th><th>Trạng thái</th><th></th></tr>
    </thead>
    <tbody>
        <?php foreach ($bookings as $b): ?>
            <tr>
                <td><?= htmlspecialchars($b['booking_code']) ?></td>
                <td><?= htmlspecialchars($b['full_name']) ?><br><span class="movie-meta"><?= htmlspecialchars($b['email']) ?></span></td>
                <td><?= htmlspecialchars($b['movie_title']) ?></td>
                <td><?= htmlspecialchars($b['cinema_name']) ?></td>
                <td><?= htmlspecialchars(date('d/m/Y', strtotime($b['show_date']))) ?> <?= htmlspecialchars(substr($b['start_time'], 0, 5)) ?></td>
                <td><?= number_format($b['total_amount'], 0, ',', '.') ?>đ</td>
                <td><?= $b['status'] === 'paid' ? 'Đã thanh toán' : 'Đã hủy' ?></td>
                <td class="actions-cell">
                    <a class="btn btn-small btn-secondary" href="<?= BASE_URL ?>/admin/bookings/<?= htmlspecialchars($b['booking_code']) ?>">Xem</a>
                    <?php if ($b['status'] === 'paid'): ?>
                        <form method="post" action="<?= BASE_URL ?>/admin/bookings/<?= htmlspecialchars($b['booking_code']) ?>/cancel" onsubmit="return confirm('Hủy đơn vé này?');">
                            <?= Csrf::field() ?>
                            <button type="submit" class="btn btn-small btn-danger">Hủy vé</button>
                        </form>
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
