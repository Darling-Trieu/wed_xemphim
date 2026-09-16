<?php

require_once __DIR__ . '/../models/Movie.php';
require_once __DIR__ . '/../models/Genre.php';
require_once __DIR__ . '/../models/Review.php';

class MovieController extends Controller
{
    public function index(): void
    {
        $status = $this->input('status');
        if (!in_array($status, ['now_showing', 'coming_soon'], true)) {
            $status = null;
        }

        $movies = Movie::allByStatus($status);

        $this->view('movies/index', [
            'pageTitle' => 'Danh sách phim',
            'movies'    => $movies,
            'status'    => $status,
        ]);
    }

    public function show(string $id): void
    {
        $movie = Movie::find((int) $id);
        if (!$movie) {
            $this->notFound();
            return;
        }

        $genres = Genre::forMovie((int) $id);
        $reviews = Review::allByMovie((int) $id);
        $avgRating = Review::averageRating((int) $id);

        $this->view('movies/show', [
            'pageTitle' => $movie['title'],
            'movie'     => $movie,
            'genres'    => $genres,
            'reviews'   => $reviews,
            'avgRating' => $avgRating,
        ]);
    }

    public function storeReview(string $id): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $movie = Movie::find((int) $id);
        if (!$movie) {
            $this->notFound();
            return;
        }

        $rating = (int) $this->input('rating', 5);
        $rating = max(1, min(5, $rating));
        $comment = $this->input('comment');

        Review::create((int) $id, Auth::id(), $rating, $comment ?: null);

        $this->flash('success', 'Cảm ơn bạn đã đánh giá phim!');
        $this->redirect('/movies/' . $id);
    }
}
