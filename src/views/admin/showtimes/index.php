<div class="admin-toolbar">
    <h1 style="margin:0;">Quản lý suất chiếu</h1>
    <a class="btn" href="<?= BASE_URL ?>/admin/showtimes/create">+ Thêm suất chiếu</a>
</div>

<div class="table-responsive">
<table class="data-table">
    <thead>
        <tr><th>Phim</th><th>Rạp / Phòng</th><th>Ngày</th><th>Giờ chiếu (chiếm phòng)</th><th class="num">Giá vé</th><th class="num">Ghế đã bán</th><th>Trạng thái</th><th></th></tr>
    </thead>
    <tbody>
        <?php foreach ($showtimes as $st): ?>
            <tr>
                <td><?= htmlspecialchars($st['movie_title']) ?></td>
                <td><?= htmlspecialchars($st['cinema_name']) ?> - <?= htmlspecialchars($st['room_name']) ?></td>
                <td><?= htmlspecialchars(date('d/m/Y', strtotime($st['show_date']))) ?></td>
                <td><?= htmlspecialchars(substr($st['start_time'], 0, 5)) ?> - <?= htmlspecialchars(substr($st['end_time'], 0, 5)) ?></td>
                <td class="num"><?= number_format($st['base_price'], 0, ',', '.') ?>đ</td>
                <td class="num"><?= (int) $st['sold_count'] ?></td>
                <td><?= htmlspecialchars(Showtime::statusLabel($st)) ?></td>
                <td class="actions-cell">
                    <?php if ($st['status'] === 'active'): ?>
                        <a class="btn btn-small btn-secondary" href="<?= BASE_URL ?>/admin/showtimes/<?= (int) $st['id'] ?>/edit">Sửa</a>
                    <?php endif; ?>
                    <?php if ($st['status'] === 'active' && (int) $st['sold_count'] === 0): ?>
                        <form method="post" action="<?= BASE_URL ?>/admin/showtimes/<?= (int) $st['id'] ?>/cancel" onsubmit="return confirm('Hủy suất chiếu này? Suất sẽ không còn mở bán.');">
                            <?= Csrf::field() ?>
                            <button type="submit" class="btn btn-small btn-danger">Hủy suất</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($showtimes)): ?>
            <tr><td colspan="8">Chưa có suất chiếu nào.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>
