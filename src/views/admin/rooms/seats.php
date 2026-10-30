<?php
$byRow = [];
foreach ($seats as $seat) {
    $byRow[$seat['seat_row']][] = $seat;
}
ksort($byRow);

// Cùng quy ước màu với sơ đồ ghế phía khách (public/js/seat-map.js)
$typeClass = static function (string $name): string {
    if ($name === 'VIP') return 'type-vip';
    if ($name === 'Đôi') return 'type-doi';
    return 'type-thuong';
};
$maxSeatNumber = $seats ? max(array_map(static fn ($s) => (int) $s['seat_number'], $seats)) : 1;
$typeClassById = [];
foreach ($seatTypes as $type) {
    $typeClassById[(int) $type['id']] = $typeClass($type['name']);
}
?>
<h1>Sơ đồ ghế - <?= htmlspecialchars($room['name']) ?> (<?= htmlspecialchars($room['cinema_name']) ?>)</h1>

<form class="admin-card" method="post" action="<?= BASE_URL ?>/admin/rooms/<?= (int) $room['id'] ?>/seats" style="max-width:none;">
    <?= Csrf::field() ?>
    <div class="seat-editor-legend">
        <?php foreach ($seatTypes as $type): ?>
            <span><i class="seat-editor-swatch <?= $typeClassById[(int) $type['id']] ?>"></i><?= htmlspecialchars($type['name']) ?></span>
        <?php endforeach; ?>
    </div>
    <div class="seat-editor-grid" style="--cols: <?= (int) $maxSeatNumber ?>;">
        <div class="screen-bar seat-editor-screen">MÀN HÌNH</div>
        <?php foreach ($byRow as $rowLabel => $rowSeats): ?>
            <div class="seat-editor-row">
                <label><?= htmlspecialchars($rowLabel) ?></label>
                <?php foreach ($rowSeats as $seat): ?>
                    <div class="seat-editor-cell <?= $typeClassById[(int) $seat['seat_type_id']] ?? 'type-thuong' ?>" style="grid-column: <?= (int) $seat['seat_number'] + 1 ?>;">
                        <span class="seat-editor-name"><?= htmlspecialchars($rowLabel . $seat['seat_number']) ?></span>
                        <select name="seat_type[<?= (int) $seat['id'] ?>]" title="<?= htmlspecialchars($rowLabel . $seat['seat_number']) ?>">
                            <?php foreach ($seatTypes as $type): ?>
                                <option value="<?= (int) $type['id'] ?>" data-class="<?= $typeClassById[(int) $type['id']] ?>" <?= (int) $type['id'] === (int) $seat['seat_type_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($type['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <p class="movie-meta">Chọn loại ghế cho từng ghế (Thường / VIP / Đôi) rồi lưu lại.</p>
    <button type="submit" class="btn">Lưu sơ đồ ghế</button>
    <a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/rooms">&larr; Quay lại</a>
</form>

<script>
// Đổi màu ô ngay khi chọn loại ghế khác
document.querySelectorAll('.seat-editor-cell select').forEach(function (select) {
    select.addEventListener('change', function () {
        var cell = select.closest('.seat-editor-cell');
        cell.classList.remove('type-thuong', 'type-vip', 'type-doi');
        cell.classList.add(select.selectedOptions[0].dataset.class);
    });
});
</script>
