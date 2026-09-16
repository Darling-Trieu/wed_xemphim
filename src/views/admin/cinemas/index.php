<div class="admin-toolbar">
    <h1 style="margin:0;">Quản lý rạp chiếu</h1>
    <a class="btn" href="<?= BASE_URL ?>/admin/cinemas/create">+ Thêm rạp</a>
</div>

<table class="data-table">
    <thead>
        <tr><th>Tên rạp</th><th>Địa chỉ</th><th>Thành phố</th><th>SĐT</th><th>Trạng thái</th><th></th></tr>
    </thead>
    <tbody>
        <?php foreach ($cinemas as $cinema): ?>
            <tr>
                <td><?= htmlspecialchars($cinema['name']) ?></td>
                <td><?= htmlspecialchars($cinema['address']) ?></td>
                <td><?= htmlspecialchars($cinema['city']) ?></td>
                <td><?= htmlspecialchars($cinema['phone'] ?? '') ?></td>
                <td><?= $cinema['status'] === 'active' ? 'Hoạt động' : 'Ngừng hoạt động' ?></td>
                <td class="actions-cell">
                    <a class="btn btn-small btn-secondary" href="<?= BASE_URL ?>/admin/cinemas/<?= (int) $cinema['id'] ?>/edit">Sửa</a>
                    <form method="post" action="<?= BASE_URL ?>/admin/cinemas/<?= (int) $cinema['id'] ?>/delete" onsubmit="return confirm('Xóa rạp này?');">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn-small btn-danger">Xóa</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($cinemas)): ?>
            <tr><td colspan="6">Chưa có rạp nào.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
