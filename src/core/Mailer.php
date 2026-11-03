<?php

class Mailer
{
    /**
     * Gửi email xác nhận đặt vé tới địa chỉ email trong dữ liệu User.
     *
     * @param array{email:string,full_name?:string} $user
     * @param array{booking_code:string,movie_title:string,cinema_name:string,room_name:string,show_date:string,start_time:string,total_amount:int|string} $booking
     * @param array<int,array{seat_label:string}> $seats
     */
    public static function sendBookingConfirmation(array $user, array $booking, array $seats): bool
    {
        $email = trim((string) ($user['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log('Không thể gửi email xác nhận: email người dùng không hợp lệ.');
            return false;
        }

        $name = htmlspecialchars((string) ($user['full_name'] ?? 'khách hàng'), ENT_QUOTES, 'UTF-8');
        $code = htmlspecialchars((string) $booking['booking_code'], ENT_QUOTES, 'UTF-8');
        $movie = htmlspecialchars((string) $booking['movie_title'], ENT_QUOTES, 'UTF-8');
        $cinema = htmlspecialchars((string) $booking['cinema_name'], ENT_QUOTES, 'UTF-8');
        $room = htmlspecialchars((string) $booking['room_name'], ENT_QUOTES, 'UTF-8');
        $showDate = date('d/m/Y', strtotime((string) $booking['show_date']));
        $showTime = htmlspecialchars(substr((string) $booking['start_time'], 0, 5), ENT_QUOTES, 'UTF-8');
        $seatLabels = array_map(
            static fn (array $seat): string => htmlspecialchars((string) $seat['seat_label'], ENT_QUOTES, 'UTF-8'),
            $seats
        );
        $seatList = implode(', ', $seatLabels);
        $total = number_format((float) $booking['total_amount'], 0, ',', '.') . 'đ';

        $subject = 'Xac nhan dat ve - ' . $booking['booking_code'];
        $body = <<<HTML
<!doctype html>
<html lang="vi">
<body>
    <p>Xin chào {$name},</p>
    <p>Đặt vé của bạn đã được xác nhận.</p>
    <p><strong>Mã đơn:</strong> {$code}</p>
    <p><strong>Phim:</strong> {$movie}<br>
       <strong>Rạp:</strong> {$cinema} - {$room}<br>
       <strong>Suất chiếu:</strong> {$showDate} lúc {$showTime}<br>
       <strong>Ghế:</strong> {$seatList}<br>
       <strong>Tổng tiền:</strong> {$total}</p>
    <p>Vui lòng xuất trình mã đơn khi đến nhận vé.</p>
</body>
</html>
HTML;

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM_ADDRESS . '>',
        ];

        $sent = @mail($email, $subject, $body, implode("\r\n", $headers));
        if (!$sent) {
            error_log('Không thể gửi email xác nhận đặt vé tới ' . $email . '. Kiểm tra cấu hình mail() / SMTP.');
        }

        return $sent;
    }
}
