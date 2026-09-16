<?php
/**
 * @var array $showtime
 * @var bool $interactive
 */
?>
<div class="seat-map-wrap">
    <h2 style="margin-top:0;"><?= htmlspecialchars($showtime['movie_title']) ?></h2>
    <p class="movie-meta">
        <?= htmlspecialchars($showtime['cinema_name']) ?> &middot; <?= htmlspecialchars($showtime['room_name']) ?>
        &middot; <?= htmlspecialchars(date('d/m/Y', strtotime($showtime['show_date']))) ?>
        &middot; <?= htmlspecialchars(substr($showtime['start_time'], 0, 5)) ?>
    </p>

    <div class="screen-bar">MÀN HÌNH</div>

    <div class="seat-legend">
        <span><span class="legend-box" style="background:#fff;"></span> Thường</span>
        <span><span class="legend-box" style="border-color:#f59e0b;"></span> VIP</span>
        <span><span class="legend-box" style="border-color:#a855f7;"></span> Ghế đôi</span>
        <span><span class="legend-box" style="background:#d1d5db;"></span> Đã đặt</span>
        <span><span class="legend-box" style="background:#e50914;border-color:#e50914;"></span> Đang chọn</span>
    </div>

    <div id="seat-map"
         data-showtime-id="<?= (int) $showtime['id'] ?>"
         data-interactive="<?= $interactive ? '1' : '0' ?>"
         data-api-url="<?= BASE_URL ?>/api.php">
        <p>Đang tải sơ đồ ghế...</p>
    </div>

    <?php if ($interactive): ?>
        <form id="booking-form" method="post" action="<?= BASE_URL ?>/booking/<?= (int) $showtime['id'] ?>">
            <?= Csrf::field() ?>
            <input type="hidden" name="seat_ids" id="seat-ids-input" value="">
            <div class="booking-summary">
                <div>
                    <div id="selected-seats-label" class="movie-meta">Chưa chọn ghế nào</div>
                    <div class="total">Tổng tiền: <span id="total-price">0đ</span></div>
                </div>
                <button type="submit" class="btn" id="submit-booking" disabled>Xác nhận đặt vé</button>
            </div>
        </form>
    <?php else: ?>
        <div class="booking-summary">
            <div class="movie-meta">Đăng nhập để chọn ghế và đặt vé.</div>
            <a class="btn" href="<?= BASE_URL ?>/booking/<?= (int) $showtime['id'] ?>">Đặt vé ngay</a>
        </div>
    <?php endif; ?>
</div>
