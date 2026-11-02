<div class="ticket-card">
    <h1>🎉 Đặt vé thành công!</h1>
    <div class="alert alert-success">
        Đơn <strong><?= htmlspecialchars($booking['booking_code']) ?></strong> đã được xác nhận.
        Vui lòng đọc mã đơn tại quầy để nhận vé. (Thông báo xác nhận mô phỏng, không gửi email.)
    </div>
    <dl>
        <dt>Mã đơn</dt><dd><strong><?= htmlspecialchars($booking['booking_code']) ?></strong></dd>
        <dt>Phim</dt><dd><?= htmlspecialchars($booking['movie_title']) ?></dd>
        <dt>Rạp</dt><dd><?= htmlspecialchars($booking['cinema_name']) ?> - <?= htmlspecialchars($booking['room_name']) ?></dd>
        <dt>Suất chiếu</dt>
        <dd><?= htmlspecialchars(date('d/m/Y', strtotime($booking['show_date']))) ?> lúc <?= htmlspecialchars(substr($booking['start_time'], 0, 5)) ?></dd>
        <dt>Ghế</dt>
        <dd><?= htmlspecialchars(implode(', ', array_column($seats, 'seat_label'))) ?></dd>
        <dt>Tổng tiền</dt><dd><strong><?= number_format($booking['total_amount'], 0, ',', '.') ?>đ</strong></dd>
        <dt>Trạng thái</dt><dd><?= Booking::statusLabel($booking) ?></dd>
        <dt>Thanh toán</dt><dd><?= Booking::paymentLabel($booking['payment_status']) ?> (mô phỏng)</dd>
    </dl>
    <p style="margin-top:20px;">
        <a class="btn" href="<?= BASE_URL ?>/account/bookings">Xem lịch sử đặt vé</a>
        <a class="btn btn-secondary" href="<?= BASE_URL ?>/">Về trang chủ</a>
    </p>
</div>
<script>
// Đơn đã tạo: xóa ghế đang chọn đã lưu cho suất này
try { sessionStorage.removeItem('seat-selection-<?= (int) $booking['showtime_id'] ?>'); } catch (e) {}
</script>
