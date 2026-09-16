<?php

require_once __DIR__ . '/../../models/Booking.php';
require_once __DIR__ . '/../../models/BookingSeat.php';

class AdminTicketController extends Controller
{
    public function index(): void
    {
        Auth::requireAdmin();
        $this->adminView('admin/bookings/index', [
            'pageTitle' => 'Quản lý đơn đặt vé',
            'bookings'  => Booking::allWithDetails(),
        ]);
    }

    public function show(string $code): void
    {
        Auth::requireAdmin();
        $booking = Booking::findByCode($code);
        if (!$booking) {
            $this->notFound();
            return;
        }

        $this->adminView('admin/bookings/show', [
            'pageTitle' => 'Đơn vé ' . $code,
            'booking'   => $booking,
            'seats'     => BookingSeat::allByBooking((int) $booking['id']),
        ]);
    }

    public function cancel(string $code): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();

        $booking = Booking::findByCode($code);
        if ($booking) {
            Booking::cancel((int) $booking['id']);
            $this->flash('success', 'Đã hủy đơn vé ' . $code . '.');
        }

        $this->redirect('/admin/bookings');
    }
}
