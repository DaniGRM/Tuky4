<?php
declare(strict_types=1);

// Router para usar con: php -S localhost:8000 router.php
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (is_string($uriPath)) {
    $target = __DIR__ . $uriPath;
    if ($uriPath !== '/' && is_file($target)) {
        return false;
    }
}

require_once __DIR__ . '/app/bootstrap.php';
$router = require __DIR__ . '/app/routes.php';
$router->dispatch();
