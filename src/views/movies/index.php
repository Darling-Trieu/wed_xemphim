<h1 class="section-title">
    <?php if ($status === 'now_showing'): ?>Phim đang chiếu
    <?php elseif ($status === 'coming_soon'): ?>Phim sắp chiếu
    <?php else: ?>Tất cả phim
    <?php endif; ?>
</h1>

<div class="filter-bar">
    <a class="btn btn-small <?= $status === null ? '' : 'btn-secondary' ?>" href="<?= BASE_URL ?>/movies">Tất cả</a>
    <a class="btn btn-small <?= $status === 'now_showing' ? '' : 'btn-secondary' ?>" href="<?= BASE_URL ?>/movies?status=now_showing">Đang chiếu</a>
    <a class="btn btn-small <?= $status === 'coming_soon' ? '' : 'btn-secondary' ?>" href="<?= BASE_URL ?>/movies?status=coming_soon">Sắp chiếu</a>
</div>

<div class="movie-grid">
    <?php if (empty($movies)): ?>
        <p>Không có phim nào.</p>
    <?php endif; ?>
    <?php foreach ($movies as $movie): ?>
        <?php include __DIR__ . '/../partials/movie_card.php'; ?>
    <?php endforeach; ?>
</div>
