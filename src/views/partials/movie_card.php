<?php /** @var array $movie */ ?>
<a class="movie-card" href="<?= BASE_URL ?>/movies/<?= (int) $movie['id'] ?>">
    <div class="movie-poster" style="<?= $movie['poster_path'] ? 'background-image:url(' . htmlspecialchars(UPLOAD_URL . '/' . $movie['poster_path']) . ')' : '' ?>">
        <?= $movie['poster_path'] ? '' : 'Chưa có poster' ?>
    </div>
    <div class="movie-card-body">
        <h3><?= htmlspecialchars($movie['title']) ?></h3>
        <div class="movie-meta">
            <?= (int) $movie['duration_minutes'] ?> phút &middot; <?= htmlspecialchars($movie['age_rating']) ?>
        </div>
        <div style="margin-top:8px;">
            <span class="badge <?= $movie['status'] === 'coming_soon' ? 'badge-soon' : '' ?>">
                <?= $movie['status'] === 'now_showing' ? 'Đang chiếu' : ($movie['status'] === 'coming_soon' ? 'Sắp chiếu' : 'Ngừng chiếu') ?>
            </span>
        </div>
    </div>
</a>
