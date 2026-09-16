<?php

require_once __DIR__ . '/../../models/Genre.php';

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
        if ($name !== '') {
            Genre::create($name);
            $this->flash('success', 'Đã thêm thể loại.');
        }

        $this->redirect('/admin/genres');
    }

    public function delete(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verifyRequest();
        Genre::delete((int) $id);
        $this->flash('success', 'Đã xóa thể loại.');
        $this->redirect('/admin/genres');
    }
}
