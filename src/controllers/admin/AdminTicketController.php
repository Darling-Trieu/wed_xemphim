<?php

require_once __DIR__ . '/../../models/Booking.php';
require_once __DIR__ . '/../../models/BookingSeat.php';

class AdminTicketController extends Controller
{
    public function index(): void
    {
        Auth::requireAdmin();
        $this->adminView('admin/bookings/index', [
            'pageTitle'     => 'Quản lý đơn đặt vé',
            'bookings'      => Booking::allWithDetails(),
            'totalRefunded' => Booking::totalRefunded(),
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
        if (!$booking) {
            $this->notFound();
            return;
        }

        $reason = trim((string) $this->input('cancel_reason'));
        if ($reason === '' || mb_strlen($reason) > 255) {
            $this->flash('error', 'Vui lòng nhập lý do hủy (tối đa 255 ký tự).');
            $this->redirect('/admin/bookings/' . rawurlencode($code));
            return;
        }

        try {
            Booking::cancelAndRefund((int) $booking['id'], $reason);
        } catch (BookingException $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect('/admin/bookings/' . rawurlencode($code));
            return;
        }

        $this->flash('success', 'Đã hủy đơn vé ' . $code . ' và hoàn '
            . number_format($booking['total_amount'], 0, ',', '.') . 'đ cho khách. Ghế đã được trả lại.');
        $this->redirect('/admin/bookings');
    }
}
