<div class="ticket-card">
    <h1>Chi tiết vé</h1>
    <dl>
        <dt>Mã đơn</dt><dd><strong><?= htmlspecialchars($booking['booking_code']) ?></strong></dd>
        <dt>Phim</dt><dd><?= htmlspecialchars($booking['movie_title']) ?></dd>
        <dt>Rạp / Phòng</dt><dd><?= htmlspecialchars($booking['cinema_name']) ?> - <?= htmlspecialchars($booking['room_name']) ?></dd>
        <dt>Suất chiếu</dt>
        <dd><?= htmlspecialchars(date('d/m/Y', strtotime($booking['show_date']))) ?> lúc <?= htmlspecialchars(substr($booking['start_time'], 0, 5)) ?></dd>
        <dt>Thời điểm đặt</dt><dd><?= htmlspecialchars(date('d/m/Y H:i', strtotime($booking['created_at']))) ?></dd>
        <dt>Trạng thái</dt><dd><?= Booking::statusLabel($booking) ?></dd>
        <dt>Thanh toán</dt><dd><?= Booking::paymentLabel($booking['payment_status']) ?> (mô phỏng)</dd>
        <dt>Tổng tiền</dt><dd><strong><?= number_format($booking['total_amount'], 0, ',', '.') ?>đ</strong></dd>
        <?php if ($booking['refund_amount'] !== null): ?>
            <dt>Đã hoàn tiền</dt>
            <dd><strong><?= number_format($booking['refund_amount'], 0, ',', '.') ?>đ</strong> lúc <?= htmlspecialchars(date('d/m/Y H:i', strtotime($booking['refunded_at']))) ?></dd>
            <?php if (!empty($booking['cancel_reason'])): ?>
                <dt>Lý do hủy</dt><dd><?= htmlspecialchars($booking['cancel_reason']) ?></dd>
            <?php endif; ?>
        <?php endif; ?>
    </dl>
    <?php include __DIR__ . '/../partials/ticket_table.php'; ?>
    <p><a class="btn btn-secondary" href="<?= BASE_URL ?>/account/bookings">&larr; Quay lại lịch sử vé</a></p>
</div>
