<?php /** @var array $movie */ ?>
<li class="movie-card">
    <a class="poster" href="<?= BASE_URL ?>/movies/<?= (int) $movie['id'] ?>" title="<?= htmlspecialchars($movie['title']) ?>">
        <?php if ($movie['poster_path']): ?>
            <img src="<?= htmlspecialchars(UPLOAD_URL . '/' . $movie['poster_path']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>" loading="lazy">
        <?php else: ?>
            <span class="poster-empty"><?= htmlspecialchars($movie['title']) ?></span>
        <?php endif; ?>
        <span class="age-badge"><span><?= htmlspecialchars(strtolower($movie['age_rating'])) ?></span></span>
    </a>
    <div class="movie-card-info">
        <h3><a href="<?= BASE_URL ?>/movies/<?= (int) $movie['id'] ?>"><?= htmlspecialchars($movie['title']) ?></a></h3>
        <?php if ($movie['status'] === 'coming_soon' && $movie['release_date']): ?>
            <div class="movie-card-release">Ngày khởi chiếu: <?= htmlspecialchars(date('d/m/Y', strtotime($movie['release_date']))) ?></div>
        <?php endif; ?>
        <div class="movie-meta"><?= (int) $movie['duration_minutes'] ?> phút &middot; <?= htmlspecialchars($movie['age_rating']) ?></div>
    </div>
    <?php if ($movie['status'] === 'now_showing'): ?>
        <a class="btn-book" href="<?= BASE_URL ?>/showtimes?movie_id=<?= (int) $movie['id'] ?>">Đặt ngay</a>
    <?php else: ?>
        <a class="btn-book btn-book--soon" href="<?= BASE_URL ?>/movies/<?= (int) $movie['id'] ?>">Chi tiết</a>
    <?php endif; ?>
</li>
