<h1>Thêm phòng chiếu</h1>

<form class="admin-card" method="post" action="<?= BASE_URL ?>/admin/rooms">
    <?= Csrf::field() ?>
    <div class="form-group">
        <label>Rạp chiếu</label>
        <select class="form-control" name="cinema_id" required>
            <?php foreach ($cinemas as $cinema): ?>
                <option value="<?= (int) $cinema['id'] ?>"><?= htmlspecialchars($cinema['name']) ?> (<?= htmlspecialchars($cinema['city']) ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label>Tên phòng</label>
        <input class="form-control" type="text" name="name" required placeholder="VD: Phòng 3">
    </div>
    <div class="form-group">
        <label>Số hàng ghế (A, B, C...)</label>
        <input class="form-control" type="number" name="rows" min="1" max="26" value="5" required>
    </div>
    <div class="form-group">
        <label>Số ghế mỗi hàng</label>
        <input class="form-control" type="number" name="cols" min="1" max="30" value="8" required>
    </div>
    <div class="form-group">
        <label>Loại ghế mặc định</label>
        <select class="form-control" name="seat_type_id">
            <?php foreach ($seatTypes as $type): ?>
                <option value="<?= (int) $type['id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <p class="movie-meta">Sau khi tạo, bạn có thể chỉnh loại ghế cho từng ghế riêng lẻ ở bước tiếp theo.</p>
    </div>
    <button type="submit" class="btn">Tạo phòng &amp; sơ đồ ghế</button>
    <a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/rooms">Hủy</a>
</form>
