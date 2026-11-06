<?php

class Auth
{
    public static function login(array $user): void
    {
        self::regenerateSession();
        $_SESSION['user'] = [
            'id'        => $user['id'],
            'full_name' => $user['full_name'],
            'email'     => $user['email'],
            'role'      => $user['role'],
        ];
    }

    /**
     * Đổi session ID (chống session fixation). Ghế khách đang giữ gắn với session ID nên được
     * chuyển sang ID mới, để khách chọn ghế rồi mới đăng nhập vẫn đặt được đúng ghế đó.
     */
    public static function regenerateSession(): void
    {
        $oldId = session_id();
        session_regenerate_id(true);
        Database::getConnection()
            ->prepare('UPDATE seat_holds SET session_id = ? WHERE session_id = ?')
            ->execute([session_id(), $oldId]);
    }

    public static function logout(): void
    {
        Database::getConnection()->prepare('DELETE FROM seat_holds WHERE session_id = ?')->execute([session_id()]);
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        session_start();
        session_regenerate_id(true);
    }

    /**
     * Gọi đầu mỗi request: đọc lại trạng thái tài khoản từ CSDL để tài khoản vừa bị
     * admin khóa (hoặc đổi vai trò) bị chặn ngay ở request kế tiếp (UC14).
     */
    public static function syncWithDatabase(): void
    {
        if (!self::check()) {
            return;
        }

        $stmt = Database::getConnection()->prepare('SELECT full_name, role, status FROM users WHERE id = ?');
        $stmt->execute([self::id()]);
        $row = $stmt->fetch();

        if (!$row || $row['status'] !== 'active') {
            self::logout();
            $_SESSION['flash']['error'] = 'Tài khoản đang bị khóa, vui lòng liên hệ quản trị viên.';
            return;
        }

        $_SESSION['user']['full_name'] = $row['full_name'];
        $_SESSION['user']['role'] = $row['role'];
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function isAdmin(): bool
    {
        return self::check() && $_SESSION['user']['role'] === 'admin';
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            // Chỉ nhớ trang GET để quay lại sau khi đăng nhập (route POST không mở lại được bằng GET)
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
                $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? BASE_URL . '/';
            }
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            echo 'Bạn không có quyền truy cập trang này.';
            exit;
        }
    }
}
