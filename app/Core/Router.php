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
            foreach ($this->routes[$method] ?? [] as $route => $candidate) {
                $parameterNames = [];
                $patternParts = [];
                foreach (explode('/', trim($route, '/')) as $segment) {
                    if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/', $segment, $match)) {
                        $parameterNames[] = $match[1];
                        $patternParts[] = '([^/]+)';
                    } else {
                        $patternParts[] = preg_quote($segment, '#');
                    }
                }
                $pattern = '/' . implode('/', $patternParts);

                if (preg_match('#^' . $pattern . '$#', $path, $matches)) {
                    array_shift($matches);
                    foreach ($parameterNames as $index => $name) {
                        $_GET[$name] = rawurldecode($matches[$index]);
                    }
                    $handler = $candidate;
                    break;
                }
            }
        }
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

