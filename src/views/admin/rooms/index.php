<div class="admin-toolbar">
    <h1 style="margin:0;">Quản lý phòng chiếu</h1>
    <a class="btn" href="<?= BASE_URL ?>/admin/rooms/create">+ Thêm phòng chiếu</a>
</div>

<div class="table-responsive">
<table class="data-table">
    <thead><tr><th>Rạp</th><th>Tên phòng</th><th>Loại phòng</th><th>Trạng thái</th><th></th></tr></thead>
    <tbody>
        <?php foreach ($rooms as $room): ?>
            <tr>
                <td><?= htmlspecialchars($room['cinema_name']) ?></td>
                <td><?= htmlspecialchars($room['name']) ?></td>
                <td><?= htmlspecialchars(Room::TYPES[$room['room_type']] ?? $room['room_type']) ?></td>
                <td><?= htmlspecialchars(Room::STATUSES[$room['status']] ?? $room['status']) ?></td>
                <td class="actions-cell">
                    <a class="btn btn-small btn-secondary" href="<?= BASE_URL ?>/admin/rooms/<?= (int) $room['id'] ?>/edit">Sửa</a>
                    <a class="btn btn-small btn-secondary" href="<?= BASE_URL ?>/admin/rooms/<?= (int) $room['id'] ?>/seats">Sơ đồ ghế</a>
                    <form method="post" action="<?= BASE_URL ?>/admin/rooms/<?= (int) $room['id'] ?>/delete" onsubmit="return confirm('Xóa phòng chiếu này? Phòng đã có suất chiếu sẽ chỉ chuyển sang Ngừng hoạt động.');">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn-small btn-danger">Xóa</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($rooms)): ?>
            <tr><td colspan="5">Chưa có phòng chiếu nào.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>
