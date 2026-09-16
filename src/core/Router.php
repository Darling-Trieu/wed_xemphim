<?php

class Router
{
    /** @var array<int, array{method:string, pattern:string, regex:string, params:string[], handler:string}> */
    private array $routes = [];

    public function get(string $pattern, string $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, string $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    private function add(string $method, string $pattern, string $handler): void
    {
        $paramNames = [];
        $regex = preg_replace_callback('/\{([a-zA-Z_]+)\}/', function ($m) use (&$paramNames) {
            $paramNames[] = $m[1];
            return '([^/]+)';
        }, $pattern);

        $this->routes[] = [
            'method'  => $method,
            'regex'   => '#^' . $regex . '$#',
            'params'  => $paramNames,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['regex'], $path, $matches)) {
                array_shift($matches);
                $args = array_combine($route['params'], $matches);
                $this->call($route['handler'], $args);
                return;
            }
        }

        http_response_code(404);
        require dirname(__DIR__) . '/views/errors/404.php';
    }

    private function call(string $handler, array $args): void
    {
        [$controllerName, $action] = explode('@', $handler);
        $controllerClass = str_replace('/', '\\', $controllerName);
        $controllerFile = dirname(__DIR__) . '/controllers/' . $controllerName . '.php';

        require_once $controllerFile;

        $shortClass = basename(str_replace('\\', '/', $controllerClass));
        $controller = new $shortClass();
        call_user_func_array([$controller, $action], $args);
    }
}
