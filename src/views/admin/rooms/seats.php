<?php
/**
 * Thiết kế sơ đồ ghế (UC11): mỗi ô chọn loại ghế, Ngừng sử dụng hoặc Lối đi; cột cuối chọn
 * nhanh cho cả hàng. Giá trị gửi lên: "t:<id loại ghế>" | "inactive" | "aisle".
 *
 * @var array $room
 * @var array $seats
 * @var array $seatTypes
 * @var int[] $ticketSeatIds
 */
$byRow = [];
foreach ($seats as $seat) {
    $byRow[$seat['seat_row']][] = $seat;
}
uksort($byRow, static fn ($a, $b) => strlen($a) <=> strlen($b) ?: strcmp($a, $b));

// Cùng quy ước màu với sơ đồ ghế phía khách (ts/seat-map.ts)
$typeClass = static function (string $name): string {
    if ($name === 'VIP') return 'type-vip';
    if ($name === 'Đôi') return 'type-doi';
    return 'type-thuong';
};
$typeClassById = [];
foreach ($seatTypes as $type) {
    $typeClassById[(int) $type['id']] = $typeClass($type['name']);
}
$maxSeatNumber = $seats ? max(array_map(static fn ($s) => (int) $s['seat_number'], $seats)) : 1;

$cellValue = static fn (array $seat): string => $seat['status'] === 'active' ? 't:' . (int) $seat['seat_type_id'] : $seat['status'];
$options = static function (string $selected) use ($seatTypes, $typeClassById): string {
    $html = '';
    foreach ($seatTypes as $type) {
        $value = 't:' . (int) $type['id'];
        $html .= sprintf('<option value="%s" data-class="%s"%s>%s</option>', $value, $typeClassById[(int) $type['id']],
            $selected === $value ? ' selected' : '', htmlspecialchars($type['name']));
    }
    foreach (['inactive' => 'Ngừng sử dụng', 'aisle' => 'Lối đi'] as $value => $label) {
        $html .= sprintf('<option value="%s"%s>%s</option>', $value, $selected === $value ? ' selected' : '', $label);
    }
    return $html;
};
?>
<h1>Sơ đồ ghế - <?= htmlspecialchars($room['name']) ?> (<?= htmlspecialchars($room['cinema_name']) ?>)</h1>
<p class="movie-meta">
    <?= htmlspecialchars(Room::TYPES[$room['room_type']] ?? $room['room_type']) ?> &middot;
    <a href="<?= BASE_URL ?>/admin/rooms/<?= (int) $room['id'] ?>/edit">Sửa thông tin phòng</a> &middot;
    <a href="<?= BASE_URL ?>/admin/seat-types">Loại ghế &amp; phụ thu</a>
</p>

<form class="admin-card" method="post" action="<?= BASE_URL ?>/admin/rooms/<?= (int) $room['id'] ?>/seats" style="max-width:none;">
    <?= Csrf::field() ?>
    <div class="seat-editor-legend">
        <?php foreach ($seatTypes as $type): ?>
            <span><i class="seat-editor-swatch <?= $typeClassById[(int) $type['id']] ?>"></i><?= htmlspecialchars($type['name']) ?></span>
        <?php endforeach; ?>
        <span><i class="seat-editor-swatch" style="border-style:dashed;opacity:.6"></i>Ngừng sử dụng</span>
        <span><i class="seat-editor-swatch" style="border-style:dotted;background:none"></i>Lối đi</span>
        <span>• = ghế đã có vé (không đổi thành lối đi được)</span>
    </div>
    <div class="seat-editor-grid" style="--cols: <?= (int) $maxSeatNumber ?>;">
        <div class="screen-bar seat-editor-screen">MÀN HÌNH</div>
        <?php foreach ($byRow as $rowLabel => $rowSeats): ?>
            <div class="seat-editor-row" data-row="<?= htmlspecialchars($rowLabel) ?>">
                <label><?= htmlspecialchars($rowLabel) ?></label>
                <?php foreach ($rowSeats as $seat): ?>
                    <?php
                    $classes = [$typeClassById[(int) $seat['seat_type_id']] ?? 'type-thuong'];
                    if ($seat['status'] !== 'active') $classes[] = 'is-' . $seat['status'];
                    if (in_array((int) $seat['id'], $ticketSeatIds, true)) $classes[] = 'has-ticket';
                    $seatLabel = $rowLabel . $seat['seat_number'];
                    ?>
                    <div class="seat-editor-cell <?= implode(' ', $classes) ?>" style="grid-column: <?= (int) $seat['seat_number'] + 1 ?>;">
                        <span class="seat-editor-name"><?= htmlspecialchars($seatLabel) ?></span>
                        <select name="seat[<?= (int) $seat['id'] ?>]" title="<?= htmlspecialchars($seatLabel) ?>">
                            <?= $options($cellValue($seat)) ?>
                        </select>
                    </div>
                <?php endforeach; ?>
                <div class="seat-editor-rowfill">
                    <select aria-label="Áp dụng cho cả hàng <?= htmlspecialchars($rowLabel) ?>">
                        <option value="">Cả hàng...</option>
                        <?= $options('') ?>
                    </select>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <p class="movie-meta">Chọn loại ghế cho từng ghế hoặc cả hàng, đánh dấu ghế Ngừng sử dụng / ô Lối đi rồi lưu lại.</p>
    <button type="submit" class="btn">Lưu sơ đồ ghế</button>
    <a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/rooms">&larr; Quay lại</a>
</form>

<script>
// Đổi màu ô ngay khi chọn; ô "Cả hàng" áp dụng lựa chọn cho mọi ghế trong hàng
function paintCell(select) {
    var cell = select.closest('.seat-editor-cell');
    var option = select.selectedOptions[0];
    cell.classList.remove('type-thuong', 'type-vip', 'type-doi', 'is-inactive', 'is-aisle');
    if (option.dataset.class) {
        cell.classList.add(option.dataset.class);
    } else {
        cell.classList.add('is-' + select.value);
    }
}
document.querySelectorAll('.seat-editor-cell select').forEach(function (select) {
    select.addEventListener('change', function () { paintCell(select); });
});
document.querySelectorAll('.seat-editor-rowfill select').forEach(function (fill) {
    fill.addEventListener('change', function () {
        if (!fill.value) return;
        fill.closest('.seat-editor-row').querySelectorAll('.seat-editor-cell select').forEach(function (select) {
            select.value = fill.value;
            paintCell(select);
        });
        fill.value = '';
    });
});
</script>
