<?php
/**
 * @var ?array $showtime  suất đang sửa (null khi thêm mới)
 * @var array $old        dữ liệu vừa nhập (giữ lại khi có lỗi)
 * @var ?string $error
 * @var int $soldCount
 */
$value = static function (string $key) use ($old, $showtime): string {
    if (array_key_exists($key, $old)) {
        return (string) $old[$key];
    }
    if ($key === 'start_time' && $showtime) {
        return substr($showtime['start_time'], 0, 5);
    }
    return (string) ($showtime[$key] ?? '');
};
?>
<h1><?= $showtime ? 'Sửa suất chiếu' : 'Thêm suất chiếu' ?></h1>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($soldCount > 0): ?>
    <div class="alert alert-error">Suất này đã bán <?= (int) $soldCount ?> ghế: chỉ sửa được giá cơ bản (vé đã bán giữ nguyên giá cũ).</div>
<?php endif; ?>

<form class="admin-card" method="post"
      action="<?= BASE_URL ?>/admin/showtimes<?= $showtime ? '/' . (int) $showtime['id'] : '' ?>">
    <?= Csrf::field() ?>
    <div class="form-group">
        <label>Phim (Đang chiếu / Sắp chiếu)</label>
        <select class="form-control" name="movie_id" required>
            <option value="">-- Chọn phim --</option>
            <?php foreach ($movies as $movie): ?>
                <option value="<?= (int) $movie['id'] ?>" <?= $value('movie_id') === (string) $movie['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($movie['title']) ?> (<?= (int) $movie['duration_minutes'] ?> phút)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label>Phòng chiếu (đang hoạt động)</label>
        <select class="form-control" name="room_id" required>
            <option value="">-- Chọn phòng --</option>
            <?php foreach ($rooms as $room): ?>
                <option value="<?= (int) $room['id'] ?>" <?= $value('room_id') === (string) $room['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($room['cinema_name']) ?> - <?= htmlspecialchars($room['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label>Ngày chiếu</label>
        <input class="form-control" type="date" name="show_date" required value="<?= htmlspecialchars($value('show_date')) ?>">
    </div>
    <div class="form-group">
        <label>Giờ bắt đầu (24h)</label>
        <input class="form-control" type="time" name="start_time" required value="<?= htmlspecialchars($value('start_time')) ?>">
        <p class="movie-meta">Giờ kết thúc chiếm phòng = giờ bắt đầu + thời lượng phim + <?= CLEANUP_MINUTES ?> phút dọn phòng (tự tính).</p>
    </div>
    <div class="form-group">
        <label>Giá vé cơ bản (ghế thường, đồng)</label>
        <input class="form-control" type="number" min="1" step="1" name="base_price" required value="<?= htmlspecialchars($value('base_price')) ?>">
    </div>
    <button type="submit" class="btn">Lưu</button>
    <a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/showtimes">Hủy</a>
</form>
