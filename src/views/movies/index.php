<div class="page-head">
    <h1>
        <?php if ($status === 'now_showing'): ?>Phim Đang Chiếu
        <?php elseif ($status === 'coming_soon'): ?>Phim Sắp Chiếu
        <?php else: ?>Tất Cả Phim
        <?php endif; ?>
    </h1>
    <div class="page-head__tabs">
        <a href="<?= BASE_URL ?>/movies"<?= $status === null ? ' aria-current="page"' : '' ?>>Tất cả</a>
        <a href="<?= BASE_URL ?>/movies?status=now_showing"<?= $status === 'now_showing' ? ' aria-current="page"' : '' ?>>Đang chiếu</a>
        <a href="<?= BASE_URL ?>/movies?status=coming_soon"<?= $status === 'coming_soon' ? ' aria-current="page"' : '' ?>>Sắp chiếu</a>
    </div>
</div>

<?php if (empty($movies)): ?>
    <p class="empty-note">Chưa có phim</p>
<?php else: ?>
    <ul class="movie-grid">
        <?php foreach ($movies as $movie): ?>
            <?php include __DIR__ . '/../partials/movie_card.php'; ?>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
