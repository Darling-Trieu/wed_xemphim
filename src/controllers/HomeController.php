<?php

require_once __DIR__ . '/../models/Movie.php';

class HomeController extends Controller
{
    public function index(): void
    {
        $nowShowing = Movie::allByStatus('now_showing');
        $comingSoon = Movie::allByStatus('coming_soon');

        $this->view('home/index', [
            'pageTitle'  => 'Trang chủ',
            'nowShowing' => $nowShowing,
            'comingSoon' => $comingSoon,
        ]);
    }
}
