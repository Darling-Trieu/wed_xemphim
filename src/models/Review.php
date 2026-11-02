<?php

class Review extends Model
{
    public static function allByMovie(int $movieId): array
    {
        $stmt = self::db()->prepare(
            'SELECT r.*, u.full_name FROM reviews r
             INNER JOIN users u ON u.id = r.user_id
             WHERE r.movie_id = ?
             ORDER BY r.created_at DESC'
        );
        $stmt->execute([$movieId]);
        return $stmt->fetchAll();
    }

    /** Mỗi user 1 đánh giá / phim (BR08): đánh giá lại thì cập nhật bản cũ. */
    public static function create(int $movieId, int $userId, int $rating, ?string $comment): void
    {
        $stmt = self::db()->prepare(
            'INSERT INTO reviews (movie_id, user_id, rating, comment) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment)'
        );
        $stmt->execute([$movieId, $userId, $rating, $comment]);
    }

    public static function averageRating(int $movieId): ?float
    {
        $stmt = self::db()->prepare('SELECT AVG(rating) FROM reviews WHERE movie_id = ?');
        $stmt->execute([$movieId]);
        $avg = $stmt->fetchColumn();
        return $avg !== null ? round((float) $avg, 1) : null;
    }
}
