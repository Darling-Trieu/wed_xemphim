<h1 class="section-title">Tra cứu lịch chiếu</h1>

<form class="filter-bar" method="get" action="<?= BASE_URL ?>/showtimes">
    <select name="movie_id">
        <option value="">-- Tất cả phim --</option>
        <?php foreach ($movies as $movie): ?>
            <option value="<?= (int) $movie['id'] ?>" <?= $movieId === (int) $movie['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($movie['title']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <select name="cinema_id">
        <option value="">-- Tất cả rạp --</option>
        <?php foreach ($cinemas as $cinema): ?>
            <option value="<?= (int) $cinema['id'] ?>" <?= $cinemaId === (int) $cinema['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cinema['name']) ?> (<?= htmlspecialchars($cinema['city']) ?>)
            </option>
        <?php endforeach; ?>
    </select>
    <input type="date" name="date" value="<?= htmlspecialchars($date ?? '') ?>">
    <button type="submit" class="btn">Tìm suất chiếu</button>
</form>

<div class="showtime-list">
    <?php if (empty($showtimes)): ?>
        <p>Không tìm thấy suất chiếu phù hợp.</p>
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
                <span style="margin-right:12px;color:#e50914;font-weight:600;">
                    <?= number_format($st['base_price'], 0, ',', '.') ?>đ
                </span>
                <a class="btn btn-small" href="<?= BASE_URL ?>/showtimes/<?= (int) $st['id'] ?>/seats">Xem ghế &amp; đặt vé</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>
