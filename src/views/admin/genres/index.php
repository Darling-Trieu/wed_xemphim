<h1>Quản lý thể loại</h1>

<form class="admin-card genre-add" method="post" action="<?= BASE_URL ?>/admin/genres">
    <?= Csrf::field() ?>
    <div class="form-group" style="margin:0;">
        <label for="genre-name">Tên thể loại mới</label>
        <div class="genre-add-row">
            <input class="form-control" type="text" id="genre-name" name="name" maxlength="100" required>
            <button type="submit" class="btn">Thêm</button>
        </div>
    </div>
</form>

<div class="table-responsive genre-list">
<table class="data-table">
    <thead><tr><th>Tên thể loại</th><th></th></tr></thead>
    <tbody>
        <?php foreach ($genres as $genre): ?>
            <tr>
                <td><?= htmlspecialchars($genre['name']) ?></td>
                <td class="actions-cell">
                    <form method="post" action="<?= BASE_URL ?>/admin/genres/<?= (int) $genre['id'] ?>/delete" onsubmit="return confirm('Xóa thể loại này?');">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn-small btn-danger">Xóa</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($genres)): ?>
            <tr><td colspan="2" class="table-empty">Chưa có thể loại nào.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>
