<?php
$pretty = static function (?string $json): string {
    if ($json === null) {
        return '';
    }
    $data = json_decode($json, true);
    return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
<table class="data-table">
    <thead><tr><th>Thời điểm</th><th>Người thực hiện</th><th>Hành động</th><th>Đối tượng</th><th>Trước</th><th>Sau</th><th>IP</th></tr></thead>
    <tbody>
        <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($log['created_at']))) ?></td>
                <td><?= htmlspecialchars($log['user_email'] ?? '(khách)') ?></td>
                <td><?= htmlspecialchars($log['action']) ?></td>
                <td><?= htmlspecialchars(($entities[$log['entity']] ?? $log['entity']) . ($log['entity_id'] !== null ? ' #' . $log['entity_id'] : '')) ?></td>
                <td><pre class="log-data"><?= htmlspecialchars($pretty($log['before_data'])) ?></pre></td>
                <td><pre class="log-data"><?= htmlspecialchars($pretty($log['after_data'])) ?></pre></td>
                <td><?= htmlspecialchars($log['ip_address'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($logs)): ?>
            <tr><td colspan="7">Chưa có nhật ký nào.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>
