<div class="admin-toolbar">
    <h1 style="margin:0;">Quản lý suất chiếu</h1>
    <a class="btn" href="<?= BASE_URL ?>/admin/showtimes/create">+ Thêm suất chiếu</a>
</div>

<table class="data-table">
    <thead>
        <tr><th>Phim</th><th>Rạp / Phòng</th><th>Ngày</th><th>Giờ chiếu</th><th>Giá vé</th><th>Trạng thái</th><th></th></tr>
    </thead>
    <tbody>
        <?php foreach ($showtimes as $st): ?>
            <tr>
                <td><?= htmlspecialchars($st['movie_title']) ?></td>
                <td><?= htmlspecialchars($st['cinema_name']) ?> - <?= htmlspecialchars($st['room_name']) ?></td>
                <td><?= htmlspecialchars(date('d/m/Y', strtotime($st['show_date']))) ?></td>
                <td><?= htmlspecialchars(substr($st['start_time'], 0, 5)) ?> - <?= htmlspecialchars(substr($st['end_time'], 0, 5)) ?></td>
                <td><?= number_format($st['base_price'], 0, ',', '.') ?>đ</td>
                <td><?= $st['status'] === 'active' ? 'Hoạt động' : 'Đã hủy' ?></td>
                <td class="actions-cell">
                    <a class="btn btn-small btn-secondary" href="<?= BASE_URL ?>/admin/showtimes/<?= (int) $st['id'] ?>/edit">Sửa</a>
                    <form method="post" action="<?= BASE_URL ?>/admin/showtimes/<?= (int) $st['id'] ?>/delete" onsubmit="return confirm('Xóa suất chiếu này?');">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn-small btn-danger">Xóa</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($showtimes)): ?>
            <tr><td colspan="7">Chưa có suất chiếu nào.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
