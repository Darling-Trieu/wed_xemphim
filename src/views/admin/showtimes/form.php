<h1><?= $showtime ? 'Sửa suất chiếu' : 'Thêm suất chiếu' ?></h1>

<form class="admin-card" method="post"
      action="<?= BASE_URL ?>/admin/showtimes<?= $showtime ? '/' . (int) $showtime['id'] : '' ?>">
    <?= Csrf::field() ?>
    <div class="form-group">
        <label>Phim</label>
        <select class="form-control" name="movie_id" required>
            <?php foreach ($movies as $movie): ?>
                <option value="<?= (int) $movie['id'] ?>" <?= (int) ($showtime['movie_id'] ?? 0) === (int) $movie['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($movie['title']) ?> (<?= (int) $movie['duration_minutes'] ?> phút)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label>Phòng chiếu</label>
        <select class="form-control" name="room_id" required>
            <?php foreach ($rooms as $room): ?>
                <option value="<?= (int) $room['id'] ?>" <?= (int) ($showtime['room_id'] ?? 0) === (int) $room['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($room['cinema_name']) ?> - <?= htmlspecialchars($room['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label>Ngày chiếu</label>
        <input class="form-control" type="date" name="show_date" required
               value="<?= htmlspecialchars($showtime['show_date'] ?? '') ?>">
    </div>
    <div class="form-group">
        <label>Giờ bắt đầu</label>
        <input class="form-control" type="time" name="start_time" required
               value="<?= htmlspecialchars($showtime ? substr($showtime['start_time'], 0, 5) : '') ?>">
    </div>
    <div class="form-group">
        <label>Giá vé cơ bản (ghế thường)</label>
        <input class="form-control" type="number" min="1000" step="1000" name="base_price" required
               value="<?= htmlspecialchars((string) ($showtime['base_price'] ?? '')) ?>">
    </div>
    <button type="submit" class="btn">Lưu</button>
    <a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/showtimes">Hủy</a>
</form>
