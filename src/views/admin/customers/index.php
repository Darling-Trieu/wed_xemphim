<h1>Quản lý tài khoản</h1>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form class="filter-bar" method="get" action="<?= BASE_URL ?>/admin/customers" style="margin-bottom:16px;">
    <input class="form-control" type="search" name="q" maxlength="100" placeholder="Tìm theo email hoặc số điện thoại"
           value="<?= htmlspecialchars($keyword) ?>" style="max-width:320px;">
    <button type="submit" class="btn btn-small">Tìm</button>
    <?php if ($keyword !== ''): ?>
        <a class="btn btn-small btn-secondary" href="<?= BASE_URL ?>/admin/customers">Bỏ lọc</a>
    <?php endif; ?>
</form>

<div class="table-responsive">
<table class="data-table">
    <thead><tr><th>Họ tên</th><th>Email</th><th>SĐT</th><th>Vai trò</th><th class="num">Số đơn</th><th>Ngày đăng ký</th><th>Trạng thái</th><th></th></tr></thead>
    <tbody>
        <?php foreach ($customers as $c): ?>
            <?php $isSelf = (int) $c['id'] === Auth::id(); ?>
            <tr>
                <td><?= htmlspecialchars($c['full_name']) ?></td>
                <td><?= htmlspecialchars($c['email']) ?></td>
                <td><?= htmlspecialchars($c['phone'] ?? '') ?></td>
                <td><?= $c['role'] === 'admin' ? 'Quản trị viên' : 'Người dùng' ?></td>
                <td class="num"><?= (int) $c['booking_count'] ?></td>
                <td><?= htmlspecialchars(date('d/m/Y', strtotime($c['created_at']))) ?></td>
                <td><?= $c['status'] === 'active' ? 'Hoạt động' : 'Bị khóa' ?></td>
                <td>
                    <?php if ($isSelf): ?>
                        <span class="movie-meta">Đang đăng nhập</span>
                    <?php else: ?>
                        <form method="post" action="<?= BASE_URL ?>/admin/customers/<?= (int) $c['id'] ?>/toggle"
                              onsubmit="return confirm('<?= $c['status'] === 'active' ? 'Khóa' : 'Mở khóa' ?> tài khoản này?');">
                            <?= Csrf::field() ?>
                            <button type="submit" class="btn btn-small <?= $c['status'] === 'active' ? 'btn-danger' : '' ?>">
                                <?= $c['status'] === 'active' ? 'Khóa tài khoản' : 'Mở khóa' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($customers)): ?>
            <tr><td colspan="8">Không tìm thấy tài khoản nào.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>
