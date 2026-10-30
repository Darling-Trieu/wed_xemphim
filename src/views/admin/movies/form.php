<h1><?= $movie ? 'Sửa phim' : 'Thêm phim' ?></h1>

<form class="admin-card" method="post" enctype="multipart/form-data"
      action="<?= BASE_URL ?>/admin/movies<?= $movie ? '/' . (int) $movie['id'] : '' ?>">
    <?= Csrf::field() ?>

    <div class="form-group">
        <label>Tên phim</label>
        <input class="form-control" type="text" name="title" required value="<?= htmlspecialchars($movie['title'] ?? '') ?>">
    </div>
    <div class="form-group">
        <label>Mô tả</label>
        <textarea class="form-control" name="description"><?= htmlspecialchars($movie['description'] ?? '') ?></textarea>
    </div>
    <div class="form-group">
        <label>Thời lượng (phút)</label>
        <input class="form-control" type="number" min="1" name="duration_minutes" required value="<?= htmlspecialchars((string) ($movie['duration_minutes'] ?? '')) ?>">
    </div>
    <div class="form-group">
        <label>Độ tuổi</label>
        <select class="form-control" name="age_rating">
            <?php foreach (['P', 'K', 'T13', 'T16', 'T18'] as $rating): ?>
                <option value="<?= $rating ?>" <?= ($movie['age_rating'] ?? 'P') === $rating ? 'selected' : '' ?>><?= $rating ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label>Trạng thái</label>
        <select class="form-control" name="status">
            <option value="coming_soon" <?= ($movie['status'] ?? '') === 'coming_soon' ? 'selected' : '' ?>>Sắp chiếu</option>
            <option value="now_showing" <?= ($movie['status'] ?? '') === 'now_showing' ? 'selected' : '' ?>>Đang chiếu</option>
            <option value="ended" <?= ($movie['status'] ?? '') === 'ended' ? 'selected' : '' ?>>Ngừng chiếu</option>
        </select>
    </div>
    <div class="form-group">
        <label>Ngày khởi chiếu</label>
        <input class="form-control" type="date" name="release_date" value="<?= htmlspecialchars($movie['release_date'] ?? '') ?>">
    </div>
    <div class="form-group">
        <label>Link trailer (YouTube...)</label>
        <input class="form-control" type="text" name="trailer_url" value="<?= htmlspecialchars($movie['trailer_url'] ?? '') ?>">
    </div>
    <div class="form-group">
        <label>Thể loại</label>
        <?php foreach ($genres as $genre): ?>
            <label style="font-weight:400;display:inline-flex;align-items:center;gap:4px;margin-right:12px;">
                <input type="checkbox" name="genre_ids[]" value="<?= (int) $genre['id'] ?>"
                    <?= in_array($genre['id'], $selectedGenres) ? 'checked' : '' ?>>
                <?= htmlspecialchars($genre['name']) ?>
            </label>
        <?php endforeach; ?>
    </div>
    <div class="form-group">
        <label>Poster<?= $movie ? ' (để trống nếu không đổi)' : '' ?></label>
        <input class="form-control" type="file" name="poster" accept="image/png,image/jpeg,image/webp">
        <?php if (!empty($movie['poster_path'])): ?>
            <img src="<?= htmlspecialchars(UPLOAD_URL . '/' . $movie['poster_path']) ?>" style="max-width:120px;margin-top:8px;border-radius:6px;">
        <?php endif; ?>
    </div>

    <button type="submit" class="btn">Lưu</button>
    <a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/movies">Hủy</a>
</form>
