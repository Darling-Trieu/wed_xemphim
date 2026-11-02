<?php

require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/AuditLog.php';

class AdminCustomerController extends Controller
{
    public function index(): void
    {
        Auth::requireAdmin();

        $keyword = (string) $this->input('q', '');
        $error = null;
        if (mb_strlen($keyword) > 100) {
            $error = 'Từ khóa tìm kiếm tối đa 100 ký tự.';
            $keyword = '';
        }

        $this->adminView('admin/customers/index', [
            'pageTitle' => 'Quản lý tài khoản',
            'customers' => User::search($keyword),
            'keyword'   => $keyword,
            'error'     => $error,
        ]);
    }

    /** Khóa / mở khóa tài khoản (UC14). Không cho tự khóa tài khoản đang đăng nhập. */
    public function toggleStatus(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();

        $user = User::find((int) $id);
        if (!$user) {
            $this->notFound();
            return;
        }

        if ((int) $user['id'] === Auth::id()) {
            $this->flash('error', 'Không thể tự khóa tài khoản đang đăng nhập');
            $this->redirect('/admin/customers');
            return;
        }

        $newStatus = $user['status'] === 'active' ? 'locked' : 'active';
        User::setStatus((int) $user['id'], $newStatus);
        AuditLog::write($newStatus === 'locked' ? 'lock' : 'unlock', 'user', $user['id'],
            ['status' => $user['status']], ['status' => $newStatus]);

        $this->flash('success', ($newStatus === 'locked' ? 'Đã khóa' : 'Đã mở khóa') . ' tài khoản ' . $user['email'] . '.');
        $this->redirect('/admin/customers');
    }
}
