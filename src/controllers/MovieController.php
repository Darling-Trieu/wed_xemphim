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

        // UC01: phim Ngừng chiếu không hiện phía khách
        $movies = $status !== null
            ? Movie::allByStatus($status)
            : array_merge(Movie::allByStatus('now_showing'), Movie::allByStatus('coming_soon'));

        $this->view('movies/index', [
            'pageTitle' => 'Danh sách phim',
            'movies'    => $movies,
            'status'    => $status,
        ]);
    }

    public function show(string $id): void
    {
        $movie = Movie::find((int) $id);
        // UC01: phim Ngừng chiếu không hiện phía khách, kể cả khi gõ thẳng URL
        if (!$movie || $movie['status'] === 'ended') {
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
            'canReview' => Auth::check() && Review::canReview(Auth::id(), (int) $id),
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

        if (!Review::canReview(Auth::id(), (int) $id)) {
            $this->flash('error', 'Bạn chỉ có thể đánh giá sau khi đã xem phim.');
            $this->redirect('/movies/' . (int) $id);
            return;
        }

        $rating = (int) $this->input('rating', 5);
        $rating = max(1, min(5, $rating));
        $comment = (string) $this->input('comment', '');
        if (mb_strlen($comment) > 500) {
            $this->flash('error', 'Nhận xét tối đa 500 ký tự.');
            $this->redirect('/movies/' . (int) $id);
            return;
        }

        Review::create((int) $id, Auth::id(), $rating, $comment ?: null);

        $this->flash('success', 'Cảm ơn bạn đã đánh giá phim!');
        $this->redirect('/movies/' . $id);
    }
}
