<?php

abstract class Controller
{
    /**
     * Render 1 view PHP thuần với layout mặc định (header/footer phía khách).
     */
    protected function view(string $view, array $data = []): void
    {
        extract($data);
        $viewFile = dirname(__DIR__) . '/views/' . $view . '.php';

        require dirname(__DIR__) . '/views/layouts/header.php';
        require $viewFile;
        require dirname(__DIR__) . '/views/layouts/footer.php';
    }

    /**
     * Render 1 view PHP thuần với layout dành cho khu vực quản trị.
     */
    protected function adminView(string $view, array $data = []): void
    {
        extract($data);
        $viewFile = dirname(__DIR__) . '/views/' . $view . '.php';

        require dirname(__DIR__) . '/views/layouts/admin_header.php';
        require $viewFile;
        require dirname(__DIR__) . '/views/layouts/admin_footer.php';
    }

    /**
     * Render view không kèm layout nào (trang login/register/lỗi...).
     */
    protected function bareView(string $view, array $data = []): void
    {
        extract($data);
        require dirname(__DIR__) . '/views/' . $view . '.php';
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . BASE_URL . $path);
        exit;
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function input(string $key, $default = null)
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    /** Chuỗi có độ dài (ký tự Unicode) trong khoảng [$min, $max], dùng trước khi ghi vào cột VARCHAR. */
    protected function validLength(?string $value, int $max, int $min = 1): bool
    {
        $length = mb_strlen((string) $value);
        return $length >= $min && $length <= $max;
    }

    /** SĐT Việt Nam 10 chữ số, bắt đầu bằng 0. */
    protected function validPhone(?string $phone): bool
    {
        return is_string($phone) && preg_match('/^0\d{9}$/', $phone) === 1;
    }

    protected function flash(string $key, ?string $message = null)
    {
        if ($message !== null) {
            $_SESSION['flash'][$key] = $message;
            return null;
        }

        $value = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $value;
    }

    protected function notFound(): void
    {
        http_response_code(404);
        $this->bareView('errors/404');
        exit;
    }
}
