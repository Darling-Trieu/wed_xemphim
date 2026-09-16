<?php

/** @var Router $router */

// ---------------------------------------------------------------- Guest / User
$router->get('/', 'HomeController@index');

$router->get('/movies', 'MovieController@index');
$router->get('/movies/{id}', 'MovieController@show');
$router->post('/movies/{id}/reviews', 'MovieController@storeReview');

$router->get('/showtimes', 'ShowtimeController@search');
$router->get('/showtimes/{id}/seats', 'ShowtimeController@seats');

$router->get('/booking/{showtimeId}', 'BookingController@create');
$router->post('/booking/{showtimeId}', 'BookingController@store');
$router->get('/booking/success/{code}', 'BookingController@success');

$router->get('/register', 'AuthController@registerForm');
$router->post('/register', 'AuthController@register');
$router->get('/login', 'AuthController@loginForm');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');

$router->get('/account', 'AccountController@profile');
$router->post('/account', 'AccountController@update');
$router->get('/account/bookings', 'AccountController@bookings');
$router->get('/account/bookings/{code}', 'AccountController@bookingDetail');

// ---------------------------------------------------------------- Admin
$router->get('/admin', 'admin/AdminDashboardController@index');

$router->get('/admin/movies', 'admin/AdminMovieController@index');
$router->get('/admin/movies/create', 'admin/AdminMovieController@createForm');
$router->post('/admin/movies', 'admin/AdminMovieController@store');
$router->get('/admin/movies/{id}/edit', 'admin/AdminMovieController@editForm');
$router->post('/admin/movies/{id}', 'admin/AdminMovieController@update');
$router->post('/admin/movies/{id}/delete', 'admin/AdminMovieController@delete');

$router->get('/admin/genres', 'admin/AdminGenreController@index');
$router->post('/admin/genres', 'admin/AdminGenreController@store');
$router->post('/admin/genres/{id}/delete', 'admin/AdminGenreController@delete');

$router->get('/admin/cinemas', 'admin/AdminCinemaController@index');
$router->get('/admin/cinemas/create', 'admin/AdminCinemaController@createForm');
$router->post('/admin/cinemas', 'admin/AdminCinemaController@store');
$router->get('/admin/cinemas/{id}/edit', 'admin/AdminCinemaController@editForm');
$router->post('/admin/cinemas/{id}', 'admin/AdminCinemaController@update');
$router->post('/admin/cinemas/{id}/delete', 'admin/AdminCinemaController@delete');

$router->get('/admin/rooms', 'admin/AdminRoomController@index');
$router->get('/admin/rooms/create', 'admin/AdminRoomController@createForm');
$router->post('/admin/rooms', 'admin/AdminRoomController@store');
$router->get('/admin/rooms/{id}/seats', 'admin/AdminRoomController@seats');
$router->post('/admin/rooms/{id}/seats', 'admin/AdminRoomController@updateSeats');
$router->post('/admin/rooms/{id}/delete', 'admin/AdminRoomController@delete');

$router->get('/admin/showtimes', 'admin/AdminShowtimeController@index');
$router->get('/admin/showtimes/create', 'admin/AdminShowtimeController@createForm');
$router->post('/admin/showtimes', 'admin/AdminShowtimeController@store');
$router->get('/admin/showtimes/{id}/edit', 'admin/AdminShowtimeController@editForm');
$router->post('/admin/showtimes/{id}', 'admin/AdminShowtimeController@update');
$router->post('/admin/showtimes/{id}/delete', 'admin/AdminShowtimeController@delete');

$router->get('/admin/bookings', 'admin/AdminTicketController@index');
$router->get('/admin/bookings/{code}', 'admin/AdminTicketController@show');
$router->post('/admin/bookings/{code}/cancel', 'admin/AdminTicketController@cancel');

$router->get('/admin/customers', 'admin/AdminCustomerController@index');
$router->post('/admin/customers/{id}/toggle', 'admin/AdminCustomerController@toggleStatus');
