<div class="ticket-card">
    <h1>🎉 Đặt vé thành công!</h1>
    <dl>
        <dt>Mã vé</dt><dd><strong><?= htmlspecialchars($booking['booking_code']) ?></strong></dd>
        <dt>Phim</dt><dd><?= htmlspecialchars($booking['movie_title']) ?></dd>
        <dt>Rạp</dt><dd><?= htmlspecialchars($booking['cinema_name']) ?> - <?= htmlspecialchars($booking['room_name']) ?></dd>
        <dt>Suất chiếu</dt>
        <dd><?= htmlspecialchars(date('d/m/Y', strtotime($booking['show_date']))) ?> lúc <?= htmlspecialchars(substr($booking['start_time'], 0, 5)) ?></dd>
        <dt>Ghế</dt>
        <dd>
            <?= htmlspecialchars(implode(', ', array_map(fn($s) => $s['seat_row'] . $s['seat_number'], $seats))) ?>
        </dd>
        <dt>Tổng tiền</dt><dd><strong><?= number_format($booking['total_amount'], 0, ',', '.') ?>đ</strong></dd>
        <dt>Trạng thái</dt><dd><?= $booking['status'] === 'paid' ? 'Đã thanh toán' : 'Đã hủy' ?></dd>
    </dl>
    <p style="margin-top:20px;">
        <a class="btn" href="<?= BASE_URL ?>/account/bookings">Xem lịch sử đặt vé</a>
        <a class="btn btn-secondary" href="<?= BASE_URL ?>/">Về trang chủ</a>
    </p>
</div>
