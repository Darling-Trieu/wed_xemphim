<?php

require_once __DIR__ . '/../models/User.php';

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
        Csrf::verifyRequest();

        $fullName = $this->input('full_name');
        $email = strtolower((string) $this->input('email'));
        $phone = $this->input('phone');
        $password = (string) $this->input('password');
        $passwordConfirm = (string) $this->input('password_confirm');

        $errors = [];
        if ($fullName === '' || $email === '' || $password === '') {
            $errors[] = 'Vui lòng nhập đầy đủ thông tin bắt buộc.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email không hợp lệ.';
        }
        if (strlen($password) < 6) {
            $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
        }
        if ($password !== $passwordConfirm) {
            $errors[] = 'Xác nhận mật khẩu không khớp.';
        }
        if (empty($errors) && User::findByEmail($email)) {
            $errors[] = 'Email này đã được đăng ký.';
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
        $this->bareView('auth/login', ['pageTitle' => 'Đăng nhập']);
    }

    public function login(): void
    {
        Csrf::verifyRequest();

        $email = strtolower((string) $this->input('email'));
        $password = (string) $this->input('password');

        $user = User::findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->bareView('auth/login', [
                'pageTitle' => 'Đăng nhập',
                'errors'    => ['Email hoặc mật khẩu không đúng.'],
                'old'       => ['email' => $email],
            ]);
            return;
        }

        if ($user['status'] === 'locked') {
            $this->bareView('auth/login', [
                'pageTitle' => 'Đăng nhập',
                'errors'    => ['Tài khoản của bạn đã bị khóa.'],
                'old'       => ['email' => $email],
            ]);
            return;
        }

        Auth::login($user);

        $redirectTo = $_SESSION['redirect_after_login'] ?? ($user['role'] === 'admin' ? BASE_URL . '/admin' : BASE_URL . '/');
        unset($_SESSION['redirect_after_login']);
        header('Location: ' . $redirectTo);
        exit;
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/');
    }
}
