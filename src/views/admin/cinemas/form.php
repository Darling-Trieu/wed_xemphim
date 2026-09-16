<h1><?= $cinema ? 'Sửa rạp chiếu' : 'Thêm rạp chiếu' ?></h1>

<form class="admin-card" method="post"
      action="<?= BASE_URL ?>/admin/cinemas<?= $cinema ? '/' . (int) $cinema['id'] : '' ?>">
    <?= Csrf::field() ?>
    <div class="form-group">
        <label>Tên rạp</label>
        <input class="form-control" type="text" name="name" required value="<?= htmlspecialchars($cinema['name'] ?? '') ?>">
    </div>
    <div class="form-group">
        <label>Địa chỉ</label>
        <input class="form-control" type="text" name="address" required value="<?= htmlspecialchars($cinema['address'] ?? '') ?>">
    </div>
    <div class="form-group">
        <label>Thành phố</label>
        <input class="form-control" type="text" name="city" required value="<?= htmlspecialchars($cinema['city'] ?? '') ?>">
    </div>
    <div class="form-group">
        <label>Số điện thoại</label>
        <input class="form-control" type="text" name="phone" value="<?= htmlspecialchars($cinema['phone'] ?? '') ?>">
    </div>
    <div class="form-group">
        <label>Trạng thái</label>
        <select class="form-control" name="status">
            <option value="active" <?= ($cinema['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Hoạt động</option>
            <option value="inactive" <?= ($cinema['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Ngừng hoạt động</option>
        </select>
    </div>
    <button type="submit" class="btn">Lưu</button>
    <a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/cinemas">Hủy</a>
</form>
