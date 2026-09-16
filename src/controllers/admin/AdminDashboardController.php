<?php

require_once __DIR__ . '/../../models/Movie.php';
require_once __DIR__ . '/../../models/Showtime.php';
require_once __DIR__ . '/../../models/Booking.php';
require_once __DIR__ . '/../../models/User.php';

class AdminDashboardController extends Controller
{
    public function index(): void
    {
        Auth::requireAdmin();

        $this->adminView('admin/dashboard/index', [
            'pageTitle'     => 'Dashboard',
            'movieCount'    => Movie::count(),
            'showtimeCount' => Showtime::count(),
            'ticketCount'   => Booking::countPaid(),
            'revenue'       => Booking::totalRevenue(),
            'customerCount' => User::countCustomers(),
            'pageScripts'   => ['admin-charts'],
        ]);
    }
}
