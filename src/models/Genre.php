<?php

class Genre extends Model
{
    public static function all(): array
    {
        return self::db()->query('SELECT * FROM genres ORDER BY name')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM genres WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(string $name): int
    {
        $stmt = self::db()->prepare('INSERT INTO genres (name) VALUES (?)');
        $stmt->execute([$name]);
        return (int) self::db()->lastInsertId();
    }

    public static function delete(int $id): void
    {
        self::db()->prepare('DELETE FROM genres WHERE id = ?')->execute([$id]);
    }

    public static function forMovie(int $movieId): array
    {
        $stmt = self::db()->prepare(
            'SELECT g.* FROM genres g
             INNER JOIN movie_genres mg ON mg.genre_id = g.id
             WHERE mg.movie_id = ? ORDER BY g.name'
        );
        $stmt->execute([$movieId]);
        return $stmt->fetchAll();
    }

    public static function syncForMovie(int $movieId, array $genreIds): void
    {
        $db = self::db();
        $db->prepare('DELETE FROM movie_genres WHERE movie_id = ?')->execute([$movieId]);

        if (empty($genreIds)) {
            return;
        }

        $stmt = $db->prepare('INSERT INTO movie_genres (movie_id, genre_id) VALUES (?, ?)');
        foreach ($genreIds as $genreId) {
            $stmt->execute([$movieId, (int) $genreId]);
        }
    }
}
