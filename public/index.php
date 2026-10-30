<?php

require dirname(__DIR__) . '/src/config/config.php';
require dirname(__DIR__) . '/src/core/Database.php';
require dirname(__DIR__) . '/src/core/Model.php';
require dirname(__DIR__) . '/src/core/Controller.php';
require dirname(__DIR__) . '/src/core/Auth.php';
require dirname(__DIR__) . '/src/core/Csrf.php';
require dirname(__DIR__) . '/src/core/Router.php';
require dirname(__DIR__) . '/src/core/ErrorHandler.php';

ErrorHandler::register();
Auth::syncWithDatabase();

$router = new Router();
require dirname(__DIR__) . '/src/routes.php';

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
