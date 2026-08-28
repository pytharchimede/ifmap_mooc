<?php
namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void { $this->add('GET', $path, $handler); }
    public function post(string $path, callable|array $handler): void { $this->add('POST', $path, $handler); }
    private function add(string $method, string $path, callable|array $handler): void { $this->routes[$method][$path] = $handler; }

    public function dispatch(string $method, string $path): void
    {
        $path = '/' . trim($path, '/');
        if ($path !== '/') $path = rtrim($path, '/');
        $handler = $this->routes[$method][$path] ?? null;
        if (!$handler) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Page introuvable']);
            return;
        }
        if (is_array($handler)) {
            [$class, $action] = $handler;
            (new $class())->$action();
            return;
        }
        $handler();
    }
}

