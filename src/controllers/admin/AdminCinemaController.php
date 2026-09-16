<?php

require_once __DIR__ . '/../../models/Cinema.php';

class AdminCinemaController extends Controller
{
    public function index(): void
    {
        Auth::requireAdmin();
        $this->adminView('admin/cinemas/index', [
            'pageTitle' => 'Quản lý rạp chiếu',
            'cinemas'   => Cinema::all(),
        ]);
    }

    public function createForm(): void
    {
        Auth::requireAdmin();
        $this->adminView('admin/cinemas/form', ['pageTitle' => 'Thêm rạp chiếu', 'cinema' => null]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();
        Cinema::create($this->collectInput());
        $this->flash('success', 'Đã thêm rạp chiếu.');
        $this->redirect('/admin/cinemas');
    }

    public function editForm(string $id): void
    {
        Auth::requireAdmin();
        $cinema = Cinema::find((int) $id);
        if (!$cinema) {
            $this->notFound();
            return;
        }
        $this->adminView('admin/cinemas/form', ['pageTitle' => 'Sửa rạp chiếu', 'cinema' => $cinema]);
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();
        Cinema::update((int) $id, $this->collectInput());
        $this->flash('success', 'Đã cập nhật rạp chiếu.');
        $this->redirect('/admin/cinemas');
    }

    public function delete(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();
        Cinema::delete((int) $id);
        $this->flash('success', 'Đã xóa rạp chiếu.');
        $this->redirect('/admin/cinemas');
    }

    private function collectInput(): array
    {
        return [
            'name'    => (string) $this->input('name'),
            'address' => (string) $this->input('address'),
            'city'    => (string) $this->input('city'),
            'phone'   => $this->input('phone'),
            'status'  => (string) $this->input('status', 'active'),
        ];
    }
}
