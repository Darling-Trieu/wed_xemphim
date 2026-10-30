<div class="movie-detail">
    <div class="movie-poster" style="<?= $movie['poster_path'] ? 'background-image:url(' . htmlspecialchars(UPLOAD_URL . '/' . $movie['poster_path']) . ')' : '' ?>">
        <?= $movie['poster_path'] ? '' : 'Chưa có poster' ?>
    </div>
    <div class="movie-info">
        <h1><?= htmlspecialchars($movie['title']) ?></h1>
        <?php if ($avgRating): ?>
            <p class="stars">★ <?= $avgRating ?>/5</p>
        <?php endif; ?>
        <dl>
            <dt>Thể loại</dt>
            <dd><?= $genres ? htmlspecialchars(implode(', ', array_column($genres, 'name'))) : 'Đang cập nhật' ?></dd>
            <dt>Thời lượng</dt>
            <dd><?= (int) $movie['duration_minutes'] ?> phút</dd>
            <dt>Độ tuổi</dt>
            <dd><?= htmlspecialchars($movie['age_rating']) ?></dd>
            <dt>Khởi chiếu</dt>
            <dd><?= $movie['release_date'] ? htmlspecialchars(date('d/m/Y', strtotime($movie['release_date']))) : 'Đang cập nhật' ?></dd>
            <dt>Trạng thái</dt>
            <dd><?= $movie['status'] === 'now_showing' ? 'Đang chiếu' : ($movie['status'] === 'coming_soon' ? 'Sắp chiếu' : 'Ngừng chiếu') ?></dd>
        </dl>
        <p><?= nl2br(htmlspecialchars($movie['description'] ?? '')) ?></p>

        <?php // Chỉ in link http(s): dữ liệu cũ có thể chứa "javascript:" ?>
        <?php if ($movie['trailer_url'] && preg_match('#^https?://#i', $movie['trailer_url'])): ?>
            <p><a class="btn btn-secondary" href="<?= htmlspecialchars($movie['trailer_url']) ?>" target="_blank" rel="noopener">Xem trailer</a></p>
        <?php endif; ?>

        <?php if ($movie['status'] === 'now_showing'): ?>
            <a class="btn" href="<?= BASE_URL ?>/showtimes?movie_id=<?= (int) $movie['id'] ?>">Chọn suất chiếu &amp; đặt vé</a>
        <?php endif; ?>
    </div>
</div>

<h2 class="section-title">Đánh giá phim</h2>

<?php if (Auth::check()): ?>
    <form class="form-card" method="post" action="<?= BASE_URL ?>/movies/<?= (int) $movie['id'] ?>/reviews" style="margin-left:0;">
        <?= Csrf::field() ?>
        <div class="form-group">
            <label>Đánh giá</label>
            <select name="rating" class="form-control">
                <option value="5">★★★★★ (5)</option>
                <option value="4">★★★★ (4)</option>
                <option value="3">★★★ (3)</option>
                <option value="2">★★ (2)</option>
                <option value="1">★ (1)</option>
            </select>
        </div>
        <div class="form-group">
            <label>Nhận xét</label>
            <textarea name="comment" class="form-control" placeholder="Chia sẻ cảm nhận của bạn..."></textarea>
        </div>
        <button type="submit" class="btn">Gửi đánh giá</button>
    </form>
<?php else: ?>
    <p><a href="<?= BASE_URL ?>/login">Đăng nhập</a> để đánh giá phim này.</p>
<?php endif; ?>

<?php foreach ($reviews as $review): ?>
    <div class="review-item">
        <strong><?= htmlspecialchars($review['full_name']) ?> <span class="stars">★<?= (int) $review['rating'] ?></span></strong>
        <span class="movie-meta"><?= htmlspecialchars(date('d/m/Y', strtotime($review['created_at']))) ?></span>
        <?php if ($review['comment']): ?><p><?= nl2br(htmlspecialchars($review['comment'])) ?></p><?php endif; ?>
    </div>
<?php endforeach; ?>
<?php if (empty($reviews)): ?>
    <p>Chưa có đánh giá nào cho phim này.</p>
<?php endif; ?>
