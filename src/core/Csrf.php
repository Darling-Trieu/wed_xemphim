<?php

class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::token()) . '">';
    }

    public static function verify(?string $token): bool
    {
        return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function verifyRequest(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!self::verify($token)) {
            http_response_code(419);
            echo 'Phiên làm việc đã hết hạn, vui lòng tải lại trang và thử lại.';
            exit;
        }
    }
}
