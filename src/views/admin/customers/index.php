<h1>Quản lý khách hàng</h1>

<table class="data-table">
    <thead><tr><th>Họ tên</th><th>Email</th><th>SĐT</th><th>Ngày đăng ký</th><th>Trạng thái</th><th></th></tr></thead>
    <tbody>
        <?php foreach ($customers as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['full_name']) ?></td>
                <td><?= htmlspecialchars($c['email']) ?></td>
                <td><?= htmlspecialchars($c['phone'] ?? '') ?></td>
                <td><?= htmlspecialchars(date('d/m/Y', strtotime($c['created_at']))) ?></td>
                <td><?= $c['status'] === 'active' ? 'Hoạt động' : 'Đã khóa' ?></td>
                <td>
                    <form method="post" action="<?= BASE_URL ?>/admin/customers/<?= (int) $c['id'] ?>/toggle"
                          onsubmit="return confirm('<?= $c['status'] === 'active' ? 'Khóa' : 'Mở khóa' ?> tài khoản này?');">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn-small <?= $c['status'] === 'active' ? 'btn-danger' : '' ?>">
                            <?= $c['status'] === 'active' ? 'Khóa tài khoản' : 'Mở khóa' ?>
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($customers)): ?>
            <tr><td colspan="6">Chưa có khách hàng nào.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
