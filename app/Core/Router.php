<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Router
{
    /** @var array<string, array<string, callable|array{0:class-string,1:string}>> */
    private array $routes = [];

    public function get(string $path, callable|array $handler): self
    {
        return $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): self
    {
        return $this->add('POST', $path, $handler);
    }

    public function any(string $path, callable|array $handler): self
    {
        $this->add('GET', $path, $handler);
        $this->add('POST', $path, $handler);
        $this->add('PUT', $path, $handler);
        $this->add('PATCH', $path, $handler);
        $this->add('DELETE', $path, $handler);
        $this->add('OPTIONS', $path, $handler);
        return $this;
    }

    public function add(string $method, string $path, callable|array $handler): self
    {
        $method = strtoupper(trim($method));
        if ($method === '') {
            throw new RuntimeException('Metodo HTTP invalido.');
        }

        $normalizedPath = $this->normalizePath($path);
        $this->routes[$method][$normalizedPath] = $handler;
        return $this;
    }

    public function dispatch(?string $method = null, ?string $uri = null): void
    {
        $method = strtoupper($method ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $path = $this->normalizePath($uri ?? ($_SERVER['REQUEST_URI'] ?? '/'));

        $handler = $this->routes[$method][$path] ?? null;
        if ($handler === null) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo '404 Not Found';
            return;
        }

        $this->invoke($handler);
    }

    private function normalizePath(string $raw): string
    {
        $path = parse_url($raw, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return '/';
        }

        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $path;
    }

    private function invoke(callable|array $handler): void
    {
        if (is_callable($handler)) {
            $handler();
            return;
        }

        if (count($handler) !== 2 || !is_string($handler[0]) || !is_string($handler[1])) {
            throw new RuntimeException('Handler de ruta invalido.');
        }

        $class = $handler[0];
        $method = $handler[1];
        $instance = new $class();

        if (!method_exists($instance, $method)) {
            throw new RuntimeException("Metodo de ruta no encontrado: {$class}::{$method}");
        }

        $instance->{$method}();
    }
}
