<?php

require_once __DIR__ . '/../../models/Cinema.php';
require_once __DIR__ . '/../../models/AuditLog.php';

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

        $data = $this->collectInput();
        if ($error = $this->validate($data)) {
            $this->flash('error', $error);
            $this->redirect('/admin/cinemas/create');
            return;
        }

        $id = Cinema::create($data);
        AuditLog::write('create', 'cinema', $id, null, $data);
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

        $before = Cinema::find((int) $id);
        if (!$before) {
            $this->notFound();
            return;
        }

        $data = $this->collectInput();
        if ($error = $this->validate($data)) {
            $this->flash('error', $error);
            $this->redirect('/admin/cinemas/' . (int) $id . '/edit');
            return;
        }

        Cinema::update((int) $id, $data);
        AuditLog::write('update', 'cinema', $id, $before, $data);
        $this->flash('success', 'Đã cập nhật rạp chiếu.');
        $this->redirect('/admin/cinemas');
    }

    /** BR09: rạp đã có phòng chiếu không xóa cứng (sẽ kéo theo phòng/ghế/suất), chỉ ngừng hoạt động. */
    public function delete(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();

        $cinema = Cinema::find((int) $id);
        if (!$cinema) {
            $this->notFound();
            return;
        }

        if (Cinema::hasRooms((int) $id)) {
            Cinema::setStatus((int) $id, 'inactive');
            AuditLog::write('deactivate', 'cinema', $id, ['status' => $cinema['status']], ['status' => 'inactive']);
            $this->flash('success', 'Rạp đã có phòng chiếu nên không xóa, đã chuyển sang Ngừng hoạt động.');
        } else {
            Cinema::delete((int) $id);
            AuditLog::write('delete', 'cinema', $id, $cinema, null);
            $this->flash('success', 'Đã xóa rạp chiếu.');
        }
        $this->redirect('/admin/cinemas');
    }

    private function collectInput(): array
    {
        return [
            'name'    => (string) $this->input('name'),
            'address' => (string) $this->input('address'),
            'city'    => (string) $this->input('city'),
            'phone'   => $this->input('phone') ?: null,
            'status'  => (string) $this->input('status', 'active'),
        ];
    }

    private function validate(array $data): ?string
    {
        if (!$this->validLength($data['name'], 150)) {
            return 'Tên rạp không được để trống và tối đa 150 ký tự.';
        }
        if (!$this->validLength($data['address'], 255)) {
            return 'Địa chỉ không được để trống và tối đa 255 ký tự.';
        }
        if (!$this->validLength($data['city'], 100)) {
            return 'Thành phố không được để trống và tối đa 100 ký tự.';
        }
        if ($data['phone'] !== null && !preg_match('/^[0-9 +().-]{8,20}$/', $data['phone'])) {
            return 'Số điện thoại rạp không hợp lệ.';
        }
        if (!in_array($data['status'], ['active', 'inactive'], true)) {
            return 'Trạng thái rạp không hợp lệ.';
        }
        return null;
    }
}
