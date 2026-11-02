<?php
$showingCount = count(array_filter($movies, static fn ($m) => $m['status'] === 'now_showing'));
$statusView = [
    'now_showing' => ['Đang chiếu', 'status-dot--on'],
    'coming_soon' => ['Sắp chiếu', 'status-dot--soon'],
    'ended'       => ['Ngừng chiếu', 'status-dot--off'],
];
?>
<div class="admin-toolbar">
    <div>
        <p class="admin-eyebrow">Thư viện điện ảnh</p>
        <h1>Quản lý phim</h1>
        <p class="admin-summary"><?= count($movies) ?> phim · <?= $showingCount ?> phim đang chiếu</p>
    </div>
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
            <th><span class="sr-only">Thao tác</span></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($movies as $movie): ?>
            <?php [$statusText, $statusClass] = $statusView[$movie['status']] ?? $statusView['ended']; ?>
            <tr>
                <td><?= htmlspecialchars($movie['title']) ?></td>
                <td><?= (int) $movie['duration_minutes'] ?> phút</td>
                <td><span class="age-label"><?= htmlspecialchars($movie['age_rating']) ?></span></td>
                <td><span class="status-dot <?= $statusClass ?>"><?= $statusText ?></span></td>
                <td><?= $movie['release_date'] ? htmlspecialchars(date('d/m/Y', strtotime($movie['release_date']))) : '-' ?></td>
                <td class="actions-cell">
                    <a class="btn btn-small btn-secondary" href="<?= BASE_URL ?>/admin/movies/<?= (int) $movie['id'] ?>/edit">Sửa</a>
                    <form method="post" action="<?= BASE_URL ?>/admin/movies/<?= (int) $movie['id'] ?>/delete" onsubmit="return confirm('Xóa phim này? Phim đã có suất chiếu sẽ chỉ chuyển sang Ngừng chiếu.');">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn-small btn-danger">Xóa</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($movies)): ?>
            <tr><td colspan="6" class="table-empty">Chưa có phim nào trong danh sách.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>
