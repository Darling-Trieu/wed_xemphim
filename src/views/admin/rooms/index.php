<div class="admin-toolbar">
    <h1 style="margin:0;">Quản lý phòng chiếu</h1>
    <a class="btn" href="<?= BASE_URL ?>/admin/rooms/create">+ Thêm phòng chiếu</a>
</div>

<div class="table-responsive">
<table class="data-table">
    <thead><tr><th>Rạp</th><th>Tên phòng</th><th>Trạng thái</th><th></th></tr></thead>
    <tbody>
        <?php foreach ($rooms as $room): ?>
            <tr>
                <td><?= htmlspecialchars($room['cinema_name']) ?></td>
                <td><?= htmlspecialchars($room['name']) ?></td>
                <td><?= $room['status'] === 'active' ? 'Hoạt động' : 'Ngừng hoạt động' ?></td>
                <td class="actions-cell">
                    <a class="btn btn-small btn-secondary" href="<?= BASE_URL ?>/admin/rooms/<?= (int) $room['id'] ?>/seats">Sơ đồ ghế</a>
                    <form method="post" action="<?= BASE_URL ?>/admin/rooms/<?= (int) $room['id'] ?>/delete" onsubmit="return confirm('Xóa phòng chiếu này? Toàn bộ ghế sẽ bị xóa.');">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn-small btn-danger">Xóa</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($rooms)): ?>
            <tr><td colspan="4">Chưa có phòng chiếu nào.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>
