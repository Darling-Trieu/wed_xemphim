<h2 class="section-title">Phim đang chiếu</h2>
<div class="movie-grid">
    <?php if (empty($nowShowing)): ?>
        <p>Hiện chưa có phim đang chiếu.</p>
    <?php endif; ?>
    <?php foreach ($nowShowing as $movie): ?>
        <?php include __DIR__ . '/../partials/movie_card.php'; ?>
    <?php endforeach; ?>
</div>

<h2 class="section-title">Phim sắp chiếu</h2>
<div class="movie-grid">
    <?php if (empty($comingSoon)): ?>
        <p>Hiện chưa có phim sắp chiếu.</p>
    <?php endif; ?>
    <?php foreach ($comingSoon as $movie): ?>
        <?php include __DIR__ . '/../partials/movie_card.php'; ?>
    <?php endforeach; ?>
</div>
