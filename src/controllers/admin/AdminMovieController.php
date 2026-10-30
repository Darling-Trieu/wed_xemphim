<?php

require_once __DIR__ . '/../../models/Movie.php';
require_once __DIR__ . '/../../models/Genre.php';
require_once __DIR__ . '/../../models/AuditLog.php';

class AdminMovieController extends Controller
{
    private const ALLOWED_MIME = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    private const MAX_SIZE = 3 * 1024 * 1024; // 3MB

    public function index(): void
    {
        Auth::requireAdmin();
        $this->adminView('admin/movies/index', [
            'pageTitle' => 'Quản lý phim',
            'movies'    => Movie::allByStatus(null),
        ]);
    }

    public function createForm(): void
    {
        Auth::requireAdmin();
        $this->adminView('admin/movies/form', [
            'pageTitle' => 'Thêm phim',
            'movie'     => null,
            'genres'    => Genre::all(),
            'selectedGenres' => [],
        ]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();

        $data = $this->collectInput();
        if ($error = $this->validateTrailer($data['trailer_url'])) {
            $this->flash('error', $error);
            $this->redirect('/admin/movies/create');
            return;
        }
        $posterPath = $this->handleUpload();
        $data['poster_path'] = $posterPath;

        $movieId = Movie::create($data);
        Genre::syncForMovie($movieId, $_POST['genre_ids'] ?? []);
        AuditLog::write('create', 'movie', $movieId, null, $data);

        $this->flash('success', 'Đã thêm phim mới.');
        $this->redirect('/admin/movies');
    }

    public function editForm(string $id): void
    {
        Auth::requireAdmin();
        $movie = Movie::find((int) $id);
        if (!$movie) {
            $this->notFound();
            return;
        }

        $selectedGenres = array_column(Genre::forMovie((int) $id), 'id');

        $this->adminView('admin/movies/form', [
            'pageTitle' => 'Sửa phim',
            'movie'     => $movie,
            'genres'    => Genre::all(),
            'selectedGenres' => $selectedGenres,
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();

        $before = Movie::find((int) $id);
        if (!$before) {
            $this->notFound();
            return;
        }

        $data = $this->collectInput();
        if ($error = $this->validateTrailer($data['trailer_url'])) {
            $this->flash('error', $error);
            $this->redirect('/admin/movies/' . (int) $id . '/edit');
            return;
        }
        $posterPath = $this->handleUpload();
        if ($posterPath) {
            $data['poster_path'] = $posterPath;
        }

        Movie::update((int) $id, $data);
        Genre::syncForMovie((int) $id, $_POST['genre_ids'] ?? []);
        AuditLog::write('update', 'movie', $id, $before, Movie::find((int) $id));

        $this->flash('success', 'Đã cập nhật phim.');
        $this->redirect('/admin/movies');
    }

    /**
     * BR09: phim đã có suất chiếu (và có thể đã có vé) không xóa cứng, chỉ chuyển Ngừng chiếu.
     * Phim chưa từng được xếp suất thì xóa được.
     */
    public function delete(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();

        $movie = Movie::find((int) $id);
        if (!$movie) {
            $this->notFound();
            return;
        }

        if (Movie::hasShowtimes((int) $id)) {
            Movie::setStatus((int) $id, 'ended');
            AuditLog::write('deactivate', 'movie', $id, ['status' => $movie['status']], ['status' => 'ended']);
            $this->flash('success', 'Phim đã có suất chiếu nên không xóa, đã chuyển sang Ngừng chiếu.');
        } else {
            Movie::delete((int) $id);
            AuditLog::write('delete', 'movie', $id, $movie, null);
            $this->flash('success', 'Đã xóa phim.');
        }
        $this->redirect('/admin/movies');
    }

    /** Chỉ nhận link http/https (chặn "javascript:..." bị in vào href -> XSS). */
    private function validateTrailer(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true) || strlen($url) > 255) {
            return 'Link trailer phải là URL http(s) hợp lệ.';
        }
        return null;
    }

    private function collectInput(): array
    {
        return [
            'title'            => (string) $this->input('title'),
            'description'      => (string) $this->input('description'),
            'duration_minutes' => (int) $this->input('duration_minutes'),
            'trailer_url'      => $this->input('trailer_url') ?: null,
            'age_rating'       => (string) $this->input('age_rating', 'P'),
            'status'           => (string) $this->input('status', 'coming_soon'),
            'release_date'     => $this->input('release_date'),
            'poster_path'      => null,
        ];
    }

    private function handleUpload(): ?string
    {
        if (empty($_FILES['poster']) || $_FILES['poster']['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $file = $_FILES['poster'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Tải ảnh lên thất bại.');
            return null;
        }
        if ($file['size'] > self::MAX_SIZE) {
            $this->flash('error', 'Ảnh vượt quá dung lượng cho phép (3MB).');
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset(self::ALLOWED_MIME[$mime])) {
            $this->flash('error', 'Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.');
            return null;
        }

        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.' . self::ALLOWED_MIME[$mime];
        move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $filename);

        return $filename;
    }
}
