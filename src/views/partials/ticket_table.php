<?php /** @var array $seats  vé đọc từ snapshot (BookingSeat::allByBooking) */ ?>
<div class="table-responsive" style="margin:12px 0;">
    <table class="data-table price-table">
        <thead><tr><th>Ghế</th><th>Loại ghế</th><th class="num">Giá</th></tr></thead>
        <tbody>
            <?php foreach ($seats as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['seat_label']) ?></td>
                    <td><?= htmlspecialchars($s['seat_type_name']) ?></td>
                    <td class="num"><?= number_format($s['price'], 0, ',', '.') ?>đ</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
