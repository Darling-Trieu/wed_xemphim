<h1>Chi tiết đơn vé <?= htmlspecialchars($booking['booking_code']) ?></h1>

<div class="admin-card">
    <dl>
        <dt>Khách hàng</dt><dd><?= htmlspecialchars($booking['full_name'] ?? '') ?> (<?= htmlspecialchars($booking['email'] ?? '') ?>)</dd>
        <dt>Phim</dt><dd><?= htmlspecialchars($booking['movie_title']) ?></dd>
        <dt>Rạp / Phòng</dt><dd><?= htmlspecialchars($booking['cinema_name']) ?> - <?= htmlspecialchars($booking['room_name']) ?></dd>
        <dt>Suất chiếu</dt>
        <dd><?= htmlspecialchars(date('d/m/Y', strtotime($booking['show_date']))) ?> lúc <?= htmlspecialchars(substr($booking['start_time'], 0, 5)) ?></dd>
        <dt>Tổng tiền</dt><dd><strong><?= number_format($booking['total_amount'], 0, ',', '.') ?>đ</strong></dd>
        <dt>Trạng thái</dt><dd><?= Booking::statusLabel($booking) ?></dd>
        <dt>Thanh toán</dt><dd><?= Booking::paymentLabel($booking['payment_status']) ?></dd>
        <dt>Ngày đặt</dt><dd><?= htmlspecialchars(date('d/m/Y H:i', strtotime($booking['created_at']))) ?></dd>
        <?php if ($booking['refund_amount'] !== null): ?>
            <dt>Đã hoàn tiền</dt>
            <dd><strong><?= number_format($booking['refund_amount'], 0, ',', '.') ?>đ</strong> lúc <?= htmlspecialchars(date('d/m/Y H:i', strtotime($booking['refunded_at']))) ?></dd>
            <dt>Lý do hủy</dt><dd><?= htmlspecialchars($booking['cancel_reason'] ?? 'Không ghi lý do') ?></dd>
        <?php endif; ?>
    </dl>
    <?php include __DIR__ . '/../../partials/ticket_table.php'; ?>
    <?php if ($booking['status'] === 'paid'): ?>
        <form method="post" action="<?= BASE_URL ?>/admin/bookings/<?= htmlspecialchars($booking['booking_code']) ?>/cancel"
              onsubmit="return confirm('Hủy đơn vé này và hoàn <?= number_format($booking['total_amount'], 0, ',', '.') ?>đ cho khách? Bấm Cancel để bỏ qua.');">
            <?= Csrf::field() ?>
            <div class="form-group">
                <label>Lý do hủy (bắt buộc, tối đa 255 ký tự)</label>
                <input class="form-control" type="text" name="cancel_reason" maxlength="255" required placeholder="VD: Khách yêu cầu, suất chiếu bị hủy...">
            </div>
            <button type="submit" class="btn btn-danger">Hủy đơn &amp; hoàn tiền</button>
        </form>
    <?php endif; ?>
    <a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/bookings">&larr; Quay lại</a>
</div>
