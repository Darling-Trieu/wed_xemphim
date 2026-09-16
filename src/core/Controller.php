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
