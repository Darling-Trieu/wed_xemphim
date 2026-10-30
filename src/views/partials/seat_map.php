<?php
/**
 * @var array $showtime
 * @var array $seatTypes
 * @var bool $saleClosed
 */
$legendStyle = ['VIP' => 'border-color:#f5b301;', 'Đôi' => 'border-color:#a855f7;'];
?>
<div class="seat-map-wrap">
    <h2 style="margin-top:0;"><?= htmlspecialchars($showtime['movie_title']) ?>
        <span class="badge"><?= htmlspecialchars($showtime['age_rating']) ?></span></h2>
    <p class="movie-meta">
        <?= htmlspecialchars($showtime['cinema_name']) ?> &middot; <?= htmlspecialchars($showtime['room_name']) ?>
        &middot; <?= htmlspecialchars(date('d/m/Y', strtotime($showtime['show_date']))) ?>
        &middot; <?= htmlspecialchars(substr($showtime['start_time'], 0, 5)) ?>
    </p>

    <?php if ($saleClosed): ?>
        <div class="alert alert-error">
            <?= $showtime['status'] === 'cancelled' ? 'Suất chiếu đã bị hủy.' : 'Suất chiếu đã đóng bán.' ?>
        </div>
    <?php endif; ?>

    <div class="screen-bar">MÀN HÌNH</div>

    <div class="seat-legend">
        <?php foreach ($seatTypes as $type): ?>
            <span><span class="legend-box" style="<?= $legendStyle[$type['name']] ?? '' ?>"></span>
                <?= htmlspecialchars($type['name'] === 'Đôi' ? 'Ghế đôi (2 người)' : $type['name']) ?>:
                <?= number_format((int) $showtime['base_price'] + (int) $type['surcharge'], 0, ',', '.') ?>đ</span>
        <?php endforeach; ?>
        <span><span class="legend-box" style="background:#2a2a2e;"></span> Đã bán</span>
        <span><span class="legend-box inactive"></span> Ngừng sử dụng</span>
        <span><span class="legend-box" style="background:#e50914;border-color:#e50914;"></span> Đang chọn</span>
    </div>

    <div class="seat-scroll">
        <div id="seat-map"
             data-showtime-id="<?= (int) $showtime['id'] ?>"
             data-interactive="<?= $saleClosed ? '0' : '1' ?>"
             data-max-seats="<?= MAX_SEATS_PER_BOOKING ?>"
             data-api-url="<?= BASE_URL ?>/api.php">
            <p>Đang tải sơ đồ ghế...</p>
        </div>
    </div>

    <?php if (!$saleClosed): ?>
        <div class="seat-message" id="seat-message" role="alert"></div>
        <?php if (Auth::check()): ?>
            <form id="booking-form" method="post" action="<?= BASE_URL ?>/booking/<?= (int) $showtime['id'] ?>/confirm">
                <?= Csrf::field() ?>
                <input type="hidden" name="seat_ids" id="seat-ids-input" value="">
        <?php else: ?>
            <?php // Khách chưa đăng nhập: ghế đang chọn được lưu ở sessionStorage, đăng nhập xong quay lại đúng suất này ?>
            <form id="booking-form" method="get" action="<?= BASE_URL ?>/booking/<?= (int) $showtime['id'] ?>">
        <?php endif; ?>
            <div class="booking-summary">
                <div>
                    <div id="selected-seats-label" class="movie-meta">Chưa chọn ghế nào</div>
                    <div class="total">Tạm tính: <span id="total-price">0đ</span></div>
                </div>
                <button type="submit" class="btn" id="submit-booking" disabled>
                    <?= Auth::check() ? 'Đặt vé' : 'Đăng nhập để đặt vé' ?>
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>
