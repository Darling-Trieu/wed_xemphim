<?php

require_once __DIR__ . '/../../models/User.php';

class AdminCustomerController extends Controller
{
    public function index(): void
    {
        Auth::requireAdmin();
        $this->adminView('admin/customers/index', [
            'pageTitle' => 'Quản lý khách hàng',
            'customers' => User::allCustomers(),
        ]);
    }

    public function toggleStatus(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();
        User::toggleStatus((int) $id);
        $this->flash('success', 'Đã cập nhật trạng thái tài khoản.');
        $this->redirect('/admin/customers');
    }
}
