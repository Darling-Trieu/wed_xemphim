<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/AuditLog.php';

class AuthController extends Controller
{
    public function registerForm(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
            return;
        }
        $this->bareView('auth/register', ['pageTitle' => 'Đăng ký']);
    }

    public function register(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
            return;
        }
        Csrf::verifyRequest();

        $fullName = (string) $this->input('full_name', '');
        $email = strtolower((string) $this->input('email'));
        $phone = (string) $this->input('phone', '');
        $password = $this->passwordInput('password');
        $passwordConfirm = $this->passwordInput('password_confirm');

        $errors = [];
        if (!$this->validLength($fullName, 100)) {
            $errors['full_name'] = 'Vui lòng nhập đầy đủ họ tên, tối đa 100 ký tự.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            $errors['email'] = 'Email không hợp lệ.';
        }
        if (!$this->validPhone($phone)) {
            $errors['phone'] = 'Số điện thoại phải gồm 10 chữ số, bắt đầu bằng 0.';
        }
        if (!$this->validPassword($password)) {
            $errors['password'] = 'Mật khẩu phải có ít nhất 8 ký tự, có chữ và số, tối đa 72 byte và không chứa ký tự NUL.';
        }
        if ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Xác nhận mật khẩu không khớp.';
        }
        if (!isset($errors['email']) && User::findByEmail($email)) {
            $errors['email'] = 'Email hoặc số điện thoại đã được sử dụng';
        }
        if (!isset($errors['phone']) && User::findByPhone($phone)) {
            $errors['phone'] = 'Email hoặc số điện thoại đã được sử dụng';
        }

        if (!empty($errors)) {
            $this->bareView('auth/register', [
                'pageTitle' => 'Đăng ký',
                'errors'    => $errors,
                'old'       => ['full_name' => $fullName, 'email' => $email, 'phone' => $phone],
            ]);
            return;
        }

        User::create($fullName, $email, $password, $phone ?: null);

        $this->flash('success', 'Đăng ký thành công, vui lòng đăng nhập.');
        $this->redirect('/login');
    }

    public function loginForm(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
            return;
        }
        // Nhớ trang trước đó (cùng site) để đăng nhập xong quay lại, nếu requireLogin chưa ghi
        if (empty($_SESSION['redirect_after_login'])) {
            $previous = $this->localPath($_SERVER['HTTP_REFERER'] ?? '');
            if ($previous !== null && !preg_match('#^' . preg_quote(BASE_URL, '#') . '/(login|register)\b#', $previous)) {
                $_SESSION['redirect_after_login'] = $previous;
            }
        }
        $this->bareView('auth/login', ['pageTitle' => 'Đăng nhập']);
    }

    public function login(): void
    {
        Csrf::verifyRequest();

        // Ô "Email hoặc số điện thoại" (giữ tương thích tên trường cũ "email")
        $login = trim((string) ($this->input('login') ?? $this->input('email') ?? ''));
        $password = $this->passwordInput('password');

        if ($login === '' || $password === '') {
            // Ô bỏ trống được tô đỏ kèm hướng dẫn (trạng thái "Nhập thiếu" trên Figma)
            $invalid = array_keys(array_filter(['login' => $login === '', 'password' => $password === '']));
            $this->loginFailed($login, 'Cần nhập các trường bắt buộc', null, false, $invalid);
            return;
        }

        $user = User::findByLogin($login);

        // Không cho biết sai tài khoản hay sai mật khẩu
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->loginFailed($login, 'Tài khoản hoặc mật khẩu chưa đúng', $user, true);
            return;
        }

        if ($user['status'] !== 'active') {
            $this->loginFailed($login, 'Tài khoản đang bị khóa, vui lòng liên hệ quản trị viên', $user, true);
            return;
        }

        Auth::login($user);
        AuditLog::write('login_success', 'auth', $user['id'], null, ['login' => $login], (int) $user['id']);

        $previous = $this->localPath($_SESSION['redirect_after_login'] ?? '');
        unset($_SESSION['redirect_after_login']);

        if ($user['role'] === 'admin') {
            $isAdminPage = $previous !== null && str_starts_with($previous, BASE_URL . '/admin');
            $redirectTo = $isAdminPage ? $previous : BASE_URL . '/admin';
        } else {
            $redirectTo = $previous ?? BASE_URL . '/';
        }
        header('Location: ' . $redirectTo);
        exit;
    }

    public function logout(): void
    {
        Csrf::verifyRequest();
        Auth::logout();
        $this->redirect('/');
    }

    private function loginFailed(string $login, string $message, ?array $user, bool $log, array $invalid = []): void
    {
        if ($log) {
            AuditLog::write('login_failed', 'auth', $user['id'] ?? null, null,
                ['login' => mb_substr($login, 0, 150), 'reason' => $message], $user ? (int) $user['id'] : null);
        }
        $this->bareView('auth/login', [
            'pageTitle' => 'Đăng nhập',
            'errors'    => [$message],
            'invalid'   => $invalid,
            'old'       => ['login' => $login],
        ]);
    }

    /**
     * Chỉ chấp nhận đường dẫn nội bộ (chống open redirect): URL tuyệt đối phải cùng host,
     * kết quả luôn bắt đầu bằng "/" và không phải "//".
     */
    private function localPath(string $url): ?string
    {
        if ($url === '') {
            return null;
        }
        $parts = parse_url($url);
        if ($parts === false) {
            return null;
        }
        if (isset($parts['host']) && $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '') !== ($_SERVER['HTTP_HOST'] ?? '')) {
            return null;
        }
        $path = ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
        return str_starts_with($path, '/') && !str_starts_with($path, '//') ? $path : null;
    }
}
