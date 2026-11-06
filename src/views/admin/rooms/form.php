<?php
/**
 * Form dùng chung cho Thêm / Sửa phòng. Khi sửa chỉ đổi tên, loại, trạng thái;
 * lưới ghế (hàng, cột, loại ghế, lối đi) sửa ở trang "Sơ đồ ghế".
 *
 * @var ?array $room
 * @var array $cinemas
 * @var array $seatTypes
 */
$isEdit = $room !== null;
?>
<h1><?= $isEdit ? 'Sửa phòng chiếu' : 'Thêm phòng chiếu' ?></h1>

<form class="admin-card" method="post" action="<?= BASE_URL ?>/admin/rooms<?= $isEdit ? '/' . (int) $room['id'] : '' ?>">
    <?= Csrf::field() ?>
    <div class="form-group">
        <label>Rạp chiếu</label>
        <?php if ($isEdit): ?>
            <input class="form-control" type="text" value="<?= htmlspecialchars($room['cinema_name']) ?>" disabled>
        <?php else: ?>
            <select class="form-control" name="cinema_id" required>
                <?php foreach ($cinemas as $cinema): ?>
                    <option value="<?= (int) $cinema['id'] ?>"><?= htmlspecialchars($cinema['name']) ?> (<?= htmlspecialchars($cinema['city']) ?>)</option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
    </div>
    <div class="form-group">
        <label>Tên phòng (duy nhất trong rạp)</label>
        <input class="form-control" type="text" name="name" maxlength="50" required placeholder="VD: Phòng 3"
               value="<?= htmlspecialchars($room['name'] ?? '') ?>">
    </div>
    <div class="form-group">
        <label>Loại phòng</label>
        <select class="form-control" name="room_type">
            <?php foreach (Room::TYPES as $value => $label): ?>
                <option value="<?= $value ?>" <?= ($room['room_type'] ?? 'standard') === $value ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if ($isEdit): ?>
        <div class="form-group">
            <label>Trạng thái</label>
            <select class="form-control" name="status">
                <?php foreach (Room::STATUSES as $value => $label): ?>
                    <option value="<?= $value ?>" <?= $room['status'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
            <p class="movie-meta">Phòng Ngừng hoạt động không xếp suất mới được; các suất đã có vẫn giữ nguyên.</p>
        </div>
    <?php else: ?>
        <div class="form-group">
            <label>Số hàng ghế (A, B, C...)</label>
            <input class="form-control" type="number" name="rows" min="1" max="20" value="5" required>
        </div>
        <div class="form-group">
            <label>Số ghế mỗi hàng</label>
            <input class="form-control" type="number" name="cols" min="1" max="20" value="8" required>
        </div>
        <div class="form-group">
            <label>Loại ghế mặc định</label>
            <select class="form-control" name="seat_type_id">
                <?php foreach ($seatTypes as $type): ?>
                    <option value="<?= (int) $type['id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="movie-meta">Sau khi tạo, đánh dấu lối đi, ghế ngừng sử dụng và loại ghế theo hàng / từng ghế ở bước tiếp theo.</p>
        </div>
    <?php endif; ?>

    <button type="submit" class="btn"><?= $isEdit ? 'Lưu' : 'Tạo phòng &amp; sơ đồ ghế' ?></button>
    <a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/rooms">Hủy</a>
</form>
