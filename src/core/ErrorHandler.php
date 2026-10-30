<?php

/**
 * Bắt mọi exception chưa xử lý: ghi chi tiết vào logs/app.log, người dùng chỉ thấy
 * "Lỗi hệ thống, vui lòng thử lại" (không lộ câu SQL / stack trace).
 */
class ErrorHandler
{
    public const MESSAGE = 'Lỗi hệ thống, vui lòng thử lại.';

    public static function register(bool $json = false): void
    {
        ini_set('display_errors', '0');
        error_reporting(E_ALL);

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(static function (Throwable $e) use ($json): void {
            self::log($e);

            if (!headers_sent()) {
                http_response_code(500);
            }

            if ($json) {
                if (!headers_sent()) {
                    header('Content-Type: application/json; charset=utf-8');
                }
                echo json_encode(['error' => self::MESSAGE], JSON_UNESCAPED_UNICODE);
                return;
            }

            require dirname(__DIR__) . '/views/errors/500.php';
        });
    }

    public static function log(Throwable $e): void
    {
        $dir = dirname(__DIR__, 2) . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $line = sprintf(
            "[%s] %s: %s in %s:%d\n%s\n\n",
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        @file_put_contents($dir . '/app.log', $line, FILE_APPEND);
    }
}
