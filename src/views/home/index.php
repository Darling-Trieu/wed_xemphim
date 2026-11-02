<?php
/**
 * @var array $nowShowing
 * @var array $comingSoon
 */
// Cụm 3 poster trong hero: các phim đang chiếu có poster
$collage = array_slice(array_values(array_filter($nowShowing, static fn ($m) => !empty($m['poster_path']))), 0, 3);
?>
<section class="hero">
    <div class="hero__bg" aria-hidden="true">
        <img src="<?= BASE_URL ?>/asset/pic/hero-cinema.png" alt="">
        <img src="<?= BASE_URL ?>/asset/pic/hero-cinema.png" alt="">
    </div>
    <div class="hero__inner">
        <div class="hero__text">
            <h1 class="hero__title">Xin chào đến với <?= APP_NAME ?></h1>
            <a class="btn-book" href="<?= BASE_URL ?>/showtimes">Xem lịch chiếu</a>
        </div>
        <?php if ($collage): ?>
            <div class="collage">
                <?php foreach ($collage as $i => $movie): ?>
                    <a class="collage__item collage__item--<?= $i + 1 ?>" href="<?= BASE_URL ?>/movies/<?= (int) $movie['id'] ?>">
                        <span class="poster">
                            <img src="<?= htmlspecialchars(UPLOAD_URL . '/' . $movie['poster_path']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
                            <span class="age-badge"><span><?= htmlspecialchars(strtolower($movie['age_rating'])) ?></span></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="movie-section">
    <h2 class="section-title"><span>Phim đang chiếu</span></h2>
    <?php if (empty($nowShowing)): ?>
        <p class="empty-note">Chưa có phim</p>
    <?php else: ?>
        <ul class="movie-grid">
            <?php foreach ($nowShowing as $movie): ?>
                <?php include __DIR__ . '/../partials/movie_card.php'; ?>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="movie-section">
    <h2 class="section-title"><span>Phim sắp chiếu</span></h2>
    <?php if (empty($comingSoon)): ?>
        <p class="empty-note">Chưa có phim</p>
    <?php else: ?>
        <ul class="movie-grid">
            <?php foreach ($comingSoon as $movie): ?>
                <?php include __DIR__ . '/../partials/movie_card.php'; ?>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
