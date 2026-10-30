<div class="admin-toolbar">
    <h1 style="margin:0;">Quản lý phim</h1>
    <a class="btn" href="<?= BASE_URL ?>/admin/movies/create">+ Thêm phim</a>
</div>

<div class="table-responsive">
<table class="data-table">
    <thead>
        <tr>
            <th>Tên phim</th>
            <th>Thời lượng</th>
            <th>Độ tuổi</th>
            <th>Trạng thái</th>
            <th>Khởi chiếu</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($movies as $movie): ?>
            <tr>
                <td><?= htmlspecialchars($movie['title']) ?></td>
                <td><?= (int) $movie['duration_minutes'] ?> phút</td>
                <td><?= htmlspecialchars($movie['age_rating']) ?></td>
                <td>
                    <?= $movie['status'] === 'now_showing' ? 'Đang chiếu' : ($movie['status'] === 'coming_soon' ? 'Sắp chiếu' : 'Ngừng chiếu') ?>
                </td>
                <td><?= $movie['release_date'] ? htmlspecialchars(date('d/m/Y', strtotime($movie['release_date']))) : '-' ?></td>
                <td class="actions-cell">
                    <a class="btn btn-small btn-secondary" href="<?= BASE_URL ?>/admin/movies/<?= (int) $movie['id'] ?>/edit">Sửa</a>
                    <form method="post" action="<?= BASE_URL ?>/admin/movies/<?= (int) $movie['id'] ?>/delete" onsubmit="return confirm('Xóa phim này? Phim đã có suất chiếu sẽ chỉ chuyển sang Ngừng chiếu.');">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn-small btn-danger">Xóa / Ngừng chiếu</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($movies)): ?>
            <tr><td colspan="6">Chưa có phim nào.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>
