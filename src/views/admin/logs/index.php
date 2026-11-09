<?php
/**
 * @var array $logs
 * @var array $entities
 * @var string $entity
 */
$actionLabels = [
    'login_success'   => 'Đăng nhập thành công',
    'login_failed'    => 'Đăng nhập thất bại',
    'create'          => 'Thêm mới',
    'update'          => 'Cập nhật',
    'update_seats'    => 'Sửa sơ đồ ghế',
    'delete'          => 'Xóa',
    'deactivate'      => 'Ngừng hoạt động',
    'cancel'          => 'Hủy',
    'lock'            => 'Khóa tài khoản',
    'unlock'          => 'Mở khóa tài khoản',
    'password_change' => 'Đổi mật khẩu',
];
$fieldLabels = [
    'login'            => 'Tài khoản',
    'reason'           => 'Lý do',
    'status'           => 'Trạng thái',
    'payment'          => 'Thanh toán',
    'refund_amount'    => 'Hoàn tiền',
    'name'             => 'Tên',
    'title'            => 'Tên phim',
    'description'      => 'Mô tả',
    'duration_minutes' => 'Thời lượng (phút)',
    'age_rating'       => 'Nhãn tuổi',
    'release_date'     => 'Khởi chiếu',
    'trailer_url'      => 'Trailer',
    'poster_path'      => 'Poster',
    'created_at'       => 'Ngày tạo',
    'movie_id'         => 'Mã phim',
    'room_id'          => 'Mã phòng',
    'cinema_id'        => 'Mã rạp',
    'room_type'        => 'Loại phòng',
    'rows'             => 'Số hàng',
    'cols'             => 'Số cột',
    'seat_type_id'     => 'Mã loại ghế',
    'surcharge'        => 'Phụ thu',
    'show_date'        => 'Ngày chiếu',
    'start_time'       => 'Giờ bắt đầu',
    'end_time'         => 'Giờ kết thúc',
    'base_price'       => 'Giá cơ bản',
];

// Dữ liệu trước / sau (JSON) -> danh sách "tên trường: giá trị" đã escape
$renderData = static function (?string $json) use ($fieldLabels): string {
    $data = $json === null ? null : json_decode($json, true);
    if (!is_array($data) || $data === []) {
        return '<span class="log-empty">—</span>';
    }
    $rows = '';
    foreach ($data as $key => $value) {
        $label = $fieldLabels[$key] ?? (is_int($key) ? 'Ghế #' . $key : $key);
        $text = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : ($value === null ? '—' : (string) $value);
        $rows .= '<div><span class="log-key">' . htmlspecialchars((string) $label) . ':</span> ' . htmlspecialchars($text) . '</div>';
    }
    return '<div class="log-data">' . $rows . '</div>';
};
?>
<h1>Nhật ký hệ thống</h1>
<p class="movie-meta">Ghi lại đăng nhập (thành công / thất bại) và thao tác quản trị. Chỉ xem, không sửa hay xóa được. Hiển thị 300 dòng mới nhất.</p>

<form class="filter-bar" method="get" action="<?= BASE_URL ?>/admin/logs" style="margin-bottom:16px;">
    <select class="form-control" name="entity" style="max-width:240px;" onchange="this.form.submit()">
        <option value="">-- Tất cả --</option>
        <?php foreach ($entities as $key => $label): ?>
            <option value="<?= htmlspecialchars($key) ?>" <?= $entity === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
        <?php endforeach; ?>
    </select>
    <noscript><button type="submit" class="btn btn-small">Lọc</button></noscript>
</form>

<div class="table-responsive">
<table class="data-table log-table">
    <thead><tr><th>Thời điểm</th><th>Người thực hiện</th><th>Hành động</th><th>Đối tượng</th><th>Trước</th><th>Sau</th><th>IP</th></tr></thead>
    <tbody>
        <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($log['created_at']))) ?></td>
                <td><?= htmlspecialchars($log['user_email'] ?? '(khách)') ?></td>
                <td><?= htmlspecialchars($actionLabels[$log['action']] ?? $log['action']) ?></td>
                <td><?= htmlspecialchars(($entities[$log['entity']] ?? $log['entity']) . ($log['entity_id'] !== null ? ' #' . $log['entity_id'] : '')) ?></td>
                <td><?= $renderData($log['before_data']) ?></td>
                <td><?= $renderData($log['after_data']) ?></td>
                <td><?= htmlspecialchars($log['ip_address'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($logs)): ?>
            <tr><td colspan="7">Chưa có nhật ký nào.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>
