<?php
$byRow = [];
foreach ($seats as $seat) {
    $byRow[$seat['seat_row']][] = $seat;
}
ksort($byRow);
?>
<h1>Sơ đồ ghế - <?= htmlspecialchars($room['name']) ?> (<?= htmlspecialchars($room['cinema_name']) ?>)</h1>

<form class="admin-card" method="post" action="<?= BASE_URL ?>/admin/rooms/<?= (int) $room['id'] ?>/seats" style="max-width:none;">
    <?= Csrf::field() ?>
    <div class="seat-editor-grid">
        <?php foreach ($byRow as $rowLabel => $rowSeats): ?>
            <div class="seat-editor-row">
                <label><?= htmlspecialchars($rowLabel) ?></label>
                <?php foreach ($rowSeats as $seat): ?>
                    <div class="seat-editor-cell">
                        <select name="seat_type[<?= (int) $seat['id'] ?>]" title="<?= htmlspecialchars($rowLabel . $seat['seat_number']) ?>">
                            <?php foreach ($seatTypes as $type): ?>
                                <option value="<?= (int) $type['id'] ?>" <?= (int) $type['id'] === (int) $seat['seat_type_id'] ? 'selected' : '' ?>>
                                       <?= htmlspecialchars($rowLabel . $seat['seat_number']) ?>
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
