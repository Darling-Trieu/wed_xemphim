<?php

require_once __DIR__ . '/../../models/Movie.php';
require_once __DIR__ . '/../../models/Cinema.php';
require_once __DIR__ . '/../../models/Room.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Report.php';

/** Dashboard thống kê vé / doanh thu theo khoảng ngày, phim, rạp, phòng (FR12, UC15). */
class AdminDashboardController extends Controller
{
    private const DEFAULT_DAYS = 14;
    private const MAX_DAYS = 366;

    public function index(): void
    {
        Auth::requireAdmin();

        $filters = [
            'from'      => (string) $this->input('from', date('Y-m-d', strtotime('-' . (self::DEFAULT_DAYS - 1) . ' days'))),
            'to'        => (string) $this->input('to', date('Y-m-d')),
            'movie_id'  => (int) $this->input('movie_id', 0) ?: null,
            'cinema_id' => (int) $this->input('cinema_id', 0) ?: null,
            'room_id'   => (int) $this->input('room_id', 0) ?: null,
        ];
        $error = $this->validateRange($filters['from'], $filters['to']);

        $this->adminView('admin/dashboard/index', [
            'pageTitle'     => 'Dashboard',
            'filters'       => $filters,
            'error'         => $error,
            'summary'       => $error ? null : Report::summary($filters),
            'byDay'         => $error ? [] : Report::byDay($filters),
            'byMovie'       => $error ? [] : Report::byMovie($filters),
            'movies'        => Movie::allByStatus(null),
            'cinemas'       => Cinema::all(),
            'rooms'         => Room::allWithCinema(),
            'movieCount'    => Movie::count(),
            'customerCount' => User::countCustomers(),
            'pageScripts'   => ['admin-charts'],
        ]);
    }

    private function validateRange(string $from, string $to): ?string
    {
        if ($from === '' || $to === '') {
            return 'Vui lòng chọn Từ ngày và Đến ngày.';
        }
        $fromDate = DateTime::createFromFormat('!Y-m-d', $from);
        $toDate = DateTime::createFromFormat('!Y-m-d', $to);
        if (!$fromDate || $fromDate->format('Y-m-d') !== $from || !$toDate || $toDate->format('Y-m-d') !== $to) {
            return 'Ngày không hợp lệ.';
        }
        if ($toDate < $fromDate) {
            return 'Đến ngày phải sau hoặc bằng Từ ngày.';
        }
        if ($fromDate->diff($toDate)->days >= self::MAX_DAYS) {
            return 'Khoảng thống kê tối đa ' . self::MAX_DAYS . ' ngày.';
        }
        return null;
    }
}
