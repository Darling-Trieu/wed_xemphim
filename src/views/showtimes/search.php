<?php
/**
 * @var array $showtimes
 * @var array $filters
 * @var ?string $error
 */
$selected = static fn (string $key, int $id): string => $filters[$key] === $id ? 'selected' : '';
?>
<div class="page-head">
    <h1>Tra Cứu Lịch Chiếu</h1>
</div>

<form class="schedule-filter" method="get" action="<?= BASE_URL ?>/showtimes">
    <select name="movie_id" aria-label="Phim">
        <option value="">--Tất cả phim--</option>
        <?php foreach ($movies as $movie): ?>
            <option value="<?= (int) $movie['id'] ?>" <?= $selected('movie_id', (int) $movie['id']) ?>><?= htmlspecialchars($movie['title']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="cinema_id" aria-label="Rạp">
        <option value="">--Tất cả rạp--</option>
        <?php foreach ($cinemas as $cinema): ?>
            <option value="<?= (int) $cinema['id'] ?>" <?= $selected('cinema_id', (int) $cinema['id']) ?>>
                <?= htmlspecialchars($cinema['name']) ?> (<?= htmlspecialchars($cinema['city']) ?>)
            </option>
        <?php endforeach; ?>
    </select>
    <select name="room_id" aria-label="Phòng">
        <option value="">--Tất cả phòng--</option>
        <?php foreach ($rooms as $room): ?>
            <option value="<?= (int) $room['id'] ?>" <?= $selected('room_id', (int) $room['id']) ?>><?= htmlspecialchars($room['cinema_name'] . ' - ' . $room['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="date" name="date" aria-label="Ngày chiếu" min="<?= $minDate ?>" max="<?= $maxDate ?>" value="<?= htmlspecialchars($filters['date'] ?? '') ?>">
    <button type="submit" class="schedule-filter__submit">Tìm</button>
</form>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="showtime-list">
    <?php if (!$error && empty($showtimes)): ?>
        <p class="schedule-empty">Chưa có lịch chiếu cho lựa chọn này</p>
    <?php endif; ?>
    <?php foreach ($showtimes as $st): ?>
        <?php $closed = Showtime::isSaleClosed($st); ?>
        <div class="showtime-item<?= $closed ? ' is-closed' : '' ?>">
            <div>
                <strong><?= htmlspecialchars($st['movie_title']) ?></strong>
                <div class="meta">
                    <?= htmlspecialchars($st['cinema_name']) ?> &middot; <?= htmlspecialchars($st['room_name']) ?>
                    &middot; <?= htmlspecialchars(date('d/m/Y', strtotime($st['show_date']))) ?>
                    &middot; <?= htmlspecialchars(substr($st['start_time'], 0, 5)) ?>
                    &middot; Còn <?= max(0, (int) $st['seats_available']) ?> ghế trống
                </div>
            </div>
            <div>
                <span class="price"><?= money($st['base_price']) ?></span>
                <?php if ($closed): ?>
                    <span class="btn btn-small btn-secondary" aria-disabled="true">Đã đóng bán</span>
                <?php else: ?>
                    <a class="btn btn-small" href="<?= BASE_URL ?>/showtimes/<?= (int) $st['id'] ?>/seats">Xem ghế &amp; đặt vé</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
