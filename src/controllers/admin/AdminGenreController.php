<?php

require_once __DIR__ . '/../../models/Genre.php';
require_once __DIR__ . '/../../models/AuditLog.php';

class AdminGenreController extends Controller
{
    public function index(): void
    {
        Auth::requireAdmin();
        $this->adminView('admin/genres/index', [
            'pageTitle' => 'Quản lý thể loại',
            'genres'    => Genre::all(),
        ]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();

        $name = (string) $this->input('name');
        if (!$this->validLength($name, 100)) {
            $this->flash('error', 'Tên thể loại không được để trống và tối đa 100 ký tự.');
        } elseif (Genre::nameExists($name)) {
            $this->flash('error', 'Thể loại "' . $name . '" đã tồn tại.');
        } else {
            $id = Genre::create($name);
            AuditLog::write('create', 'genre', $id, null, ['name' => $name]);
            $this->flash('success', 'Đã thêm thể loại.');
        }

        $this->redirect('/admin/genres');
    }

    public function delete(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();

        $genre = Genre::find((int) $id);
        if (!$genre) {
            $this->notFound();
            return;
        }

        Genre::delete((int) $id);
        AuditLog::write('delete', 'genre', $id, $genre, null);
        $this->flash('success', 'Đã xóa thể loại.');
        $this->redirect('/admin/genres');
    }
}
