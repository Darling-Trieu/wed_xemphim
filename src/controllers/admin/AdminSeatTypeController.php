<?php

require_once __DIR__ . '/../../models/SeatType.php';
require_once __DIR__ . '/../../models/AuditLog.php';

/** Quản lý loại ghế và phụ thu (FR09, BR05). */
class AdminSeatTypeController extends Controller
{
    private const MAX_SURCHARGE = 1000000;

    public function index(): void
    {
        Auth::requireAdmin();
        $this->adminView('admin/seat_types/index', [
            'pageTitle' => 'Loại ghế & phụ thu',
            'seatTypes' => SeatType::allWithUsage(),
        ]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();

        [$name, $surcharge, $error] = $this->validatedInput(null);
        if ($error) {
            $this->flash('error', $error);
        } else {
            $id = SeatType::create($name, $surcharge);
            AuditLog::write('create', 'seat_type', $id, null, ['name' => $name, 'surcharge' => $surcharge]);
            $this->flash('success', 'Đã thêm loại ghế ' . $name . '.');
        }
        $this->redirect('/admin/seat-types');
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();

        $type = SeatType::find((int) $id);
        if (!$type) {
            $this->notFound();
            return;
        }

        [$name, $surcharge, $error] = $this->validatedInput((int) $id);
        if ($error) {
            $this->flash('error', $error);
        } else {
            SeatType::update((int) $id, $name, $surcharge);
            AuditLog::write('update', 'seat_type', $id,
                ['name' => $type['name'], 'surcharge' => (int) $type['surcharge']],
                ['name' => $name, 'surcharge' => $surcharge]);
            $this->flash('success', 'Đã cập nhật loại ghế ' . $name . '. Vé đã bán vẫn giữ giá cũ.');
        }
        $this->redirect('/admin/seat-types');
    }

    /** Chỉ xóa được loại ghế không còn ghế nào dùng, và phải còn ít nhất 1 loại ghế. */
    public function delete(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();

        $types = array_column(SeatType::allWithUsage(), null, 'id');
        $type = $types[(int) $id] ?? null;
        if (!$type) {
            $this->notFound();
            return;
        }

        if ((int) $type['seat_count'] > 0) {
            $this->flash('error', 'Loại ghế đang được ' . (int) $type['seat_count'] . ' ghế sử dụng, không xóa được.');
        } elseif (count($types) <= 1) {
            $this->flash('error', 'Phải còn ít nhất 1 loại ghế.');
        } else {
            SeatType::delete((int) $id);
            AuditLog::write('delete', 'seat_type', $id, ['name' => $type['name'], 'surcharge' => (int) $type['surcharge']], null);
            $this->flash('success', 'Đã xóa loại ghế ' . $type['name'] . '.');
        }
        $this->redirect('/admin/seat-types');
    }

    /** @return array{0: string, 1: int, 2: ?string} [tên, phụ thu, lỗi] */
    private function validatedInput(?int $excludeId): array
    {
        $name = (string) $this->input('name', '');
        $surchargeRaw = (string) $this->input('surcharge', '');
        $surcharge = (int) $surchargeRaw;

        if (!$this->validLength($name, 50)) {
            return [$name, $surcharge, 'Tên loại ghế không được để trống và tối đa 50 ký tự.'];
        }
        if (!ctype_digit($surchargeRaw) || $surcharge > self::MAX_SURCHARGE) {
            return [$name, $surcharge, 'Phụ thu phải là số nguyên từ 0 đến 1.000.000đ.'];
        }
        if (SeatType::nameExists($name, $excludeId)) {
            return [$name, $surcharge, 'Tên loại ghế đã tồn tại.'];
        }
        return [$name, $surcharge, null];
    }
}
