<?php
/**
 * @var array $showtime
 * @var array $tickets
 * @var ?string $error
 */
$total = array_sum(array_column($tickets, 'price'));
$needAge = Booking::requiresAgeConfirm($showtime['age_rating']);
?>
<div class="ticket-card">
    <h1>Xác nhận đặt vé</h1>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <dl>
        <dt>Phim</dt>
        <dd><?= htmlspecialchars($showtime['movie_title']) ?> <span class="badge"><?= htmlspecialchars($showtime['age_rating']) ?></span></dd>
        <dt>Suất chiếu</dt>
        <dd><?= htmlspecialchars(date('d/m/Y', strtotime($showtime['show_date']))) ?> lúc <?= htmlspecialchars(substr($showtime['start_time'], 0, 5)) ?></dd>
        <dt>Rạp / Phòng</dt>
        <dd><?= htmlspecialchars($showtime['cinema_name']) ?> - <?= htmlspecialchars($showtime['room_name']) ?></dd>
    </dl>

    <div class="table-responsive" style="margin:16px 0;">
        <table class="data-table price-table">
            <thead><tr><th>Ghế</th><th>Loại ghế</th><th class="num">Giá</th></tr></thead>
            <tbody>
                <?php foreach ($tickets as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['seat_label']) ?></td>
                        <td><?= htmlspecialchars($t['seat_type_name']) ?></td>
                        <td class="num"><?= number_format($t['price'], 0, ',', '.') ?>đ</td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="2"><strong>Tổng cộng (<?= count($tickets) ?> ghế)</strong></td>
                    <td class="num"><strong><?= number_format($total, 0, ',', '.') ?>đ</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <form id="confirm-form" method="post" action="<?= BASE_URL ?>/booking/<?= (int) $showtime['id'] ?>">
        <?= Csrf::field() ?>
        <input type="hidden" name="seat_ids" value="<?= htmlspecialchars(implode(',', array_column($tickets, 'seat_id'))) ?>">

        <?php if ($needAge): ?>
            <label class="age-confirm">
                <input type="checkbox" name="age_confirmed" value="1" id="age-confirmed">
                <span>Tôi xác nhận tất cả người xem đủ <?= (int) substr($showtime['age_rating'], 1) ?> tuổi trở lên
                    theo quy định phim <?= htmlspecialchars($showtime['age_rating']) ?> và sẽ xuất trình giấy tờ khi được yêu cầu.</span>
            </label>
            <div class="seat-message" id="age-message" role="alert"></div>
        <?php endif; ?>

        <p class="movie-meta">Thanh toán mô phỏng: đơn được xác nhận ngay khi bấm "Xác nhận".</p>
        <button type="submit" class="btn">Xác nhận</button>
        <a class="btn btn-secondary" href="<?= BASE_URL ?>/showtimes/<?= (int) $showtime['id'] ?>/seats">&larr; Quay lại</a>
    </form>
</div>
