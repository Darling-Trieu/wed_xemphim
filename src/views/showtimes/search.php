<div class="page-head">
    <h1>Tra Cứu Lịch Chiếu</h1>
</div>

<form class="schedule-filter" method="get" action="<?= BASE_URL ?>/showtimes">
    <select name="movie_id" aria-label="Phim">
        <option value="">--Tất cả phim--</option>
        <?php foreach ($movies as $movie): ?>
            <option value="<?= (int) $movie['id'] ?>" <?= $movieId === (int) $movie['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($movie['title']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <select name="cinema_id" aria-label="Rạp">
        <option value="">--Tất cả rạp--</option>
        <?php foreach ($cinemas as $cinema): ?>
            <option value="<?= (int) $cinema['id'] ?>" <?= $cinemaId === (int) $cinema['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cinema['name']) ?> (<?= htmlspecialchars($cinema['city']) ?>)
            </option>
        <?php endforeach; ?>
    </select>
    <input type="date" name="date" aria-label="Ngày chiếu" value="<?= htmlspecialchars($date ?? '') ?>">
    <button type="submit" class="schedule-filter__submit">Tìm</button>
</form>

<div class="showtime-list">
    <?php if (empty($showtimes)): ?>
        <p class="schedule-empty">Không tìm thấy suất chiếu phù hợp.</p>
    <?php endif; ?>
    <?php foreach ($showtimes as $st): ?>
        <div class="showtime-item">
            <div>
                <strong><?= htmlspecialchars($st['movie_title']) ?></strong>
                <div class="meta">
                    <?= htmlspecialchars($st['cinema_name']) ?> &middot; <?= htmlspecialchars($st['room_name']) ?>
                    &middot; <?= htmlspecialchars(date('d/m/Y', strtotime($st['show_date']))) ?>
                    &middot; <?= htmlspecialchars(substr($st['start_time'], 0, 5)) ?>
                </div>
            </div>
            <div>
                <span class="price"><?= number_format($st['base_price'], 0, ',', '.') ?>đ</span>
                <a class="btn btn-small" href="<?= BASE_URL ?>/showtimes/<?= (int) $st['id'] ?>/seats">Xem ghế &amp; đặt vé</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>
