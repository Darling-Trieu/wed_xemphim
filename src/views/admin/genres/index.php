<h1>Quản lý thể loại</h1>

<form class="admin-card" method="post" action="<?= BASE_URL ?>/admin/genres" style="margin-bottom:20px;">
    <?= Csrf::field() ?>
    <div class="form-group" style="display:flex;gap:10px;align-items:flex-end;">
        <div style="flex:1;">
            <label>Tên thể loại mới</label>
            <input class="form-control" type="text" name="name" required>
        </div>
        <button type="submit" class="btn">Thêm</button>
    </div>
</form>

<div class="table-responsive" style="max-width:480px;">
<table class="data-table">
    <thead><tr><th>Tên thể loại</th><th></th></tr></thead>
    <tbody>
        <?php foreach ($genres as $genre): ?>
            <tr>
                <td><?= htmlspecialchars($genre['name']) ?></td>
                <td>
                    <form method="post" action="<?= BASE_URL ?>/admin/genres/<?= (int) $genre['id'] ?>/delete" onsubmit="return confirm('Xóa thể loại này?');">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn-small btn-danger">Xóa</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
