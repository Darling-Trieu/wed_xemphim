<h1>Chi tiết đơn vé <?= htmlspecialchars($booking['booking_code']) ?></h1>

<div class="admin-card">
    <dl>
        <dt>Khách hàng</dt><dd><?= htmlspecialchars($booking['full_name'] ?? '') ?></dd>
        <dt>Phim</dt><dd><?= htmlspecialchars($booking['movie_title']) ?></dd>
        <dt>Rạp</dt><dd><?= htmlspecialchars($booking['cinema_name']) ?> - <?= htmlspecialchars($booking['room_name']) ?></dd>
        <dt>Suất chiếu</dt>
        <dd><?= htmlspecialchars(date('d/m/Y', strtotime($booking['show_date']))) ?> lúc <?= htmlspecialchars(substr($booking['start_time'], 0, 5)) ?></dd>
        <dt>Ghế</dt>
        <dd><?= htmlspecialchars(implode(', ', array_map(fn($s) => $s['seat_row'] . $s['seat_number'], $seats))) ?></dd>
        <dt>Tổng tiền</dt><dd><strong><?= number_format($booking['total_amount'], 0, ',', '.') ?>đ</strong></dd>
        <dt>Trạng thái</dt><dd><?= $booking['status'] === 'paid' ? 'Đã thanh toán' : 'Đã hủy' ?></dd>
        <dt>Ngày đặt</dt><dd><?= htmlspecialchars(date('d/m/Y H:i', strtotime($booking['created_at']))) ?></dd>
    </dl>
    <?php if ($booking['status'] === 'paid'): ?>
        <form method="post" action="<?= BASE_URL ?>/admin/bookings/<?= htmlspecialchars($booking['booking_code']) ?>/cancel" onsubmit="return confirm('Hủy đơn vé này?');">
            <?= Csrf::field() ?>
            <button type="submit" class="btn btn-danger">Hủy đơn vé</button>
        </form>
    <?php endif; ?>
    <a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/bookings">&larr; Quay lại</a>
</div>
