<?php

class Movie extends Model
{
    public static function allByStatus(?string $status = null): array
    {
        if ($status === null) {
            return self::db()->query('SELECT * FROM movies ORDER BY release_date DESC')->fetchAll();
        }
        $stmt = self::db()->prepare('SELECT * FROM movies WHERE status = ? ORDER BY release_date DESC');
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM movies WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO movies (title, description, duration_minutes, poster_path, trailer_url, age_rating, status, release_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['title'],
            $data['description'],
            $data['duration_minutes'],
            $data['poster_path'],
            $data['trailer_url'],
            $data['age_rating'],
            $data['status'],
            $data['release_date'] ?: null,
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $sql = 'UPDATE movies SET title = ?, description = ?, duration_minutes = ?, trailer_url = ?,
                age_rating = ?, status = ?, release_date = ?';
        $params = [
            $data['title'],
            $data['description'],
            $data['duration_minutes'],
            $data['trailer_url'],
            $data['age_rating'],
            $data['status'],
            $data['release_date'] ?: null,
        ];

        if (!empty($data['poster_path'])) {
            $sql .= ', poster_path = ?';
            $params[] = $data['poster_path'];
        }

        $sql .= ' WHERE id = ?';
        $params[] = $id;

        self::db()->prepare($sql)->execute($params);
    }

    /** Phim đặt lịch chiếu được (UC12): Đang chiếu / Sắp chiếu. */
    public static function schedulable(): array
    {
        return self::db()->query(
            "SELECT * FROM movies WHERE status IN ('now_showing', 'coming_soon') ORDER BY title"
        )->fetchAll();
    }

    public static function hasShowtimes(int $id): bool
    {
        $stmt = self::db()->prepare('SELECT EXISTS(SELECT 1 FROM showtimes WHERE movie_id = ?)');
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }

    public static function setStatus(int $id, string $status): void
    {
        self::db()->prepare('UPDATE movies SET status = ? WHERE id = ?')->execute([$status, $id]);
    }

    /** Chỉ dùng cho phim chưa từng có suất chiếu (BR09). */
    public static function delete(int $id): void
    {
        self::db()->prepare('DELETE FROM movies WHERE id = ?')->execute([$id]);
    }

    public static function count(): int
    {
        return (int) self::db()->query('SELECT COUNT(*) FROM movies')->fetchColumn();
    }
}
