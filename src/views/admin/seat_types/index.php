<?php /** @var array $seatTypes */ ?>
<h1>Loại ghế &amp; phụ thu</h1>
<p class="movie-meta">Giá ghế = giá cơ bản của suất chiếu + phụ thu của loại ghế. Đổi phụ thu chỉ áp dụng cho vé bán sau đó, vé đã bán giữ nguyên giá.</p>

<form class="admin-card filter-bar" method="post" action="<?= BASE_URL ?>/admin/seat-types" style="margin-bottom:20px;">
    <?= Csrf::field() ?>
    <input class="form-control" type="text" name="name" maxlength="50" required placeholder="Tên loại ghế, VD: Sweetbox">
    <input class="form-control" type="number" name="surcharge" min="0" max="1000000" step="1000" required placeholder="Phụ thu (đ)">
    <button type="submit" class="btn">+ Thêm loại ghế</button>
</form>

<div class="table-responsive">
<table class="data-table">
    <thead><tr><th>Tên loại ghế</th><th class="num">Phụ thu (đ)</th><th class="num">Số ghế đang dùng</th><th></th></tr></thead>
    <tbody>
        <?php foreach ($seatTypes as $type): ?>
            <?php $formId = 'seat-type-' . (int) $type['id']; ?>
            <tr>
                <td><input class="form-control" form="<?= $formId ?>" type="text" name="name" maxlength="50" required value="<?= htmlspecialchars($type['name']) ?>"></td>
                <td class="num"><input class="form-control" form="<?= $formId ?>" type="number" name="surcharge" min="0" max="1000000" step="1000" required value="<?= (int) $type['surcharge'] ?>"></td>
                <td class="num"><?= (int) $type['seat_count'] ?></td>
                <td class="actions-cell">
                    <form id="<?= $formId ?>" method="post" action="<?= BASE_URL ?>/admin/seat-types/<?= (int) $type['id'] ?>">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn-small btn-secondary">Lưu</button>
                    </form>
                    <?php if ((int) $type['seat_count'] === 0): ?>
                        <form method="post" action="<?= BASE_URL ?>/admin/seat-types/<?= (int) $type['id'] ?>/delete" onsubmit="return confirm('Xóa loại ghế này?');">
                            <?= Csrf::field() ?>
                            <button type="submit" class="btn btn-small btn-danger">Xóa</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
